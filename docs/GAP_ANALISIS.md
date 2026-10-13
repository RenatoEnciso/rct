# RCT — Análisis de brechas para una demo funcional completa

> Comparación del código actual (commits hasta `2eb0f48`, 11/10/2026) contra `docs/RCT_CONTEXTO_NEGOCIO.md`, secciones 4 a 9.
> Solo análisis: no se implementó nada.
> Leyenda de estado:
> - **Funciona**: flujo real de punta a punta.
> - **Parcial**: existe pero incompleto.
> - **Solo vista**: hay pantalla o texto, pero sin lógica ni datos.
> - **No existe**: no hay nada en el código.

---

## Resumen ejecutivo

Hoy RCT muestra bien el **circuito de una conciliación**: propuesta FUD → aprobación automática por autonomía → sustentos con validación automática → revisión → tableros. Para presentarlo como el sistema completo del contexto faltan cuatro bloques:

1. **Programas de gobierno más allá del checklist.** No existe nada de pagos posteriores al honramiento, devoluciones a COFIDE, cuadre ni alertas. Es la brecha más grande y la de mayor impacto, porque es el "riesgo central" del módulo 2 (contexto §6).
2. **Pago / ejecución en tienda y cierre del expediente.** El flujo salta de "Aprobado" a "Sustentos". No hay estado Pagado, registro de monto final ni cierre.
3. **Reporte mensual de conciliaciones exportable**, y reportes en Excel/PDF en general (hoy no hay ninguna exportación).
4. **Notificaciones.** No existe ninguna: ni en la app, ni por correo, ni resumen diario.

El asistente IA existe pero solo responde con 4 a 5 conteos del usuario. Falta configuración de campaña y la importación de la base de aptos, así como el simulador de descuento conectado al expediente.

---

## 1. Checklist por módulo

### 1.1 Módulo Campañas (contexto §4 y §5)

| Funcionalidad | Estado | Qué falta concretamente | Esfuerzo | Impacto demo |
|---|---|---|---|---|
| Configuración de campaña (nombre, vigencia, carteras, metas) | Parcial | Tabla `campanas` (`app/Models/Campana.php`) con vigencia, máximo de usos, días de fraccionado y UIT de referencia; 1 registro por seeder. **No hay pantalla** para crear o editar campañas, ni metas por zona/tienda, ni versión de reglas. | Medio | Medio |
| UIT como parámetro anual | Parcial | Columna `campanas.limite_sk_uit` existe pero el código usa el valor fijo `15450` (`ClienteCartera::esMayor3UIT()`, `fud/create.blade.php`). No hay tabla `ParametroAnual`. | Bajo | Bajo |
| Metas por zona y tienda | No existe | Tabla de metas + avance vs. meta en reportes. Hoy "cumplimiento" significa tasa de aprobación, no avance contra una meta. | Medio | **Alto** (la Jefatura pregunta "¿cómo vamos vs. la meta?") |
| Base de aptos mensual (importación) | No existe | `clientes_cartera` es una foto única cargada por `DemoSeeder`/`ClienteCarteraSeeder`. Falta la importación desde Excel/CSV, la columna mes/campaña, el historial y la inclusión manual con aprobación del Jefe. | Medio | **Alto** (es el punto de partida real del proceso) |
| Exclusiones automáticas | Parcial | `ClienteCartera::scopeSinExclusiones()` filtra pignoraticio, leasing, multigas, adquirida y fraude. **No excluye** `es_prog_gobierno`, depósito en garantía, administrativos ni venta de cartera. No hay pantalla que muestre por qué se excluye un crédito. | Bajo | Medio |
| Contador de usos 12 meses | Parcial | `veces_conciliado` es un contador acumulado (sube al aprobar en `BandejaController@decidir`), no una ventana móvil de 12 meses. La búsqueda deshabilita a quien tiene 4 o más usos. | Bajo | Bajo |
| Gestión de contacto (visitas, llamadas, promesas) | No existe | No hay modelo `Gestion`. Paso 1 del flujo del expediente (§4.3). | Medio | Medio |
| **Simulador de descuento** (máximo, monto a pagar, quién aprueba) | Parcial / Solo vista | Hay una calculadora JS en `lineamientos.blade.php` (`calcularBeneficio()`) con reglas fijas y aproximadas, **desconectada** del cliente. En "Nueva propuesta" el lineamiento sale de un texto de la cartera (`lineamiento_campana`). Falta un simulador que, con el crédito seleccionado, calcule descuento máximo, monto a pagar en soles y aprobador, y que advierta si la propuesta supera el tope. | Medio | **Alto** |
| Motor de reglas R01–R18 | Parcial | Implementado solo: R04 aproximado (4 usos), R18 (el solicitante no es aprobador, en `AsignadorAprobador`) y la escala por 3 UIT o cartera judicial. Faltan R05–R17 como reglas validadas antes de enviar (topes %, 50% de la cuota, alineamiento interno, SUNARP ≤ 6 meses, fraccionado ≤ 90 días, castigada +10 pts). Se acordó dejarlo para después de la demo. | Alto | Medio |
| Expediente (solicitud con justificación) | Funciona | `FudController@store` con detalle por tipo, adjuntos y versiones por corrección (`parent_fud_id`). Estados reales: En revisión, Aprobado, Observado, Rechazado (y "Corregido" visible). | — | — |
| Estados completos del expediente | Parcial | Faltan Pre-validado, **Pagado**, Sustento cargado/validado como estado del expediente, **Cerrado**, Vencido (aprobación no ejecutada en X días) y Anulado. Hoy el estado del sustento vive aparte (`sustentos.estado`). | Medio | **Alto** (sin "Pagado/Cerrado" el expediente no termina) |
| Aprobación automática (ruta por autonomía) | Funciona (simple) | `app/Services/AsignadorAprobador.php`: nivel = máximo(autonomía de la cartera, regla SK > 3 UIT o judicial), misma zona, menor carga. Falta el escalamiento N1→N3→N4→N5, la delegación temporal y el vencimiento por tiempo sin atender. | Medio | Bajo |
| Doble aprobación / segregación | Funciona | Valida el estado en `BandejaController@decidir`; la corrección vuelve al aprobador que observó. | — | — |
| Registro del acuerdo en el core y monto final | No existe | Paso 6 de §4.3: Recuperaciones registra el acuerdo y RCT guarda el monto final. No hay campo ni pantalla. | Bajo | Medio |
| **Pago / ejecución en tienda (Operaciones)** | No existe | No hay rol Operaciones, pantalla "Aprobaciones listas para cobrar", registro de pago (fecha, monto, tienda de pago, N° de Nota de Abono) ni pago en otra tienda. El flujo pasa de "Aprobado" directo a cargar sustentos. | Medio | **Alto** |
| Sustentos (carga el mismo día) | Funciona | `SustentosController` con validación en servidor, historial de reemplazos y documentos requeridos según cartera, transacción 300/140 y SK > 3 UIT. Falta el rol "Administrador de tienda/Operaciones" como cargador: hoy carga el ejecutivo que envió el FUD. | — | — |
| **Validación demo de documentos** | Funciona | `app/Services/Validacion/*`, `config/rct.php`, `public/assets/js/rct.js`. Marcadores, duplicado por hash, nombre limpio, "Conforme manual". Falta solo la regla de "firmas distintas entre documentos del mismo cliente" como cruce real (se simula con `_fd`). | — | — |
| Revisión (CZR valida su zona; revisores auditan) | Parcial | `RevisionSustentosController` funciona, pero cualquier aprobador ve todas las zonas; no hay filtro por zona del CZR ni rol Revisor. | Bajo | Medio |
| Pagos fraccionados (cronograma y alertas) | No existe | No hay modelo `CuotaFraccionada`; `fud_detalle_cancelacion.fecha_limite` es una fecha suelta. | Medio | Bajo |
| **Reporte mensual de conciliaciones** (2 primeros días hábiles) | No existe | `revision/dashboard.blade.php` ("Reporte de conciliaciones") es un tablero de estados de sustento, **no** el reporte mensual: falta el listado del mes con cliente, operación, tipo, montos condonados por concepto (interés, moratorio, capital), autorizador, tienda y estado de sustento, exportable a Excel/PDF. | Medio | **Alto** |
| Cierre del expediente (R17: sustento completo) | No existe | No hay acción de cierre ni bloqueo de cierre sin sustento. | Bajo | Medio |

### 1.2 Módulo Programas de gobierno (contexto §6)

| Funcionalidad | Estado | Qué falta concretamente | Esfuerzo | Impacto demo |
|---|---|---|---|---|
| Expediente de **honramiento** | Parcial (otro propósito) | `ExpedienteGobierno` hoy modela la **habilitación de un crédito con garantía rechazada para la campaña** (motivo: garantía rechazada / pérdida de eficacia). No tiene lo de §6.1: % de cobertura, saldo a la solicitud, monto solicitado, **monto honrado**, fecha de honramiento ni estados (en preparación, enviado, observado, honrado, rechazado, pérdida de eficacia). | Medio | **Alto** |
| Checklist configurable por programa | Parcial | Checklist fijo de 10 documentos en `ExpedienteGobierno::CHECKLIST` (igual para todos los programas). Falta configurarlo por programa (tabla). | Bajo | Bajo |
| Avance por asesor/tienda (completo, incompleto, observado) | Parcial | Barra de avance por expediente (`cartera/exclusiones.blade.php`) y por programa (`ReportesController::resumenGobierno`). **Falta el corte por asesor y por tienda** (tabla "avance de mi tienda / ranking de asesores"). | Bajo | **Alto** (es lo que se revisa a diario) |
| Validación de documentos | Funciona | Mismo validador con `_sld` y `_inc`; ficha `cartera/gobierno-show.blade.php` con carga/reemplazo y conformidad manual. | — | — |
| Notificación al subir o cuando faltan documentos | No existe | Ver notificaciones. | Medio | Medio |
| **Reporte de pagos posteriores al honramiento** | No existe | No hay modelo `PagoPosterior` ni fuente de pagos del core. Para la demo bastaría una tabla de pagos simulados (seeder) por operación honrada y una pantalla "Pagos detectados en operaciones honradas". | Medio | **Alto** |
| **Cálculo de devoluciones a COFIDE** (qué operación, cuánto, cuándo) | No existe | No hay `DevolucionCofide`. Falta: % de cobertura por programa (parámetro), monto = recuperado neto × % cobertura, fecha límite según plazo del programa (parámetro), estados (pendiente, aprobada, transferida, conciliada), responsable y constancia. Los 5 escenarios de §6.2 (pago antes del envío, enviado no honrado, parcial post-honramiento, cancelación post-honramiento, pérdida de eficacia) no están modelados. | Medio-alto | **Alto** (es el "riesgo central" del módulo) |
| Cuadre automático (diario y mensual con COFIDE) | No existe | Comparar pagos posteriores contra devoluciones registradas: por devolver, devuelto, diferencias, antigüedad. | Medio | Medio |
| Alertas de pago (operación honrada, devolución por vencer o vencida) | No existe | Depende de pagos y devoluciones + notificaciones. | Medio | **Alto** |
| Tendencia de pagos cerca del cierre de mes | No existe | Gráfico de distribución de pagos por día del mes (§6.4). Con pagos simulados es un solo gráfico. | Bajo (si existen pagos) | Medio |
| Proyección de devoluciones 30/60/90 días | No existe | Suma de devoluciones pendientes por fecha límite. | Bajo (si existen devoluciones) | Medio |
| Probabilidad de pago post-honramiento | No existe | Analítica (fase 6). Para la demo puede ser una tasa histórica simple por programa y tienda. | Medio | Bajo |

### 1.3 Notificaciones (contexto §8.1 y §8.2)

| Funcionalidad | Estado | Qué falta concretamente | Esfuerzo | Impacto demo |
|---|---|---|---|---|
| Notificaciones en la app (campana con contador) | Parcial | Solo contadores en el menú lateral: bandeja y sustentos (`layouts/app.blade.php`). No hay tabla `notifications`, campana, lista, "marcar como leído" ni los eventos de §8.1 (observado, aprobado, rechazado, lista para cobrar, sustento faltante, etc.). | Bajo-medio (Laravel trae el canal `database`) | **Alto** |
| Notificaciones por correo | No existe | `MAIL_MAILER=log` en `.env`; no hay clases `Notification`/`Mailable`. Para la demo basta el canal `mail` con Mailpit o el log, y plantillas institucionales. | Bajo | Medio |
| Solicitud sin atender > X horas (escalamiento) | No existe | Requiere tarea programada (`routes/console.php` no tiene schedule) y cola. | Medio | Bajo |
| Resumen diario por rol | No existe | Ni comando programado ni vista. Para la demo, una pantalla "Mi resumen del día" por rol (asesor, administrador, CZR, Jefe), con opción de enviarla por correo, mostraría el concepto sin esperar al cron. | Bajo-medio | **Alto** |

### 1.4 Dashboards y métricas (contexto §8.3)

| Métrica del contexto | Estado | Dónde / qué falta |
|---|---|---|
| Monto recuperado (capital, intereses) | No existe | No hay registro de pago. Hoy se muestra "capital normalizado", que es el saldo capital de los FUD con sustento conforme (`ReportesController`), una aproximación. |
| Clientes normalizados | Parcial | Se puede derivar de los FUD aprobados y conformes; no se muestra como tal. |
| Reducción de mora y provisiones | No existe | Requiere saldo en mora antes/después y tasas de provisión por calificación (parámetro). Es el **objetivo n.º 1 de la campaña** (§9: simulador de provisiones). |
| Ingresos percibidos | No existe | Requiere pagos (interés cobrado). |
| Condonado por concepto, % condonado/recuperado, recuperado por sol condonado | No existe | Faltan montos condonados en soles: hoy los detalles guardan porcentajes (`fud_detalle_*`), no montos. |
| Recaída 30/60/90 | No existe | Requiere historial de atraso posterior. |
| Uso de excepciones | No existe | No se registran excepciones como tales. |
| Cumplimiento de fraccionados | No existe | No hay fraccionados. |
| Tiempo de aprobación por nivel | Parcial | Hay `fecha_envio` y `fecha_decision` (fechas sin hora); no se muestra. Cálculo simple. |
| Observados por motivo | Parcial | Los comentarios son texto libre; no hay catálogo de motivos. Sí hay hallazgos de validación con código (se podría graficar por código). |
| Sustentos a tiempo / % documentos conformes al primer intento | Parcial | Datos disponibles (`sustento_documentos.estado_validacion`, `vigente`, `cargado_en`); no se muestra el indicador. |
| Programas: % expedientes completos por asesor/tienda, honrado, por devolver, devuelto a tiempo, vencido | Parcial | Solo completos por programa. El resto depende de honramiento y COFIDE. |
| Cortes: zona, tienda, asesor, tipo de crédito, calificación, cartera, tramo, campaña | Parcial | Reporte de campaña filtra por zona, tienda y estado. Indicadores cortan por tipo y por cartera. Falta corte por asesor, calificación y tramo. |

**Tableros existentes:**
- `dashboard/index` (por rol).
- `reportes/dashboard` (Reporte de campaña).
- `indicadores/index`.
- `revision/dashboard` (sustentos).

Todos con datos reales (punto 3).

### 1.5 Reportes exportables (Excel / PDF)

| Reporte | Estado | Comentario |
|---|---|---|
| Cualquier exportación Excel/CSV | No existe | No hay paquete ni ruta de exportación. |
| PDF | Parcial | Solo "Imprimir" del navegador en `reportes/dashboard` y `revision/dashboard`, con estilos `@media print` en `rct.css`. |
| Reporte mensual de conciliaciones (Excel) | No existe | Es el reporte que hoy se arma a mano (§1). **Imprescindible.** |
| Listado de FUD / bandeja / sustentos con filtros → Excel | No existe | Bajo esfuerzo una vez instalado el paquete. |
| Devoluciones COFIDE (Excel para Tesorería/Contabilidad) | No existe | Depende del módulo COFIDE. |
| FUD imprimible (PDF del formato) y Acuerdo de Pago prellenado | No existe | §9 "Plantillas oficiales generadas por RCT". Muy vistoso en demo. |
| Exportes para Contabilidad y SBS | No existe | Después de la demo. |

### 1.6 Asistente IA (contexto §7.1)

| Capacidad | Estado | Detalle |
|---|---|---|
| Chat de texto | Funciona | `AsistenteController@chat` con Gemini (`env('GEMINI_API_KEY')`, modelo `gemini-3.6-flash`), widget en `layouts/app.blade.php`. |
| Voz (dictado y lectura) | Funciona (navegador) | Web Speech API del navegador (`toggleMic`, `hablar`); funciona en Chrome/Edge, no en Firefox. |
| Consultas sobre la BD | Parcial | El contexto enviado son 4–5 conteos del usuario (`construirContexto`). No puede responder "¿cuánto le puedo descontar al crédito X?", "¿qué me falta para cerrar mis expedientes?" ni "¿cuánto devolvemos a COFIDE esta semana?". Falta uso de herramientas (*function calling*) con consultas seguras y parametrizadas: buscar crédito, simular descuento, listar mis pendientes, resumen de zona. |
| Resúmenes (expediente para el aprobador, día por rol, campaña para gerencia) | No existe | Botón "Resumir con IA" en la ventana de la bandeja y en el reporte. Bajo esfuerzo con el contexto ya armado. |
| Registro asistido por dictado → borrador | No existe | Dictado que completa el formulario FUD como borrador para confirmar. |
| Redacción del Informe-Propuesta / sustento de proporcionalidad | No existe | Muy vistoso: botón "Redactar sustento" en Nueva propuesta. |
| Respuestas sobre lineamientos citando la sección | No existe | Incluir `RCT_CONTEXTO_NEGOCIO.md` (o un resumen) en el contexto del modelo. |
| Robustez | Riesgo | Si la API falla, devuelve al navegador el cuerpo crudo del error y lo registra completo en el log. `env()` fuera de config falla con `config:cache`. **Para la demo: tener una respuesta de respaldo y probar la clave el día anterior.** |

### 1.7 WhatsApp (contexto §7.2)

| Funcionalidad | Estado | Qué haría falta |
|---|---|---|
| Todo el canal | No existe | (1) Cuenta de WhatsApp Business Platform (Meta Cloud API) o proveedor (Twilio, 360dialog); número verificado y plantillas aprobadas por Meta, que **toma días**. (2) Webhook en Laravel (`POST /webhooks/whatsapp`) con verificación de firma. (3) Vincular el número al usuario (`users.whatsapp` verificado con código). (4) Comandos: estado de expediente, envío de foto o PDF de sustento → asociar al expediente → mismo `CargaDocumentos` → responder conforme u observado. (5) Resumen diario por plantilla. (6) Aprobación rápida con PIN para montos bajos. |
| Para la demo | — | **Simularlo**: una pantalla "Vista WhatsApp" (maqueta de conversación) que use los mismos servicios (consulta de estado, carga de sustento con validación). Muestra el concepto sin depender de Meta. |

### 1.8 Bitácora / trazabilidad (contexto §10)

| Funcionalidad | Estado | Detalle |
|---|---|---|
| Registro de acciones | Funciona (básico) | `Seguimiento::registrar()` en FUD, decisión, sustentos y gobierno; pantalla `seguimiento/index` con filtros. |
| Valores antes/después, IP, canal | No existe | La tabla `seguimiento` no tiene antes/después ni IP. |
| Inmutabilidad | No existe | Se puede editar o borrar en la BD; no hay hash encadenado. |
| Historial del expediente | Funciona | Línea de tiempo de versiones en `fud/show.blade.php`; historial de documentos reemplazados en `revision/show.blade.php`. |
| Documentos con hash y versionados | Funciona | `sustento_documentos.hash/vigente`, `expediente_gobierno_docs.hash/vigente`. No están cifrados. |
| Seguridad de acceso | Riesgo conocido | Fuera del alcance acordado: `/register` abierto, aprobador puede decidir FUD ajenos (`BandejaController@decidir` no valida `aprobador_id`), `fud.show` sin *policy*. **En la demo no mostrar `/register`.** |

---

## 2. Flujos de punta a punta

### 2.1 Pago de cuotas

| Paso (§2.9 / §4.3) | Hoy | Dónde se corta |
|---|---|---|
| 1–4 Contacto y verificación en base | Parcial | No hay registro de gestión de contacto; la "verificación" es buscar en la cartera (`fud/create`). |
| Simulación | **Falta** | No se calcula el descuento máximo ni el monto a pagar en soles; el ejecutivo escribe porcentajes libres. |
| 5 Propuesta FUD | Funciona | — |
| 6 Aprobación | Funciona | — |
| 7 Aviso a Operaciones / tienda de pago | **Falta** | Sin notificación ni pantalla de Operaciones. |
| 8–10 Acuerdo de Pago, cobro, Nota de Abono | **Falta** | No hay registro de pago ni generación del Acuerdo. **Aquí se corta el flujo.** |
| 11 Carga de sustentos | Funciona | Con validación automática. |
| 12 Validación del CZR | Funciona | Sin restricción por zona. |
| 13 Reporte mensual | **Falta** | No existe el reporte exportable. |
| Regla del 50% de la cuota y "cierra el mes vigente" | Solo vista | Solo avisos de texto; no se valida. |

### 2.2 Cancelación total

Igual que pago de cuotas en pasos 5, 6, 11 y 12. Diferencias:
- **Traslado al Módulo de Recuperaciones del core:** no existe (podría ser un check o estado).
- **SK > 3 UIT:** el aprobador se escala bien a Jefe. El **Informe-Propuesta** se exige en sustentos, pero la **búsqueda registral (SUNARP ≤ 6 meses)** no tiene modelo: solo un select "Positiva/Negativa" en el FUD; la vigencia se simula con `_vnc`.
- **Pago fraccionado:** sin cronograma ni control de 90 días.
- **Se corta en:** pago/ejecución, monto final del core y cierre.

### 2.3 Judicial / castigada / FOCMAC

- FUD con "Condiciones · cartera especial" (gravamen, etapa procesal, % SK): **funciona**.
- **Falta:**
  - el caso judicial (§9: gravamen, embargo, base y fecha de remate, consignaciones) como entidad;
  - la regla "1er rango requiere Jefe";
  - el aprobador **Coordinador de Normalización** para castigada/FOCMAC: el rol no existe y hoy se asigna al Jefe.
- Sustentos sin Acuerdo de Pago para castigada: **funciona**.
- **Se corta en:** pago y cierre, igual que los anteriores.

### 2.4 Programa de gobierno con devolución a COFIDE

| Paso | Hoy | Dónde se corta |
|---|---|---|
| Expediente de honramiento con documentos | Parcial | Existe el expediente con checklist y validación, pero modelado como "habilitación"; sin % de cobertura, monto honrado ni estados de honramiento. |
| Avance por asesor/tienda | Parcial | Sin corte por asesor ni tienda. |
| Envío / honramiento por COFIDE | **Falta** | No hay estados ni fecha de honramiento. |
| Pago posterior del cliente | **Falta** | No hay pagos. **Aquí se corta todo el módulo.** |
| Alerta "pago en operación honrada" | **Falta** | — |
| Cálculo de devolución (cuánto, de qué operación, para cuándo) | **Falta** | — |
| Aprobación, transferencia y constancia | **Falta** | — |
| Cuadre diario y mensual con COFIDE | **Falta** | — |
| Analítica (pagos cerca del cierre, proyección 30/60/90) | **Falta** | — |

---

## 3. Recomendaciones técnicas

Stack actual: Laravel 13, Breeze, Blade + Bootstrap 5 (CDN) con CSS propio (`public/assets/css/rct.css`), Tailwind solo para `/register`, Chart.js (CDN) en un tablero, MySQL 8.4, colas `database` sin uso.

| Tecnología | Para qué serviría aquí | ¿Demo o después? | Riesgo |
|---|---|---|---|
| **Maatwebsite/Laravel-Excel** (o **spatie/simple-excel**, más liviano) | Reporte mensual de conciliaciones, listados de FUD/sustentos y devoluciones COFIDE en Excel; también **importar la base de aptos**. | **Demo** | Bajo. Laravel-Excel depende de PhpSpreadsheet (pesado pero estable); simple-excel basta para exportar e importar tablas planas. |
| **barryvdh/laravel-dompdf** | PDF del FUD, Acuerdo de Pago prellenado, reporte mensual en PDF y constancia de devolución. | **Demo** | Bajo. Soporte CSS limitado (flex/grid parcial): plantillas con tablas simples. |
| **Chart.js** (ya usado) | Extender a reporte de campaña, indicadores y pagos por día del mes. Pasar de CDN a archivo local. | **Demo** | Bajo. Hoy depende de cdnjs: si la sala de la demo no tiene internet, los gráficos no cargan (igual Bootstrap, Bootstrap Icons y Google Fonts). **Recomendado: copiar estos recursos a `public/vendor`.** |
| Tablas con filtros | Ya existe un mejorador propio (búsqueda + paginación) en `rct.js` y paginación de servidor. DataTables/Tabulator agregaría orden por columna y exportación rápida. | Después | Medio: otra dependencia de jQuery (DataTables) o de estilos; lo actual es suficiente. |
| **Notificaciones Laravel (canal `database` + `mail`)** | Campana en la app y correos de los eventos de §8.1, sin paquetes extra. | **Demo** | Bajo. Usar Mailpit (incluido en Laragon) o `MAIL_MAILER=log` para no enviar correos reales. |
| Tiempo real (Laravel **Reverb** + Echo, o *polling*) | Que la bandeja o la campana se actualicen solas cuando llega un FUD. | Demo: *polling* cada 30 s. Reverb después. | Reverb añade un proceso websocket y configuración; el *polling* basta para demostrar. |
| **Colas** (`database`, ya configurado) + **Scheduler** | Enviar correos, resumen diario, cuadre COFIDE, alertas de vencimiento, escalamiento por tiempo. | Después (en la demo se puede disparar a mano con un botón o comando) | Bajo, pero exige `queue:work` y una tarea programada en el servidor. |
| **spatie/laravel-permission** | Roles múltiples por usuario, ámbito y permisos finos (Operaciones, Revisor, Auditoría, Coordinador de Normalización, Admin). | Después | Medio: migrar de `users.rol_id` + middleware `grupo` toca menú, rutas y controladores. Para la demo basta agregar roles a la tabla actual. |
| **Policies de Laravel** (nativo) | Cerrar los accesos conocidos (decidir solo lo propio, ver solo FUD propios o de la zona). | Después (prioridad alta antes de usuarios reales) | Bajo. |
| **spatie/laravel-activitylog** (u **owen-it/laravel-auditing**) | Bitácora con antes/después, usuario e IP automáticos por modelo (§10). | Después (podría entrar en la demo si se quiere mostrar auditoría) | Bajo. Convive con `seguimiento`. |
| **Livewire 3** | Componentes interactivos sin escribir JS: simulador en vivo, filtros reactivos, bandeja que se refresca. | Después | Medio: mezclar Livewire con el JS actual (`rct.js`) requiere cuidado. **Alpine.js** ya está en `package.json`; sirve para interacciones pequeñas sin cambiar la arquitectura. |
| **API de IA** (Gemini actual o Claude, con *function calling*) | Consultas reales sobre la BD (herramientas: buscar crédito, simular, pendientes, devoluciones), resúmenes y redacción del sustento. Mover la clave a `config/services.php`. | **Demo** (resúmenes y 2–3 herramientas) | Medio: latencia, costo y clave. Datos personales (Ley 29733): enviar datos mínimos y anonimizar DNI. Tener respuesta de respaldo si la API falla. |
| **WhatsApp Business API** (Meta Cloud API / Twilio) | Consultas, carga de sustentos y resumen diario por WhatsApp. | Después; en la demo, una **maqueta** que use los servicios reales | Alto: aprobación de Meta, plantillas, número dedicado, costos por conversación, webhook público (HTTPS). |
| **OCR / IA con visión** (Google Document AI, AWS Textract, Azure Document Intelligence, o un modelo multimodal) | `ValidadorReal`: extraer montos, DNI y N° de crédito; detectar firmas presentes. | Después | Medio-alto: precisión en documentos escaneados, costo por página y privacidad. La **verificación de igualdad de firmas** sigue siendo de alto riesgo (lo dice `REGLAS_DEMO_VALIDACION.md`). El contrato `ValidadorDocumentos` ya permite cambiarlo sin tocar la interfaz. |

---

## 4. Extras que sumarían en la presentación (poco esfuerzo, mucho valor)

1. **Simulador en la propuesta.** Al elegir el cliente, mostrar "Descuento máximo permitido", "Monto a pagar estimado" y "Aprueba: X" con advertencia si el porcentaje ingresado supera el tope. Reutiliza la lógica de `calcularBeneficio()` de `lineamientos.blade.php` y los datos que ya devuelve `/api/clientes`. Es el momento más "inteligente" de la demo.
2. **Botón "Resumir con IA"** en la ventana de revisión de la bandeja (el contexto ya está cargado en `BandejaController@index`) y **"Redactar sustento con IA"** en Nueva propuesta.
3. **Campana de notificaciones** con los eventos que ya ocurren (observado, aprobado, sustento observado). Laravel ya trae el canal `database`; solo faltan migración y vista.
4. **"Mi día" por rol.** Una tarjeta en el panel con lo que el usuario debe hacer hoy (pendientes, sustentos observados, expedientes de gobierno incompletos de su tienda). Concreta el "resumen diario" de §8.2 sin cron.
5. **Exportar a Excel** en Reporte de campaña, Revisión y Mis propuestas, y el **reporte mensual de conciliaciones** con un clic ("lo que hoy toma 2 días hábiles").
6. **PDF del FUD y del Acuerdo de Pago prellenado** desde el detalle (§9, plantillas oficiales).
7. **Tiempo promedio de aprobación por nivel.** Ya hay `fecha_envio`/`fecha_decision`; agregar la hora (`decidido_en`) da un KPI de eficiencia muy valorado por la Jefatura.
8. **Indicador de "documentos conformes al primer intento"** con los datos de validación que ya se guardan (`sustento_documentos.estado_validacion` + `vigente`).
9. **Mapa de calor zona × tipo de negociación** (tabla con colores) en el reporte: datos ya disponibles.
10. **Recursos locales** (Bootstrap, Icons, Chart.js, fuente Inter en `public/vendor`) para que la demo no dependa de internet.
11. **Botón "Restablecer datos de demo"** (comando `php artisan rct:demo-reset`) para repetir la presentación desde cero y quitar los 7 FUD de prueba con textos sin sentido que siguen en la BD.

---

## 5. Plan sugerido

### Imprescindible para la demo (impacto alto → esfuerzo bajo primero)

1. **Limpiar datos y blindar la presentación** (bajo): reset con `migrate:fresh --seed` o comando de reset; recursos CDN locales; respaldo del asistente si falla la API; no mostrar `/register`.
2. **Exportación Excel + reporte mensual de conciliaciones** (bajo-medio): instalar `spatie/simple-excel` o Laravel-Excel; botón en Reporte de campaña, Revisión y Mis propuestas; nueva vista "Reporte mensual" por mes con montos.
3. **Simulador de descuento en Nueva propuesta** (medio): tope por tabla del Anexo I simplificada, monto a pagar en soles, aprobador y advertencia. Guardar montos condonados en soles (habilita las métricas de §8.3).
4. **Pago / ejecución en tienda y cierre** (medio): rol Operaciones (o acción del Administrador), pantalla "Listas para cobrar", registro de pago (monto final, fecha, tienda, N° Nota de Abono), estados Pagado y Cerrado; seeder con pagos. Cierra los flujos 2.1 a 2.3.
5. **Notificaciones en la app + correo (log/Mailpit)** (bajo-medio): campana con los eventos de §8.1 que ya existen en el código.
6. **Programas de gobierno – honramiento, pagos posteriores y devoluciones COFIDE** (medio-alto, el más largo, pero el de mayor valor del módulo 2):
   1. campos de honramiento en `expedientes_gobierno` (cobertura, monto honrado, fecha, estado);
   2. tabla `pagos_posteriores` con datos simulados;
   3. tabla `devoluciones_cofide` con cálculo `recuperado neto × % cobertura` y fecha límite por parámetro de programa;
   4. pantalla "Devoluciones COFIDE" (pendientes, por vencer, vencidas, constancia);
   5. alertas en la campana;
   6. gráfico de pagos por día del mes y proyección 30/60/90.
7. **Avance de programas por asesor y tienda** (bajo): tabla y ranking en Reporte de campaña y en la página de Programas de gobierno.
8. **Asistente con resúmenes y 2–3 consultas reales** (medio): "Resumir expediente", "¿qué me falta?", "¿cuánto devolvemos a COFIDE esta semana?" (tras el punto 6).
9. **"Mi día" por rol** (bajo): resumen diario en el panel, con opción "Enviar por correo".

### Después de la demo

10. Motor de reglas R01–R18 como datos versionados por campaña; resolver los puntos ambiguos de la sección 12.
11. Configuración de campaña (pantalla), metas por zona/tienda y UIT por año.
12. Importación mensual de la base de aptos (Excel) con historial, contador de 12 meses real e inclusión manual con aprobación.
13. Roles reales (`spatie/laravel-permission`): Operaciones, Revisor, Auditoría, Coordinador de Normalización, Administrador; ámbito por zona; delegación temporal.
14. Policies y seguridad: cerrar `/register`, validar aprobador en `decidir`, *policies* en FUD y sustentos, 2FA para aprobadores.
15. Auditoría con antes/después (`laravel-activitylog`) y bitácora inmutable.
16. Escalamiento por tiempo, vencimiento de aprobaciones, resumen diario por correo con cola y scheduler.
17. Pagos fraccionados con cronograma y alertas; caso judicial (gravamen, remate, consignaciones); control de búsquedas SUNARP con vigencia.
18. `ValidadorReal` con OCR / IA con visión.
19. WhatsApp Business API.
20. Analítica avanzada (§6.4, §9): probabilidad de pago, recaída, anomalías, simulador de provisiones con tasas SBS.
21. Integración con el core (lectura de saldos y pagos) y tests automatizados (hoy 24 de 25 fallan por la migración `ALTER ... MODIFY` en SQLite).

---

### Lo que no se pudo determinar leyendo el código

- **Plazos de devolución a COFIDE, tratamiento de intereses y orden de imputación por programa:** el contexto (§6.2) indica que salen de cada reglamento; no están en el repositorio.
- **Porcentajes de cobertura reales por programa** (Reactiva, FAE-MYPE, Impulsa Perú): no hay datos.
- **Si la sala de la demo tendrá internet:** afecta CDN, Google Fonts y el asistente Gemini.
- **Si el modelo `gemini-3.6-flash` y la clave actual responden:** no se probó la llamada externa en este análisis.
- **Formato oficial del FUD y del Acuerdo de Pago:** no hay plantillas en `docs/` para generarlos en PDF.

---

## 6. Estado al 12/10/2026 y pendientes en pausa

> Se pausa este plan para empezar la **analítica avanzada y la vitrina comercial** (`docs/PROMPT_ANALITICA_DEMO.md`). Retomar desde aquí.

### Avance del plan sugerido (sección 5)

| # | Punto | Estado |
| --- | --- | --- |
| 1 | Limpiar datos y blindar la presentación | **Parcial.** Falta el comando de reset y pasar los recursos CDN a locales (se harán dentro de la analítica). Siguen pendientes el respaldo del asistente y ocultar `/register`. |
| 2 | Exportación Excel + reporte mensual de conciliaciones | **Parcial.** Hay exportación Excel en Programas de gobierno y PDF en "Mis aprobaciones". Falta el reporte mensual de conciliaciones. |
| 3 | Simulador de descuento en Nueva propuesta (montos condonados en soles) | **Pendiente.** |
| 4 | Pago / ejecución en tienda y cierre | **Pendiente.** |
| 5 | Notificaciones en la app | **Hecho:** campana, avisos al ingresar y recordatorios. El correo sigue pendiente. |
| 6 | Programas de gobierno | **Parcial.** El honramiento está hecho (modelo, importación y exportación del Excel del área, bandeja, ficha, revisión por zona y central, indicadores). Faltan pagos posteriores, devoluciones a COFIDE, cuadre y proyección 30/60/90. |
| 7 | Avance de programas por asesor y tienda | **Hecho** en Indicadores de honramiento (por zona, lote y tienda). |
| 8 | Asistente con resúmenes | **Pendiente.** |
| 9 | "Mi día" por rol | **Parcial.** La página Inicio tipo portal tiene tareas por rol; falta el envío por correo. |

### Pendientes abiertos del módulo de honramiento

- Validar el rotulado de los PDF según el **Anexo 13** (falta su estructura).
- Crear el rol **Analista / Auxiliar de Recuperaciones** para la revisión central; hoy la hace el Jefe de Recuperaciones.
- Decidir si se ejecuta `migrate:fresh --seed` para regenerar la demo desde cero. Es destructivo y queda pendiente de confirmación.
- El registro de importación que crea el seeder muestra 48 "con diferencias", calculadas con la lógica anterior. Se corrige al regenerar los datos.
- Revisar los avisos de seguridad de `league/commonmark` (composer audit).
