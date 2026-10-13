# Proceso real — Programa IMPULSO MYPERÚ (honramiento y devolución a COFIDE)

> Documento de contexto para el módulo **Programas de Gobierno** de RCT.
> Describe el proceso tal como lo ejecuta hoy el área. **Prevalece sobre lo descrito
> en la sección 6 de `RCT_CONTEXTO_NEGOCIO.md`** cuando haya diferencias.
> Al final está el prompt para Claude Code.

---

## 1. Visión general

```
Crédito llega a 90 días de atraso
        │
        ▼
[1] SOLICITUD (todo el mes) ──► Lote cargado en plataforma COFIDE
        │
        ▼
[2] HONRAMIENTO (mes siguiente) ──► Reporte COFIDE: saldo, interés, cobertura, monto a honrar
        │                                │
        │                                └─► Aviso a agencias por zona
        ▼
[3] REVISIÓN DE EXPEDIENTES (Anexo 13) ──► PENDIENTE / INCOMPLETO / INCOMPLETO DEFINITIVO / COMPLETO
        │
        ▼
[4] BASE MATRIZ ◄── fuente única de verdad de cada crédito honrado
        ▲
        │
[5] PAGOS (día a día) ──► Reversión = capital × cobertura (solo si está honrado) ──► AGREGADO
        │
        ▼
[6] DEVOLUCIÓN A COFIDE (inicio del mes siguiente)
        Cruce con notificación TXT de COFIDE ──► debe cuadrar al 100%, diferencia = 0
```

---

## 2. Detalle por etapa

### Etapa 1 — Solicitud de honramiento (durante todo el mes)

- Cada día, los créditos del programa que **llegan a 90 días de atraso** pasan a ser candidatos a honramiento.
- Por cada **corte** se arma:
  - La **notificación** (documento Word).
  - La **plantilla de honramiento** (Excel), con el formato de COFIDE.
- Se solicita a los **representantes ante COFIDE** la autorización para usar su **firma digital**.
- Se carga en la plataforma de COFIDE, que confirma el **N° de registros** y el **saldo insoluto total**.
- **Cada carga es un lote.**

Datos del lote: N° de lote, fecha de corte, fecha de carga, créditos incluidos, N° de registros, saldo insoluto total (calculado por RCT y confirmado por COFIDE), estado de la autorización de firma, estado del lote.

Estados del lote: `En preparación` → `Pendiente de firma` → `Firmado` → `Cargado en COFIDE` → `Honrado` (parcial o total) / `Observado`.

Validación clave: el N° de registros y el saldo insoluto total que confirma COFIDE **deben coincidir** con lo que RCT calculó para el lote.

### Etapa 2 — Honramiento (mes siguiente)

- Se descarga de COFIDE el **reporte detallado** por crédito:
  - saldo insoluto,
  - interés devengado,
  - **factor de cobertura**,
  - **monto a honrar**.
- Se importa a RCT y se actualiza cada crédito del lote como **honrado**, con su fecha de honramiento.
- Se avisa a las **agencias por zona**: créditos pendientes de documentación y monto a honrar, con el **formato de nombres de archivo** establecido para los expedientes.

### Etapa 3 — Revisión de expedientes (dashboard)

- Se piden todos los documentos, pero **solo se evalúan los exigidos por COFIDE (Anexo 13)**:
  - **Persona natural:** 6 documentos obligatorios.
  - **Persona jurídica:** 7 documentos obligatorios.
- Documentos que se piden pero **NO afectan el estado** (si faltan, igual queda COMPLETO):
  - Evidencia de ingresos (fotos y recibos).
  - Clasificación regulatoria (RCC, Experian).
  - Hoja resumen.
  - Declaración jurada patrimonial.
- Estados del expediente:
  - `PENDIENTE`: aún no se ha revisado o no se han cargado documentos.
  - `INCOMPLETO`: falta algún documento obligatorio del Anexo 13.
  - `INCOMPLETO DEFINITIVO`: [por confirmar el criterio exacto; ver preguntas].
  - `COMPLETO`: están todos los obligatorios del Anexo 13 (aunque falten los opcionales).

### Etapa 4 — Base matriz (IMPULSO MYPERÚ)

Es la **fuente de todo**. Un registro por crédito honrado con:

- Datos del crédito: ID, DNI/RUC, tipo de persona (natural/jurídica), crédito SICMACT, cuenta, operación, zona, tienda, cliente.
- Lote y fechas: N° de lote, fecha de corte, fecha de solicitud, fecha de honramiento.
- Montos: saldo insoluto, interés devengado, factor de cobertura, monto honrado.
- Estado de documentación (de la etapa 3).
- Acumulados de recupero: capital pagado después del honramiento, reversión acumulada, reversión devuelta, reversión pendiente (pari passu).

En RCT, la base matriz no es un Excel aparte: es la **vista consolidada** de la información de las demás etapas. Se puede exportar con el mismo formato del Excel actual.

### Etapa 5 — Pagos (registro diario)

- Cada pago de un cliente del programa se registra día a día: fecha, monto total, **monto a capital**.
- **Reversión = capital pagado × factor de cobertura.**
- **Regla crítica:** la reversión **solo aplica si el crédito ya está honrado**. Si el cliente pagó y el crédito **no** está honrado, **no se genera reversión** (el pago se registra, pero con reversión = 0 y el motivo).
- Cuando el pago se pasa a la base matriz se marca como **AGREGADO**; con eso queda calculado el **pari passu** (la parte del recupero que corresponde a COFIDE).

Estados del pago: `Registrado` → `Agregado` (a la base matriz) → `Incluido en devolución` (del mes) → `Devuelto`.

### Etapa 6 — Devolución a COFIDE (inicio del mes siguiente)

- Comercial carga el **reporte mensual de saldos insolutos**.
- COFIDE envía la **notificación de recuperación extrajudicial** (archivo TXT).
- Se cruza en la hoja **NOTIFICACIÓN TXT** contra lo calculado por RCT.
- **Objetivo:** devolver a COFIDE exactamente lo calculado. **Diferencia = 0.**
- Si hay diferencias, RCT las muestra crédito por crédito para resolverlas antes de devolver.

---

## 3. Reglas de cálculo

| Concepto | Fórmula / regla |
| --- | --- |
| Candidato a solicitud | Crédito del programa con días de atraso ≥ 90 y no incluido en otro lote |
| Saldo insoluto del lote | Σ saldo insoluto de los créditos del lote |
| Reversión por pago | `capital pagado × factor de cobertura` si el crédito está honrado a la fecha del pago; si no, 0 |
| Reversión acumulada (pari passu) | Σ reversiones de pagos AGREGADOS del crédito |
| Devolución del mes | Σ reversiones de pagos agregados del mes, no devueltas antes |
| Diferencia del cruce | `devolución calculada RCT − monto notificado por COFIDE (TXT)`, por crédito y total; debe ser 0 |
| Estado de documentación | COMPLETO si están todos los obligatorios del Anexo 13 según tipo de persona; los opcionales no cuentan |

Montos en `decimal(15,2)`; factor de cobertura con al menos 4 decimales. Redondeo a 2 decimales solo en el resultado final de cada reversión. [Confirmar con el área cómo redondea COFIDE para que el cruce dé 0.]

---

## 4. Preguntas abiertas (responder con el área)

1. ¿Cuáles son exactamente los **6 documentos (persona natural)** y los **7 (persona jurídica)** del Anexo 13? ¿El 7.º es la vigencia de poder del representante?
2. ¿Cuándo un expediente pasa a **INCOMPLETO DEFINITIVO**? ¿Plazo vencido, documento imposible de obtener, decisión del revisor?
3. "Honrado" para la reversión: ¿se compara la **fecha del pago** con la **fecha de honramiento**? ¿Qué pasa con un pago hecho entre la solicitud y el honramiento?
4. ¿La reversión es solo sobre **capital**, o también sobre interés devengado?
5. ¿Cuál es el **formato del TXT** de COFIDE (columnas, separador, decimales)? Un ejemplo anonimizado sirve.
6. ¿Cuál es el **formato de nombres de archivo** que se envía a las agencias?
7. ¿Plantillas de la **notificación Word** y de la **plantilla Excel** de honramiento? (Para generarlas automáticamente.)
8. ¿Cómo redondea COFIDE (por pago, por crédito o por total)?

---

## 5. Prompt para Claude Code

Pega esto en Claude Code (modo **Plan**):

```
NUEVO MÓDULO: proceso real de IMPULSO MYPERÚ (honramiento y devolución a COFIDE).

Lee docs/PROCESO_IMPULSO_MYPERU.md completo. Describe el proceso real del área y
PREVALECE sobre lo que diga RCT_CONTEXTO_NEGOCIO.md §6 y sobre cualquier
implementación previa de programas de gobierno o COFIDE que ya exista.

OBJETIVO
Modelar en RCT las 6 etapas: solicitud por lotes → honramiento → revisión de
expedientes (Anexo 13) → base matriz → pagos con reversión → devolución a COFIDE
con cruce del TXT (diferencia = 0). Es una demo, pero los CÁLCULOS deben ser
reales y exactos.

ANTES DE IMPLEMENTAR
1. Revisa lo que ya existe (ExpedienteGobierno, revisión de programas, módulo
   COFIDE / pagos posteriores / devoluciones si ya se hizo) y dime qué se
   reutiliza, qué se modifica y qué sobra.
2. Ajuste importante a la revisión de expedientes: de los 10 documentos, solo los
   del Anexo 13 determinan el estado (6 persona natural / 7 jurídica). Evidencia
   de ingresos, RCC-Experian, hoja resumen y DJ patrimonial se piden pero NO
   afectan el estado. Estados: PENDIENTE, INCOMPLETO, INCOMPLETO DEFINITIVO,
   COMPLETO. Agrega "tipo de persona" al crédito.
3. Mientras el área responde las preguntas de la sección 4, usa supuestos
   razonables, déjalos como PARÁMETROS configurables y lístalos en el plan.
4. Dame el plan (tablas, estados, cálculos, pantallas, importaciones y
   exportaciones) y espera mi OK.

PANTALLAS ESPERADAS
- Candidatos a honramiento: créditos que llegan a 90 días, por día de corte.
- Lotes: armar lote, generar notificación (Word) y plantilla (Excel), registrar
  autorización de firma digital, registrar confirmación de COFIDE (N° registros y
  saldo) con validación contra lo calculado.
- Importar reporte de honramiento de COFIDE (Excel) → actualizar créditos.
- Aviso a agencias por zona: pendientes y monto a honrar (pantalla + correo/log).
- Revisión de expedientes (dashboard) con la regla del Anexo 13.
- Base matriz: vista consolidada con filtros y exportación al formato actual.
- Pagos: registro diario (o importación), cálculo automático de reversión, regla
  "solo si está honrado", marcado AGREGADO.
- Devolución mensual: cálculo del mes, importación del TXT de COFIDE, cruce
  crédito por crédito, resaltar diferencias, y cerrar la devolución solo si
  la diferencia total es 0 (o con justificación del Jefe).
- Indicadores: lotes del mes, monto honrado, pendientes de documentación por
  zona, reversiones del mes, devolución por vencer, tendencia de pagos por día
  del mes.

DATOS DE DEMO
Seeder con ~3 meses de operación coherente: lotes con distintos estados,
créditos honrados de persona natural y jurídica, expedientes en los 4 estados,
pagos antes y después del honramiento (para mostrar la regla de reversión = 0),
un mes con devolución cuadrada en 0 y otro con 2 diferencias por resolver.

REGLAS
- Tests unitarios para los cálculos: reversión, pari passu, regla de honrado,
  estado del expediente por tipo de persona, cruce TXT.
- Un commit por parte. Al terminar cada parte: usuario y pantalla para probar.
- Mismo diseño institucional; las pantallas de cruce y devolución deben ser muy
  claras porque irán en el video de presentación.
```
