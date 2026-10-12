# CLAUDE.md — Proyecto RCT (Laravel)

> Este archivo va en la **raíz del proyecto** (al lado de `artisan` y `composer.json`).
> Claude Code lo lee automáticamente. Secciones completadas el 11/10/2026 tras el análisis inicial.

## Qué es este proyecto

RCT es el sistema web interno del área de Recuperaciones de Caja Trujillo (Perú). Gestiona campañas de conciliación de deudas y el control de créditos con garantía de programas de gobierno (honramientos y devoluciones a COFIDE).

- Contexto de negocio completo: `docs/RCT_CONTEXTO_NEGOCIO.md` (léelo antes de tocar reglas de negocio).
- Documento normativo vigente: `docs/campanas/2026-09_sin-atrasos-que-atrasan.pdf`.
- Idioma: código en **español** (modelos, tablas, métodos: `PropuestaFud`, `clientes_cartera`, `porAprobador()`); interfaz, mensajes y comentarios de negocio en español.

## Stack

- PHP **8.3** (Laragon: `E:\laragonin\php\php-8.3.33-Win32-vs16-x64\php.exe`; el `php` del PATH es 8.0 y falla) · Laravel **13**
- Base de datos: **MySQL 8.4** (Laragon), BD `rct_recuperaciones`. Tests: SQLite en memoria
- Frontend: **Blade** + Tailwind 3 + Alpine (Vite); CSS propio en `layouts/app.blade.php`
- Autenticación: **Breeze** (login por email/password; `/register` abierto)
- Roles y permisos: **propio**: `users.rol_id` → `roles` (`grupo` fud|aprobador, `nivel` 1–3), middleware `grupo:` (`App\Http\Middleware\CheckGrupo`, parámetros con OR)
- Colas: driver `database` configurado, **sin jobs** aún
- Almacenamiento de archivos: **local**, disco `private` (`storage/app/private`)
- Integración con el core financiero: **ninguna aún** (cartera cargada por seeder en `clientes_cartera`)

## Comandos

```bash
composer install
npm install && npm run dev
php artisan migrate --seed
php artisan serve
php artisan test            # PHPUnit 12 (hoy solo tests de Breeze, fallan en SQLite)
./vendor/bin/pint           # formato de código (si está instalado)
php artisan queue:work      # si usan colas
```

## Estructura (clásica de Laravel)

```
app/
  Http/Controllers/     # Controladores delgados: validan y delegan
  Http/Requests/        # FormRequests para toda validación de entrada
  Models/               # Eloquent
  Services/             # Lógica de negocio (crear si no existe)
    Reglas/             # Motor de reglas de campaña
    Cofide/             # Cálculo de devoluciones
  Policies/             # Autorización por rol + ámbito
  Enums/                # Estados de expediente, calificaciones, tipos
  Notifications/        # Correo / base de datos / WhatsApp
  Jobs/                 # Importaciones, validación IA, cuadres
database/migrations/
resources/views/
routes/web.php
docs/                   # Contexto, campañas, decisiones
```

Hoy **no existen** `Services/`, `Policies/`, `Enums/`, `Notifications/` ni `Jobs/`: la lógica vive en controladores y modelos.

| Módulo | Controlador(es) | Modelos | Vistas |
| --- | --- | --- | --- |
| Dashboard | `DashboardController` | — | `dashboard/index` |
| FUD (solicitud) | `FudController` | `PropuestaFud`, `FudDetalle{Cuotas,Cancelacion,Judicial}`, `FudAdjunto`, `ClienteCartera` | `fud/*` |
| Bandeja de aprobación | `BandejaController` | `PropuestaFud` | `bandeja/index` |
| Sustentos | `SustentosController` | `Sustento`, `SustentoDocumento` | `sustentos/index` |
| Revisión de sustentos | `RevisionSustentosController`, `DashboardSustentosController` | idem | `revision/*` |
| Reportes / indicadores | `ReportesController`, `IndicadoresController` | — | `reportes/dashboard`, `indicadores/index` |
| Programas de gobierno | `CarteraController` | `ExpedienteGobierno(Doc)` | `cartera/exclusiones` |
| Bitácora | `SeguimientoController` | `Seguimiento` (`::registrar()`) | `seguimiento/index` |
| Lineamientos | closure en `routes/web.php` | — | `lineamientos` (calculadora JS) |
| Asistente IA | `AsistenteController` (Gemini) | — | widget en `layouts/app` |
| Catálogos | — | `Rol`, `Zona`, `Tienda`, `Campana`, `User` | — |

## Reglas para trabajar en este código

1. **Las reglas de campaña no van en código fijo.** Porcentajes, rangos de atraso, topes, UIT y niveles de autorización se leen de tablas parametrizadas y versionadas por campaña.
2. **Montos con precisión decimal:** columnas `decimal(15,2)`; nunca `float`. Usar BCMath o una librería de dinero para cálculos.
3. **El core es la fuente de verdad** de saldos y pagos. RCT no modifica saldos.
4. **Toda acción sobre un expediente queda en bitácora** (quién, cuándo, antes/después).
5. **Solicitante ≠ aprobador**; la autorización va en Policies, no en las vistas.
6. Controladores delgados → FormRequest → Service → Model.
7. Cada regla de negocio nueva lleva su test (feature o unit).
8. No borrar documentos: versionar.
9. Si una regla del documento de campaña es ambigua (ver sección 12 del contexto), preguntar antes de implementar.

## Estado actual del sistema

- Qué funciona hoy: FUD (crear, listar, corregir con versiones `parent_fud_id`, adjuntos) → bandeja (aprobar/observar/rechazar) → sustento pendiente → carga → revisión por documento → recarga. Tableros, bitácora simple, registro de programa de gobierno (10 documentos fijos), calculadora de lineamientos, asistente Gemini.
- Qué está a medias: motor de reglas (solo límite de 4 usos y 3 UIT aproximados; tablas del Anexo I solo en JS), un rol por usuario sin ámbito, estados del expediente (4 de 12), bitácora sin antes/después, sin reporte mensual, programas de gobierno solo como habilitación a campaña.
- Qué falla o molesta (diagnóstico 11/10/2026): alias `grupo` pisado en `bootstrap/app.php`, el solicitante elige al aprobador, se puede aprobar dos veces, tablero con `rand()`, KPIs de sustentos contando estado `Completo` (ya no se usa), tests rotos por `ALTER ... MODIFY`.
- Qué datos se cargan y cómo: cartera en `clientes_cartera` vía seeder (sin importación del core ni Excel aún); documentos subidos manualmente.
- Usuarios reales que lo usan hoy (roles): ninguno en producción. Roles semilla: Ejecutivo de Cobranza (fud/1), Ejecutivo Senior (fud/2), CZR (aprobador/1), Jefe de Recuperaciones (aprobador/2), Gerente de División (aprobador/3).

## Prioridades actuales

**Demo funcional para la Jefatura (octubre 2026).** No es producción: detalles normativos (UIT exacta, topes, rangos superpuestos, casos borde de aprobación) se ven después.
1. Que nada se rompa en pantalla (middleware, rutas del menú por rol, no aprobar dos veces).
2. Aprobador asignado automáticamente por nivel de autonomía.
3. Dashboards con datos reales de la BD + seeder de demo (~200 créditos, 3 meses).
4. Validación de documentos en modo demo (`docs/REGLAS_DEMO_VALIDACION.md`) en sustentos y programas de gobierno.
5. Aspecto profesional consistente (rojo/blanco Caja Trujillo, responsive).

Fuera de alcance por ahora: `/register`, tests, archivos sobrantes, UIT, refactor, transacciones y seguridad avanzada (salvo que rompan pantalla).

## No tocar sin avisar

- `.env` (contiene `GEMINI_API_KEY`), `config/*`.
- Migraciones ya ejecutadas: crear migraciones nuevas, no editar las existentes.
- `docs/` (contexto y reglas internas los mantiene el usuario).
- No hay integración con el core ni producción todavía.

## Modo demo (IMPORTANTE)

RCT es por ahora un **mockup funcional** para presentar. Debe verse completo y creíble; la lógica compleja puede ser básica.

- La validación de documentos (firmas, misma firma, formato, montos, DNI) se **simula** según marcadores en el nombre del archivo.
- Las reglas están en `docs/REGLAS_DEMO_VALIDACION.md`. Son **normas internas**: los marcadores nunca se muestran en la interfaz.
- Siempre implementar detrás de la interfaz `ValidadorDocumentos` para poder cambiar a validación real sin tocar la UI.
- Activado con `RCT_VALIDACION_MODO=demo` en `.env`. Nunca dejarlo activo en producción.

## Glosario rápido

SK = saldo capital · RCC = reporte crediticio SBS · CPP = Con Problemas Potenciales · UIT = unidad impositiva tributaria · CZR = Coordinador Zonal de Recuperaciones · Honramiento = pago de COFIDE por la garantía · FUD = formato de exoneración actual.
