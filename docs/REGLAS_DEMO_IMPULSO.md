# Reglas internas — Módulo Impulso MyPerú (supuestos y datos de demo)

> **Documento interno del equipo de desarrollo. No se muestra en la interfaz.**
> El proceso real está en `docs/PROCESO_IMPULSO_MYPERU.md`. Este documento lista los **supuestos** que se
> tomaron para las preguntas abiertas de su sección 6, dónde se configuran y cómo se arman los datos de demo.
> Los **cálculos son reales y exactos** (BCMath, 2 decimales). Solo son supuestos los formatos y parámetros que el
> área aún debe confirmar.

---

## 1. Supuestos de las preguntas abiertas (sección 6)

Todos son parámetros en `config/rct.php`, sección `impulso`, o datos en tablas. No están fijos en el código.

| # | Pregunta | Supuesto usado | Dónde se cambia |
| --- | --- | --- | --- |
| 1 | Documentos del Anexo 13 | **Natural (6):** DNI, Ficha SPLAFT, DJ del programa (Anexo 02/03), contrato, cronograma y pagaré. **Jurídica (7):** reporte tributario, ficha RUC, Ficha SPLAFT, DJ del programa, contrato, cronograma y pagaré. El 7.º de persona jurídica es la **ficha RUC**, no la vigencia de poder. **No afectan el estado:** hoja resumen, DJ patrimonial, RCC/Experian y evidencia de ingresos. | Tabla `gob_tipos_documento` (`cofide_natural`, `cofide_juridica`) |
| 2 | ¿Cuándo pasa a INCOMPLETO DEFINITIVO? | Cuando la tienda declara **"no disponible"** un documento obligatorio después de buscarlo (con motivo). Queda incompleto de forma permanente. | `App\Services\Honramiento\Checklist` |
| 3 | "Honrado" para la reversión | Se compara la **fecha del pago** con la **fecha de honramiento** del reporte de COFIDE. El mismo día cuenta como honrado. Un pago hecho entre la solicitud y el honramiento **no genera reversión** (motivo: "Crédito no honrado a la fecha del pago"). | `Calculos::honradoALaFecha` |
| 4 | ¿La reversión es solo sobre capital? | **Sí**: reversión = capital pagado × factor de cobertura. Interés y mora no se revierten. | `Calculos::reversion` |
| 5 | Formato del TXT de COFIDE | Encabezado y una línea por crédito, separador `|`, punto decimal: `CODIGO_IFI|PERIODO|NRO_LOTE|NRO_CREDITO|TIPO_DOC|NRO_DOC|CAPITAL_RECUPERADO|FACTOR_COBERTURA|MONTO_A_DEVOLVER`. Solo son obligatorias `NRO_CREDITO` y `MONTO_A_DEVOLVER`. Si un crédito se repite, sus líneas se suman. | `impulso.txt.separador` y `impulso.txt.decimal`; `CruceTxt` |
| 6 | Formato de nombres de archivo para las agencias | `{N° CRÉDITO}_{DOCUMENTO}.pdf`, un documento por archivo, en PDF. | `impulso.formato_archivo` |
| 7 | Plantillas Word y Excel | **Word:** carta a COFIDE con el resumen del lote, el detalle de créditos y el bloque de firma digital de los representantes. **Excel:** N°, código IFI, N° lote, tipo y N° de documento, tipo de persona, nombre, N° de crédito, fecha y monto de desembolso, saldo insoluto, interés devengado, días de atraso, factor de cobertura y fecha de corte. | `DocumentosLote` |
| 8 | Redondeo de COFIDE | **Por pago**, half-up a 2 decimales. La devolución suma las reversiones ya redondeadas. | `Calculos::redondear` |
| 9 | Tablas o vistas del core | Tablas espejo `core_creditos` y `core_pagos` detrás de la interfaz `App\Contracts\FuenteCore`. Para el core real se implementa `FuenteCoreReal` y se enlaza en `AppServiceProvider` (`impulso.fuente_core`). | `FuenteCoreDemo` |

### Otros parámetros

| Parámetro | Valor | Uso |
| --- | --- | --- |
| `dias_candidato` | 90 | Días de atraso para ser candidato a honramiento |
| `tramos_cobertura` | hasta S/ 30 000 → 98 %; hasta S/ 90 000 → 95 %; hasta S/ 300 000 → 90 %; más → 80 % | Factor estimado según el monto desembolsado. El definitivo lo trae el reporte de COFIDE |
| `codigo_ifi` | 0109 | Código de la Caja ante COFIDE |
| `representantes` | Carlos Benites Alvarado (Gerente de División de Negocios) y Rosa Elvira Campos Lázaro (Gerente de Finanzas). Nombres ficticios | Firma digital del lote |
| `revisores` | jefe@cajatrujillo.pe | "Revisor de programas" de la sección 5. Hoy lo cumple la Jefatura |
| `contabilidad` | contabilidad@cajatrujillo.pe | Correo del aviso de devolución próxima a vencer |
| `administradores` | jefe@cajatrujillo.pe | Ven el botón "Simular pago del core" |
| `dia_limite_devolucion` | 15 | La devolución de un mes vence el día 15 del mes siguiente |
| `dias_aviso_vencimiento` | 5 | Días antes del vencimiento en que se avisa |
| `margen_minutos` | 60 | Cada sincronización vuelve a leer este margen. Lo ya procesado se descarta por `id_core` |
| Monto a honrar estimado | (saldo insoluto + interés devengado) × factor | Se usa hasta que llega el reporte de COFIDE, cuyo monto prevalece |
| Plazo de documentación | 30 días calendario desde la conformidad (confirmación de COFIDE) | `Lote::PLAZO_DIAS` |

## 2. Reglas de cálculo implementadas

- **Candidato:** crédito del programa (según el core) con 90 días de atraso o más que no está en ningún lote.
- **Validación del lote:** el N° de registros y el saldo insoluto que confirma COFIDE deben coincidir con los de RCT.
  Si coinciden, el lote queda **Cargado en COFIDE** y se inicia el plazo de documentación. Si no, queda **Observado**, se avisa y se muestra la diferencia.
- **Honramiento:** al importar el reporte, cada crédito pasa a **Honrado** (con fecha, saldo, interés, factor y monto de COFIDE).
  Los créditos observados o que no figuran en el reporte quedan **No honrados**, y el lote queda **Honrado parcial**.
- **Reversión por pago:** `capital × factor` si el crédito está honrado a la fecha del pago. Se marca **AGREGADO** automáticamente; si no está honrado, la reversión es 0 con su motivo.
- **Pari passu:** reversión acumulada = Σ reversiones de pagos agregados, incluidos en una devolución o devueltos.
  La reversión pendiente es la acumulada menos la devuelta.
- **Devolución del mes:** Σ reversiones agregadas del mes que no se devolvieron antes.
- **Cruce TXT:** diferencia = RCT − TXT, por crédito y en total. Solo **cuadra** si cada crédito cuadra: un total en cero con diferencias que se compensan no cuadra.
  Solo se cierra con diferencia S/ 0,00 o con la justificación del **Jefe de Recuperaciones** (mínimo 20 caracteres).
- **Estados del pago:** Sincronizado → Agregado → Incluido en devolución → Devuelto, o Sin reversión.

Los cálculos están cubiertos por tests unitarios (`tests/Unit/Impulso`):

- reversión y redondeo;
- regla de honrado;
- pari passu;
- estado del expediente por tipo de persona;
- idempotencia de la sincronización;
- cruce TXT.

Se ejecutan con `php vendor/bin/phpunit tests/Unit`.

## 3. Avisos (sección 5)

| Evento | Pantalla | Campana | Correo | Destinatario |
| --- | --- | --- | --- | --- |
| Crédito llega a 90 días | — | ✔ | ✔ | Revisor |
| Lote con diferencia frente a COFIDE | ✔ | ✔ | ✔ | Revisor y Jefe |
| Lote honrado | ✔ (revisor) | ✔ | ✔ | Revisor y agencias de cada zona (tiendas con créditos y CZR) |
| Expediente observado | — | ✔ (aviso calculado existente) | ✔ | Tienda responsable |
| Pago en crédito honrado | ✔ | ✔ | ✔ | Revisor y Jefe |
| Pago sin reversión | ✔ | ✔ | — | Revisor |
| Cruce TXT con diferencias | ✔ | ✔ | ✔ | Revisor y Jefe |
| Devolución próxima a vencer | — | ✔ | ✔ | Revisor, Jefe y Contabilidad |

- **Avisos en pantalla:** se usan las notificaciones de Laravel (canal `database`), integradas en la campana existente. La pantalla consulta cada 10 segundos (`/notificaciones/recientes`).
- **Correo:** se usa el canal `mail` con la plantilla `emails/aviso`. En la demo, `MAIL_MAILER=log`: los correos quedan en `storage/logs/laravel.log`.
- **Sincronización automática:** `rct:sincronizar-pagos-core` está programado cada 30 minutos. Para que corra solo hay que dejar activo el planificador con `php artisan schedule:work`.

## 4. Datos de demo (`ImpulsoSeeder`, incluido en `rct:demo-reset`)

Son unos tres meses de operación, determinista para una misma fecha de generación. Las fechas son relativas al día en que se ejecuta.

| Elemento | Contenido |
| --- | --- |
| Core espejo | Un crédito por cada expediente del programa (con saldo e interés coherentes con el monto honrado), más 55 créditos sin solicitar: 22 candidatos (≥ 90 días), 9 próximos (76–89) y 24 al día |
| Lotes | 008 y 009 **honrados**, 010 **honrado parcial** (COFIDE observó 2 créditos), 011 **cargado en COFIDE** (para mostrar la importación del reporte) y 012 **pendiente de firma** (8 candidatos, firmado por un representante) |
| Pagos | Pagos después del honramiento, con reversión agregada y más pagos hacia fin de mes. También pagos entre la solicitud y el honramiento y en créditos en solicitud, sin reversión. Se procesan con la sincronización real |
| Devoluciones | El mes de hace dos meses **cuadró en S/ 0,00 y está devuelto**. El mes anterior tiene **2 diferencias**: COFIDE no consideró un pago de fin de mes y un crédito no figura en el TXT. El mes en curso se acumula en vivo |
| Archivos de ejemplo | `storage/app/private/impulso/ejemplos/`: TXT de cada mes (incluido el corregido, que cuadra) y el reporte de honramiento del lote cargado |
| Avisos | El Jefe ya tiene en la campana el aviso del cruce con diferencias y el de la devolución por vencer |

## 5. Para la escena del video

1. Abrir RCT con `jefe@cajatrujillo.pe` (por ejemplo, el Centro de control o Devolución), en modo presentación.
2. En una terminal, ejecutar `php artisan rct:simular-pago-core`.
   También se puede usar el botón "Simular pago del core" en Pagos, que solo ve el administrador.
3. En 10 segundos o menos aparece el aviso "Pago detectado: … S/ X a capital → Reversión COFIDE S/ Y". La campana sube y el correo queda en `storage/logs/laravel.log`.
4. Para la escena del cruce, abrir Devolución → el mes cuadrado → **Ver cruce**. Otra opción es importar `NOTIFICACION_COFIDE_AAAAMM_corregida.txt` en el mes con diferencias: la animación termina en **Diferencia: S/ 0,00**.
