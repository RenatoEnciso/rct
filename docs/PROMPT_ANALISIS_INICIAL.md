# Prompt: análisis inicial del proyecto RCT

> Copia todo el bloque de abajo y pégalo en el chat de Claude Code (modo **Plan**).
> No modifica nada: solo lee, mapea y entrega un informe.

---

```
Eres el desarrollador principal del proyecto RCT. El proyecto YA está avanzado;
no quiero que lo rehagas, quiero entender qué hay y qué falta.

CONTEXTO
- Lee primero: CLAUDE.md, docs/RCT_CONTEXTO_NEGOCIO.md y docs/REGLAS_DEMO_VALIDACION.md.
- Es un proyecto Laravel con estructura clásica, corriendo en Laragon (Windows).
- Es una versión tipo demo / mockup funcional para presentar a la Jefatura:
  debe verse completa y creíble, pero la lógica compleja puede ser básica o simulada.

PASO 1 — LEE TODO EL DIRECTORIO
Recorre el proyecto completo (excepto vendor/, node_modules/, storage/ y .env):
routes/, app/ (Controllers, Models, Requests, Services, Policies, Middleware, Jobs,
Notifications, Enums), database/ (migrations, seeders, factories), resources/views,
resources/js, resources/css, config/, composer.json, package.json, tests/.
Ejecuta si hace falta: php artisan route:list --except-vendor, php artisan about.
NO modifiques ningún archivo en este paso.

PASO 2 — ENTRÉGAME ESTE INFORME (en docs/ANALISIS_PROYECTO.md)

1. Mapa del proyecto
   Tabla: Módulo / funcionalidad | Archivos (controlador, modelo, vista, ruta) | Estado
   (Completo / Parcial / Solo vista / No existe).
   Cubre como mínimo: login y roles, campañas, base de aptos, expedientes,
   simulador de descuento, aprobaciones por nivel, carga de sustentos,
   validación de documentos, bitácora, notificaciones, dashboards/métricas,
   programas de gobierno, devoluciones a COFIDE, asistente IA, WhatsApp.

2. Modelo de datos actual
   Tablas existentes con sus relaciones; compáralas con la sección 11 del contexto
   y marca qué tablas o campos faltan.

3. Lo que falta (gap)
   Contra las secciones 4 a 8 y el roadmap (sección 13) del contexto.
   Ordénalo por fase y por impacto en la demo.

4. Lo que hay que mejorar
   Separado en: errores o bugs, seguridad (roles, validaciones, mass assignment,
   archivos subidos), reglas de negocio mal implementadas o fijas en código,
   calidad de código (controladores gordos, duplicación), UI/UX.
   Para cada punto: archivo y línea aproximada, problema, propuesta, esfuerzo
   (bajo / medio / alto).

5. Validación de documentos
   Dime si existe algo hoy y cómo se integraría el modo demo descrito en
   docs/REGLAS_DEMO_VALIDACION.md.

6. Plan propuesto
   Lista de tareas pequeñas (cada una se puede terminar y probar en una sesión),
   en orden recomendado, empezando por lo que más luce en una demo.

PASO 3
Actualiza las secciones [COMPLETAR] de CLAUDE.md con lo que encontraste.
Muéstrame el diff antes de guardarlo.

REGLAS
- Si algo no lo puedes determinar leyendo el código, dilo; no inventes.
- No ejecutes migraciones, seeders ni comandos que borren datos.
- Responde en español.
```
