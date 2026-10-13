# Proceso real — Programa IMPULSO MYPERÚ (honramiento y devolución a COFIDE)

> Documento de contexto para el módulo **Programas de Gobierno** de RCT.
> Describe el proceso tal como lo ejecuta hoy el área y cómo lo automatiza RCT.
> **Prevalece sobre la sección 6 de `RCT_CONTEXTO_NEGOCIO.md`** y sobre cualquier
> implementación previa de programas de gobierno o COFIDE.
> Al final (sección 7) está el prompt completo para Claude Code.

---

## 1. Visión general

```
CORE FINANCIERO (actualiza cada 30 min)
   │  créditos, días de atraso, saldos, PAGOS
   ▼
[1] SOLICITUD (todo el mes) ──► crédito llega a 90 días ──► Lote ──► Plataforma COFIDE
   │
   ▼
[2] HONRAMIENTO (mes siguiente) ──► Reporte COFIDE: saldo, interés, cobertura, monto a honrar
   │                                   └─► Aviso a agencias por zona
   ▼
[3] REVISIÓN DE EXPEDIENTES (Anexo 13) ──► PENDIENTE / INCOMPLETO / INCOMPLETO DEFINITIVO / COMPLETO
   │
   ▼
[4] BASE MATRIZ ◄── fuente única de verdad de cada crédito honrado
   ▲
   │
[5] PAGOS — SINCRONIZACIÓN AUTOMÁTICA desde el core (cada 30 min)
   │   Reversión = capital × cobertura (solo si está honrado) ──► AGREGADO
   │   └─► Notificación en pantalla + campana + correo
   ▼
[6] DEVOLUCIÓN A COFIDE (inicio del mes siguiente)
       Cruce con notificación TXT de COFIDE ──► debe cuadrar al 100%: diferencia = S/ 0,00
```

---

## 2. Integración con el core (principio general)

- El core financiero es la **fuente de verdad** de créditos, saldos, días de atraso y pagos, y se actualiza **cada 30 minutos**.
- RCT **no pide registrar a mano** nada que ya esté en el core.
- Para la demo, RCT usa **tablas espejo desnormalizadas** (`core_creditos`, `core_pagos`) que simulan lo que entrega el core. Se llenan con seeders y con un comando de simulación.
- La lectura del core va detrás de una **interfaz** (`FuenteCore`). Hoy la implementa `FuenteCoreDemo` (tablas espejo); mañana, `FuenteCoreReal` (vista de base de datos, API o archivo del core), sin cambiar pantallas ni cálculos.
- Toda sincronización es **idempotente**: un mismo pago (`id_core`) nunca se procesa dos veces.
- Se guarda la **fecha y hora de la última sincronización**, visible en pantalla.

---

## 3. Detalle por etapa

### Etapa 1 — Solicitud de honramiento (durante todo el mes)

- Con cada sincronización, RCT detecta los créditos del programa que **llegan a 90 días de atraso**: son candidatos a honramiento. Se notifica al responsable.
- Por cada **corte** se arma un lote y RCT genera:
  - la **notificación** (documento Word);
  - la **plantilla de honramiento** (Excel), con el formato de COFIDE.
- Se registra la autorización de los **representantes ante COFIDE** para usar su **firma digital**.
- Se carga en la plataforma de COFIDE, que confirma el **N° de registros** y el **saldo insoluto total**.
- **Cada carga es un lote.**

Datos del lote: N° de lote, fecha de corte, fecha de carga, créditos incluidos, N° de registros, saldo insoluto total (calculado por RCT y confirmado por COFIDE), estado de la firma y estado del lote.

Estados del lote: `En preparación` → `Pendiente de firma` → `Firmado` → `Cargado en COFIDE` → `Honrado` (parcial o total) / `Observado`.

Validación: el N° de registros y el saldo que confirma COFIDE **deben coincidir** con lo calculado por RCT. Si no coinciden, se marca la diferencia.

### Etapa 2 — Honramiento (mes siguiente)

- Se descarga de COFIDE el **reporte detallado** por crédito: saldo insoluto, interés devengado, **factor de cobertura** y **monto a honrar**.
- Se importa a RCT y cada crédito del lote pasa a **Honrado**, con su fecha de honramiento.
- Se avisa a las **agencias por zona** (pantalla, campana y correo): créditos pendientes de documentación y monto a honrar, con el **formato de nombres de archivo** establecido.

### Etapa 3 — Revisión de expedientes (dashboard)

- Se piden todos los documentos, pero **solo se evalúan los exigidos por COFIDE (Anexo 13)**:
  - **Persona natural:** 6 documentos obligatorios.
  - **Persona jurídica:** 7 documentos obligatorios.
- Documentos que se piden pero **NO afectan el estado** (si faltan, igual queda COMPLETO):
  - evidencia de ingresos (fotos y recibos);
  - clasificación regulatoria (RCC, Experian);
  - hoja resumen;
  - declaración jurada patrimonial.
- Estados del expediente (calculados, nunca escritos a mano):
  - `PENDIENTE`: aún sin documentos o sin revisar.
  - `INCOMPLETO`: falta algún documento obligatorio del Anexo 13.
  - `INCOMPLETO DEFINITIVO`: [criterio por confirmar; ver preguntas].
  - `COMPLETO`: todos los obligatorios del Anexo 13, aunque falten los opcionales.
- Se reutiliza la validación demo de documentos existente (`REGLAS_DEMO_VALIDACION.md`).

### Etapa 4 — Base matriz (IMPULSO MYPERÚ)

Es la **fuente de todo** el módulo. Un registro por crédito honrado con:

- **Datos del crédito:** ID, DNI/RUC, tipo de persona, crédito SICMACT, cuenta, operación, zona, tienda, cliente.
- **Lote y fechas:** N° de lote, fecha de corte, fecha de solicitud, fecha de honramiento.
- **Montos:** saldo insoluto, interés devengado, factor de cobertura, monto honrado.
- **Estado de documentación** (etapa 3).
- **Recuperos (pari passu):** capital pagado después del honramiento, reversión acumulada, devuelta y pendiente.

En RCT no es un Excel aparte: es la **vista consolidada** de las demás etapas, exportable al formato del Excel actual.

### Etapa 5 — Pagos: sincronización automática desde el core

**Antes:** se llenaba a mano un archivo mensual de pagos, día a día, y luego se pasaba a la base matriz.
**Con RCT:** los pagos llegan solos desde el core cada 30 minutos.

En cada sincronización:

1. Se leen los **pagos nuevos** del core (tabla espejo `core_pagos`): crédito, fecha y hora, monto total, capital, interés, mora, tienda, canal, `id_core`.
2. Si el crédito pertenece a **Impulso MyPerú**:
   - si está **honrado** a la fecha del pago: **reversión = capital × factor de cobertura**, y se marca **AGREGADO** automáticamente (alimenta el pari passu);
   - si **no** está honrado: se registra el pago con **reversión = 0** y el motivo "Crédito no honrado a la fecha del pago".
3. Si el crédito tiene un **FUD aprobado** de campaña: se avisa al ejecutivo para cargar sustentos y se alimenta el "recuperado" del Centro de Control.
4. **Notificaciones** al detectar un pago del programa:
   - **aviso emergente (toast)** en pantalla, sin recargar la página. Ejemplo: *"💰 Pago detectado: García Cruz Iris María – S/ 2 500,00 a capital → Reversión COFIDE S/ 2 000,00"*;
   - **campana** con contador;
   - **correo** con plantilla institucional al revisor de programas y al Jefe de Recuperaciones.
5. **Bitácora:** "Pago sincronizado desde core el DD/MM/AAAA HH:MM".

Estados del pago: `Sincronizado` → `Agregado` → `Incluido en devolución` → `Devuelto` (o `Sin reversión`, si el crédito no estaba honrado).

### Etapa 6 — Devolución a COFIDE (inicio del mes siguiente)

- Comercial carga el **reporte mensual de saldos insolutos**.
- COFIDE envía la **notificación de recuperación extrajudicial** (archivo TXT).
- RCT la importa y la **cruza crédito por crédito** contra lo calculado (equivale a la hoja NOTIFICACIÓN TXT actual).
- **Objetivo:** devolver exactamente lo calculado. **Diferencia = S/ 0,00.**
- Si hay diferencias, se muestran por crédito para resolverlas antes de devolver. Solo se puede cerrar la devolución con diferencia 0, o con justificación del Jefe.

---

## 4. Reglas de cálculo

| Concepto | Fórmula / regla |
| --- | --- |
| Candidato a solicitud | Crédito del programa con días de atraso ≥ 90 (según core) y que no esté en otro lote |
| Saldo insoluto del lote | Σ saldo insoluto de los créditos del lote |
| Reversión por pago | `capital pagado × factor de cobertura` si el crédito está honrado a la fecha del pago; si no, 0 |
| Reversión acumulada (pari passu) | Σ reversiones de pagos AGREGADOS del crédito |
| Devolución del mes | Σ reversiones agregadas del mes que no se hayan devuelto antes |
| Diferencia del cruce | `devolución calculada RCT − monto notificado por COFIDE (TXT)`, por crédito y en total; debe ser 0 |
| Estado de documentación | COMPLETO si están todos los obligatorios del Anexo 13 según tipo de persona; los opcionales no cuentan |

- Montos en `decimal(15,2)`; factor de cobertura con al menos 4 decimales.
- Redondeo a 2 decimales solo en el resultado de cada reversión (parámetro, por confirmar con el área).

---

## 5. Notificaciones del módulo

| Evento | Pantalla (toast) | Campana | Correo | Destinatario |
| --- | --- | --- | --- | --- |
| Crédito llega a 90 días | — | ✔ | ✔ | Revisor de programas |
| Lote con diferencia vs. confirmación COFIDE | ✔ | ✔ | ✔ | Revisor + Jefe |
| Lote honrado (reporte importado) | ✔ | ✔ | ✔ | Revisor + agencias de la zona |
| Expediente incompleto u observado | — | ✔ | ✔ | Tienda responsable |
| **Pago detectado en crédito honrado** | ✔ | ✔ | ✔ | Revisor + Jefe |
| Pago en crédito no honrado (sin reversión) | ✔ | ✔ | — | Revisor |
| Cruce TXT con diferencias | ✔ | ✔ | ✔ | Revisor + Jefe |
| Devolución próxima a vencer | — | ✔ | ✔ | Revisor + Jefe + Contabilidad |

Correo en demo: `MAIL_MAILER=log` o Mailpit (incluido en Laragon). No se envían correos reales.

---

## 6. Preguntas abiertas (responder con el área)

Mientras no se respondan, se usan supuestos razonables como **parámetros configurables**.

1. ¿Cuáles son exactamente los **6 documentos (persona natural)** y los **7 (persona jurídica)** del Anexo 13? ¿El 7.º es la vigencia de poder del representante?
2. ¿Cuándo un expediente pasa a **INCOMPLETO DEFINITIVO**?
3. "Honrado" para la reversión: ¿se compara la **fecha del pago** con la **fecha de honramiento**? ¿Qué pasa con un pago hecho entre la solicitud y el honramiento?
4. ¿La reversión es solo sobre **capital**?
5. **Formato del TXT** de COFIDE (columnas, separador, decimales). Un ejemplo anonimizado sirve.
6. **Formato de nombres de archivo** que se envía a las agencias.
7. Plantillas de la **notificación Word** y de la **plantilla Excel** de honramiento.
8. ¿Cómo **redondea** COFIDE (por pago, por crédito o por total)?
9. ¿Qué tablas o vistas del **core** tienen los pagos, y con qué campos?

---

## 7. Prompt para Claude Code

Pega esto en Claude Code (modo **Plan**):

```
NUEVO MÓDULO: IMPULSO MYPERÚ — honramiento y devolución a COFIDE.

Lee docs/PROCESO_IMPULSO_MYPERU.md completo. Es el proceso real del área y
PREVALECE sobre RCT_CONTEXTO_NEGOCIO.md §6 y sobre cualquier implementación
previa de programas de gobierno o COFIDE.

OBJETIVO
Modelar las 6 etapas: solicitud por lotes → honramiento → revisión de
expedientes (Anexo 13) → base matriz → pagos sincronizados desde el core con
reversión → devolución a COFIDE con cruce TXT (diferencia = S/ 0,00).
Es una demo, pero los CÁLCULOS deben ser reales y exactos.

PUNTOS CLAVE
1. Integración con el core (sección 2 del documento):
   - Tablas espejo desnormalizadas core_creditos y core_pagos que simulan el core.
   - Interfaz FuenteCore con implementación FuenteCoreDemo, para conectar el
     core real después sin cambiar pantallas ni cálculos.
   - Comando rct:sincronizar-pagos-core, programado cada 30 minutos,
     idempotente por id_core, con fecha y hora de última sincronización visible.
   - NO hay registro manual de pagos: la pantalla "Pagos sincronizados" es de
     solo lectura, con filtros.
2. Reversión = capital × factor de cobertura, solo si el crédito está honrado a
   la fecha del pago; si no, reversión 0 con motivo. Marcado AGREGADO automático.
3. Revisión de expedientes: de los 10 documentos actuales, solo los del Anexo 13
   determinan el estado (6 persona natural / 7 jurídica). Evidencia, RCC-Experian,
   hoja resumen y DJ patrimonial se piden pero NO afectan el estado. Estados
   calculados: PENDIENTE, INCOMPLETO, INCOMPLETO DEFINITIVO, COMPLETO. Agrega
   "tipo de persona" al crédito.
4. Notificaciones (sección 5): toast en pantalla sin recargar (polling cada 10 s
   está bien), campana con contador y correo con plantilla institucional
   (MAIL a log/Mailpit). Usa el sistema de notificaciones de Laravel (canal
   database + mail); si ya existe una campana, reutilízala.
5. Para la demo: comando rct:simular-pago-core y un botón visible solo para el
   administrador, que insertan un pago realista en core_pagos y disparan la
   sincronización. En segundos debe aparecer el toast con el monto de reversión,
   subir la campana y quedar el correo en el log. Esta escena irá en el video.
6. Preguntas abiertas (sección 6): usa supuestos razonables como PARÁMETROS
   configurables y lístalos en el plan.

PANTALLAS
- Candidatos a honramiento (créditos que llegan a 90 días, según el core).
- Lotes: armar lote, generar notificación Word y plantilla Excel, registrar
  autorización de firma, registrar confirmación de COFIDE con validación.
- Importar reporte de honramiento de COFIDE (Excel).
- Aviso a agencias por zona.
- Revisión de expedientes (dashboard) con la regla del Anexo 13.
- Base matriz: vista consolidada, filtros y exportación al formato del Excel actual.
- Pagos sincronizados (solo lectura) con última sincronización.
- ⭐ Devolución mensual y cruce TXT: importar el TXT, cruce crédito por crédito
  con semáforo ✅/❌ y el total arriba en grande "Diferencia: S/ 0,00". Solo se
  cierra con diferencia 0 o con justificación del Jefe. Es la escena estrella
  del video: debe verse muy clara y con animación al cruzar.
- Indicadores: lotes del mes, monto honrado, pendientes por zona, reversiones
  del mes, devolución por vencer, tendencia de pagos por día del mes.

DATOS DE DEMO
Seeder con unos 3 meses de operación coherente con los créditos existentes:
lotes en distintos estados, personas naturales y jurídicas, expedientes en los
4 estados, pagos en core_pagos antes y después del honramiento (para mostrar
reversión 0), un mes con devolución cuadrada en S/ 0,00 y otro con 2
diferencias por resolver, con un TXT de ejemplo para cada mes en storage.
Todo determinista e incluido en rct:demo-reset.

REGLAS
- Primero dame el plan (qué reutilizas de lo existente, tablas, estados,
  cálculos, pantallas, importaciones/exportaciones, parámetros asumidos) y
  espera mi OK.
- Tests unitarios para: reversión, regla de honrado, pari passu, estado del
  expediente por tipo de persona, idempotencia de la sincronización y cruce TXT.
- Implementa por partes, un commit por parte, y dime con qué usuario y qué
  pantalla probar cada una.
- Mismo diseño institucional; pantallas limpias porque irán en el video.
- Si hay cambios de estructura, avísame al final si debo ejecutar rct:demo-reset.
```
