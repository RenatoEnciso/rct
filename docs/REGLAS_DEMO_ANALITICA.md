# Reglas internas — Analítica en modo DEMO

> **Documento interno del equipo de desarrollo. No se muestra en la interfaz.**
> La analítica de RCT (modelo de priorización, análisis de recaída y alertas de control) es por ahora una
> **simulación con fórmulas fijas**. No usa machine learning. Los resultados se precalculan y se
> guardan en tablas, para que se vean reales y cuadren con el resto del sistema.
> En pantalla se presentan como "Modelo de priorización", "Análisis de recaída" y "Alertas de control".
> La palabra "simulado" no aparece en la interfaz.

---

## 1. Principios

- **Determinista.** No se usa `rand()` en tiempo real. Si una fórmula necesita variación, la toma de un hash
  del ID (`App\Services\Analitica\Determinista::u('clave'.$id)`, valor entre 0 y 1 calculado con `crc32`).
  Con la misma base de datos se obtienen siempre los mismos números.
- **Coherente.** Los créditos, asesores, tiendas, zonas y montos son los mismos del resto del sistema.
  Un número del Centro de control cuadra con el de las otras pantallas. Por ejemplo, el % de expedientes de
  programa completos es el mismo que muestran los Indicadores de honramiento.
- **Reemplazable.** Todo pasa por interfaces (`app/Contracts/Analitica`). Para conectar un modelo real
  basta con otra implementación de la misma interfaz, enlazada en `AppServiceProvider`; las pantallas no cambian.
- **Precalculado.** Las pantallas solo leen tablas. Para recalcular:
  `php artisan rct:analitica-recalcular`. Los estados y comentarios de las alertas se conservan.

## 2. Activación y parámetros

En `.env`:

```
RCT_ANALITICA_MODO=demo
```

En `config/rct.php`, sección `analitica`:

| Parámetro | Valor | Uso |
| --- | --- | --- |
| `tasas_provision` | Normal 1 %, CPP 5 %, Deficiente 25 %, Dudoso 60 %, Pérdida 100 % | Provisión liberada y liberable (referencial SBS) |
| `segmentos` | Alta ≥ 55 · Media ≥ 35 · Baja < 35 | Segmento del puntaje de prioridad |

## 3. Tablas

| Tabla | Contenido | Se genera desde |
| --- | --- | --- |
| `recuperos` | Recuperado, condonado y provisión liberada en soles, por FUD pagado | FUD aprobado con sustento aprobado |
| `metas_zona` | Meta mensual de recupero por zona | Ritmo real de cada zona |
| `priorizacion_cartera` | Puntaje, segmento, probabilidad, montos, motivos y acción sugerida por crédito | Cartera disponible |
| `seguimiento_beneficio` | Estado a 30, 60 y 90 días, recaída, riesgo y semáforo | Recuperos (salvo la cancelación total) |
| `alertas_control` / `alerta_historial` | Alertas detectadas y sus cambios de estado | Reglas sobre los datos del sistema |

## 4. Fórmulas

### 4.1 Recupero de un FUD pagado (`CalculadoraRecupero`)

Se considera **pagado** el FUD aprobado cuyo sustento fue aprobado. La fecha de pago es la de carga del sustento.

Deuda referencial del crédito:

- interés acumulado = saldo × mín(30 %, años de atraso × 20 %)
- mora = saldo × mín(8 %, años de atraso × 4 %)

| Tipo | Recuperado | Condonado | Provisión liberada |
| --- | --- | --- | --- |
| Pago de cuotas | capital de las cuotas (desembolso / 24 × N° cuotas) + interés y mora no exonerados | interés y mora exonerados (% del FUD) | saldo × (tasa actual − tasa Normal), solo si se pone al día |
| Cancelación total | monto a pagar + interés no exonerado | capital descontado + interés exonerado + mora | saldo × tasa actual |
| Extrajudicial / judicial, castigada, FOCMAC | saldo × (1 − % de beneficio) | saldo × % de beneficio | saldo × tasa actual (castigada: 0) |

- **Cartera castigada:** ya está fuera de balance. El recupero es ingreso, no libera provisión.
- **Intereses en suspenso:** en los créditos judiciales, extrajudiciales, castigados y FOCMAC, los intereses
  y la mora están en suspenso (no se reconocieron como ingreso). Su exoneración no es una pérdida y
  **no se cuenta** como monto condonado.

**Recuperado por cada S/ 1 condonado** = recuperado / condonado.

### 4.2 Meta por zona

- Meta mensual = ritmo de recupero de la zona × (0.80 a 1.20, fijo por zona y mes).
- Antes de la campaña, el ritmo es el promedio mensual de enero a agosto.
- En campaña, el ritmo es el recuperado desde el 01/09 dividido entre los meses transcurridos.
- El avance de la campaña compara lo recuperado con la meta acumulada; el mes en curso se prorratea por días.

### 4.3 Modelo de priorización (`PriorizacionDemo`)

```
puntaje = 100 × (0.30·Pago + 0.25·Recuperable + 0.15·Provisión + 0.20·Umbral + 0.10·Historial) ± 5
```

| Componente | Cálculo (0 a 1) |
| --- | --- |
| Pago | base por calificación (CPP 0.75, Deficiente 0.60, Dudoso 0.45, Pérdida 0.25) − 0.05 por cada conciliación en 12 meses − 0.10 si es judicial − 0.15 si es castigado |
| Recuperable | percentil de (monto recuperable × Pago) dentro de la cartera |
| Provisión | percentil de la provisión liberable |
| Umbral | 1 si faltan 15 días o menos para pasar a la siguiente calificación (31, 61 y 121 días); baja a 0 en los 30 días siguientes |
| Historial | 1 si ya cumplió un beneficio anterior (tiene un recupero) y no recayó después |
| ± 5 | ajuste determinista por crédito |

- **Monto recuperable.** En pago de cuotas, el 25 % del saldo (las cuotas vencidas). En el resto, el saldo con el
  descuento máximo de su lineamiento.
- **Provisión liberable.** En cuotas, saldo × (tasa actual − tasa Normal). En el resto, saldo × tasa actual
  (castigada: 0).
- **Probabilidad de pago** = Pago × 100 ± ajuste, entre 5 % y 95 %.
- **Recupero esperado** = monto recuperable × probabilidad.
- **Motivos (los 2 o 3 de mayor peso):**
  - "Cerca de pasar a Deficiente/Dudoso/Pérdida (N días)". Si el crédito ya tiene esa calificación o una peor, dice
    "Cerca de los 61 días de atraso (N días)"
  - "Cumplió última promesa"
  - "Saldo alto" (percentil ≥ 0.75)
  - "Libera provisión" (percentil ≥ 0.75)
  - "Alta probabilidad de pago" (Pago ≥ 0.65)
  - "Cierre de mes en N días" (≤ 5 días)
  - "Sin beneficios en 12 meses"
  - "Garante contactable"
- **Acción sugerida** (se aplica la primera regla que se cumple):
  1. saldo alto y Pago < 0.45 → Visitar
  2. cuotas → Llamar (si Pago ≥ 0.7) u Ofrecer pago de cuotas
  3. Pago ≥ 0.7 → Llamar
  4. el resto → Ofrecer cancelación
- **Mejor horario:** uno de 4 bloques, elegido por hash. Las personas jurídicas solo tienen bloques de mañana.
- **Pantallas:** "Mi prioridad de hoy" (asesor) muestra los 20 créditos pendientes de su tienda con mayor puntaje; el
  botón "Gestionar" abre Nueva propuesta con el cliente seleccionado (`/fud/crear?cliente=ID`). "Priorización de cartera"
  (Jefatura y CZR) muestra el mapa de calor zona (o tienda) × segmento con créditos pendientes y recupero esperado.
- **Cartera pendiente:** créditos sin propuesta desde el inicio de la campaña. La "cartera prioritaria" es la pendiente
  de segmento Alta o Media.

**Simulador de impacto (Centro de control).** Recorre la cartera pendiente en orden de puntaje y suma el recupero
esperado y la provisión esperada del X % de los créditos. Lo compara con el mismo X % tomado en el orden de la base de
aptos (por número de crédito). El cálculo se hace en el navegador con las mismas filas de `priorizacion_cartera`.

### 4.4 Análisis de recaída (`RecaidaDemo`)

Se aplica a los beneficios pagados en los que el crédito sigue vigente; la cancelación total no puede recaer.

```
probabilidad = base × multiplicador por calificación × (1.3 si tuvo 2 o más conciliaciones)   (máximo 0.85)
```

| Base | | Calificación | Multiplicador |
| --- | --- | --- | --- |
| Cuotas que se pone al día | 0.12 | CPP | 0.8 |
| Cuotas sin ponerse al día | 0.38 | Deficiente | 1.0 |
| Extrajudicial / judicial | 0.28 | Dudoso | 1.15 |
| Castigada | 0.20 | Pérdida | 1.3 |
| FOCMAC | 0.24 | | |

- `u = hash('recaida' + id del FUD)`.
- **Recae** si u < probabilidad. El día de la recaída es 12 + (u / probabilidad) × 78, es decir, entre el día 12 y el 90.
- **Atraso leve** si u está entre la probabilidad y la probabilidad + 0.10. Se marca a los 60 y 90 días.
- El estado a 30, 60 o 90 días queda vacío hasta que transcurre ese plazo.
- **Riesgo** = probabilidad × 100 (× 1.6 si tiene atraso leve) ± 5.
- **Semáforo:** rojo si recayó o el riesgo es ≥ 40; ámbar si es ≥ 20; verde en el resto.
- **Recuperación sostenida a 90 días** = beneficios que no recayeron / beneficios con 90 días cumplidos.
- **Pantalla "Análisis de recaída"** (Jefatura y CZR):
  - KPI de recuperación sostenida a 90 días;
  - curva por mes del beneficio (% al día a 30, 60 y 90 días);
  - % de recaída a 90 días por tipo de beneficio;
  - los 10 beneficios de los últimos 90 días con mayor riesgo, con semáforo.
- **Aviso en Nueva propuesta:** si el cliente recayó tras un beneficio, se muestra "Este cliente recayó tras su último
  beneficio (hace N días)", con la fecha del pago y la de la recaída.
- El análisis de recaída se calcula **antes** que la priorización, para que un cliente que recayó no figure con el
  motivo "Cumplió última promesa".

### 4.5 Alertas de control (`AnomaliasDemo`)

Cada regla compara a la persona o tienda con el promedio de su zona o del resto.

| Tipo | Regla | Severidad |
| --- | --- | --- |
| Descuento al tope | ≥ 5 propuestas de cuotas o cancelación, ≥ 60 % con el descuento máximo y ≥ 2 veces el promedio de la zona | Alta si ≥ 85 % |
| Aprobación en minutos | ≥ 4 aprobaciones de un mismo asesor con promedio ≤ 15 min, y el aprobador demora ≥ 4 veces más con el resto | Alta |
| Excepciones por tienda | ≥ 4 propuestas "ESPECIAL" y ≥ 3 veces el % del resto de tiendas | Alta si ≥ 30 % |
| Beneficios consecutivos | ≥ 2 beneficios aprobados con ≥ 60 días de diferencia (5 de mayor saldo) | Alta si son ≥ 3 |
| Misma firma | El mismo documento (hash) en sustentos de clientes distintos | Alta |
| Carga fuera de horario | ≥ 3 cargas antes de las 7:00, desde las 21:00 o en domingo, que son ≥ 30 % de sus cargas | Media |
| Honramiento vencido | ≥ 2 expedientes de gobierno Pendiente con el plazo de 30 días vencido (3 tiendas con más casos) | Media si son ≥ 4 |

`AnaliticaSeeder` **siembra** estos patrones en los datos de la demo, para que las reglas los detecten y cuadren
con lo que se ve en otras pantallas:

| Patrón sembrado | Quién o dónde |
| --- | --- |
| Descuento máximo en todos sus FUD | ejecutivo3 (Piura), senior2 (Villa El Salvador) |
| Aprobación por el CZR en 3–9 minutos | ejecutivo4 (Tarapoto), senior3 (Centro Oriente) |
| Excepciones concentradas | Pucallpa y Bagua Chica |
| Mismo documento en clientes distintos | 3 pares de Acuerdos de Pago recientes |
| Sustentos cargados de noche | ejecutivo7 (San Juan de Lurigancho), ejecutivo10 (Huánuco) |
| Beneficios consecutivos | Clientes con FUD reciente que también tuvieron un beneficio entre enero y junio |

También deja algunas alertas gestionadas por la Jefatura (En revisión, Descartada o Confirmada, con comentario e
historial). En la aplicación, el cambio de estado de una alerta se guarda de verdad.

**Devoluciones a COFIDE:** no forman parte de esta analítica. El indicador del Centro de control está reservado
(`kpis['cofide']`) para conectarlo con el módulo de devoluciones (ver `docs/PROCESO_IMPULSO_MYPERU.md`).

## 5. Datos de la demo

- La cartera tiene unos **1 500 créditos**. Los FUD van de enero a junio de 2026 (historial cerrado) y de julio a la fecha (campaña).
- Para regenerar todo desde cero: `php artisan rct:demo-reset`. Primero respalda la base en `storage/backups/`, después
  borra los archivos de demo y ejecuta `migrate:fresh --seed`. **Borra todo lo registrado a mano.**

## 6. Para pasar a un modelo real

1. Implementar `ModeloPriorizacion`, `ModeloRecaida` y/o `DetectorAnomalias` con el modelo real.
2. Enlazarlo en `AppServiceProvider` según `config('rct.analitica.modo')`.
3. Programar `rct:analitica-recalcular` (por ejemplo, diario, después de la carga del core).
4. Quitar la siembra de patrones (`AnaliticaSeeder`) en producción.
