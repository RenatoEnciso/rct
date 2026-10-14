# Product

<!-- impeccable:product-schema 1 -->

> Escrito el 14/10/2026 a partir del brief de rediseño del usuario y de `CLAUDE.md`.
> El usuario pidió trabajar de corrido, sin ronda de preguntas; lo marcado *(inferido)* no fue confirmado.

## Platform

web

## Users

Personal del área de Recuperaciones de Caja Trujillo (caja municipal, Perú), que usa RCT durante toda la jornada:

- **Ejecutivos de cobranza (asesores)** de cada tienda: priorizan su cartera del día, registran propuestas de conciliación (FUD), corrigen observaciones y cargan sustentos.
- **Coordinadores zonales (CZR)**, **Jefe de Recuperaciones** y **Gerente de División**: aprueban, observan o rechazan propuestas según su nivel de autonomía, revisan sustentos, controlan programas de gobierno (Impulso MyPerú / COFIDE) y leen la analítica de la campaña.
- **Gerencia**, como audiencia de presentaciones: de RCT salen capturas y un video para la Gerencia.

Trabajan en computadoras de escritorio y laptops de oficina (1920×1080 y 1366×768), con luz de oficina *(inferido)*.

## Product Purpose

Gestionar las campañas de conciliación de deudas y el control de créditos con garantía de programas de gobierno (honramientos y devoluciones a COFIDE), con trazabilidad de cada decisión. El éxito es recuperar más con control: cada asesor sabe a quién cobrar primero, cada propuesta llega al aprobador correcto, cada documento se valida y cada sol devuelto a COFIDE cuadra.

## Positioning

Es el sistema propio del área: une la priorización de cartera, el circuito de aprobación por niveles de autonomía, la validación de sustentos, el cálculo de reversiones y el cruce con COFIDE en un solo expediente trazable, con las reglas de campaña de Caja Trujillo.

## Operating Context

- Flujo central: Mi prioridad de hoy → Nueva propuesta FUD → Bandeja de aprobación (observar / aprobar / rechazar) → Carga de sustentos con validación automática → Revisión de sustentos → Trazabilidad del expediente.
- Programas de gobierno: candidatos, lotes de honramiento, expedientes (Anexo 13), base matriz, pagos sincronizados desde el core y devolución mensual a COFIDE con cruce TXT.
- Analítica: Centro de control (con modo presentación), priorización de cartera, análisis de recaída y alertas de control.
- Documentos de referencia: `docs/RCT_CONTEXTO_NEGOCIO.md` y el documento de campaña vigente.

## Capabilities and Constraints

- Laravel 13 + Blade, CSS propio (`public/assets/css/rct.css`) sobre Bootstrap 5.3, Bootstrap Icons, Chart.js e Inter, todo servido localmente desde `public/vendor` (sin internet).
- Pantallas densas: tablas, KPIs y estados. Deben verse ordenadas, no recargadas.
- El rediseño cambia solo lo visual (CSS, layouts, vistas Blade, JS de interfaz); no toca controladores, cálculos, rutas ni base de datos.
- Prototipo con datos de demostración; aún no está en producción.

## Brand Commitments

- Paleta actual **fija**: rojo institucional `#a3182c` (y `#7d1221`, `#fbf0f1`, `#efd2d6`), tinta `#1c2430`, grises `#5f6b7a` / `#8a94a3`, bordes `#e3e6ea` / `#cfd4da`, fondo `#f4f5f7`, y colores de estado verde `#1f7a4d`, ámbar `#a15c07`, azul `#1f4e8c` y peligro `#b42318`, con sus variantes suaves. Puede cambiar dónde y cuánto se usan, no los valores.
- Marca: cuadro rojo con "CT" y el nombre CAJA TRUJILLO · RCT · Sistema de Recuperaciones.
- Idioma: español de Perú; formato de moneda S/.
- Aspecto de entidad financiera: claridad, confianza, eficiencia.

## Evidence on Hand

Datos de demostración generados por `rct:demo-reset` (cartera, propuestas, sustentos, lotes, pagos, devoluciones y alertas). No hay clientes, métricas ni testimonios reales que puedan citarse fuera del prototipo.

## Product Principles

1. La tarea primero: lo que el usuario debe decidir o hacer hoy se ve antes que cualquier adorno.
2. Cada cifra se lee de un vistazo y se puede rastrear hasta su expediente.
3. El estado siempre es explícito: aprobado, observado, rechazado, conforme, con diferencias.
4. Coherencia de pantalla a pantalla: el mismo componente significa lo mismo en todo el sistema.
5. Profesional y sobrio: la confianza se gana con orden y precisión, no con decoración.

## Accessibility & Inclusion

Contraste WCAG AA para texto, foco visible con teclado y lectura cómoda en laptops de 1366 px *(inferido: no se fijó una norma formal)*.
