# RCT — Contexto completo del Sistema de Recuperaciones (Caja Trujillo)

> Documento de contexto para asistentes de IA y para el equipo de desarrollo.
> Fuente normativa principal: Campaña "Sin atrasos, que atrasan" (Departamento de Recuperaciones, vigencia 01/09/2026 – 28/02/2027).
> Última actualización: 11/10/2026.

---

## 0. Cómo usar este documento (instrucciones para la IA)

- RCT es un sistema **web interno** del área de Recuperaciones de Caja Trujillo (Caja Municipal de Ahorro y Crédito, Perú).
- Stack actual: **Laravel** (estructura clásica), desarrollado en VS Code. Existe una versión básica que se quiere pulir y ampliar.
- Responde siempre en **español**, usando la terminología de este documento (expediente, conciliación, SK, RCC, UIT, honramiento, etc.).
- Las reglas de negocio de la sección 2 y 5 son **obligatorias**: cualquier código que calcule descuentos o rutas de aprobación debe respetarlas y ser parametrizable (no valores fijos en código).
- Si algo de la sección 12 (puntos ambiguos) afecta una implementación, **pregunta** antes de asumir.
- La IA del sistema **sugiere y prepara**; nunca aprueba ni registra un descuento sin confirmación humana.
- El **core financiero** es la fuente de verdad de saldos y pagos. RCT lee del core y registra decisiones; no modifica saldos.

---

## 1. Propósito y alcance

RCT digitaliza de punta a punta dos procesos hoy manuales:

1. **Campañas de recuperación de deudas** (conciliación de intereses, moratorio y capital).
2. **Programas de gobierno** (créditos con garantía estatal administrada por COFIDE: honramientos y devoluciones).

Objetivo: que cada descuento sea **trazable**, esté dentro de la **autonomía** de quien lo aprueba y tenga su **sustento documental completo y validado**.

### Problemas actuales que resuelve

- Las solicitudes viajan por correo con el formato **FUD**, sin registro único de quién propuso, aprobó y con qué sustento.
- Los sustentos (Acuerdo de Pago, Nota de Abono, Comprobante de Pago, Informe-Propuesta) se cargan a un **link compartido** y se revisan a mano, a diario, por tienda y zona.
- El reporte mensual de conciliaciones se arma manualmente en los 2 primeros días hábiles del mes.
- En programas de gobierno, cuando un cliente paga después (o cerca) del honramiento, el cálculo de cuánto devolver a COFIDE, de qué operación y cuándo, se hace en Excel.

### Componentes

| Componente | Qué hace |
| --- | --- |
| Módulo Campañas | Configura campaña, carga base de aptos, gestiona solicitud → aprobación → pago → sustento → reporte |
| Módulo Programas de Gobierno | Valida documentos de honramiento, detecta pagos posteriores, calcula devoluciones a COFIDE |
| Motor de reglas | Convierte lineamientos de cada campaña en reglas parametrizables que se validan antes de enviar a aprobar |
| Asistente IA (voz y texto) | Resúmenes, consultas en lenguaje natural, registros asistidos |
| Canal WhatsApp | Consultas, carga de sustentos, resumen diario por rol |

---

## 2. Campaña "Sin atrasos, que atrasan" — reglas que el sistema debe respetar

**Vigencia:** 01/09/2026 al 28/02/2027.
**Objetivos:** (1) disminuir el gasto de provisiones reduciendo el saldo en mora; (2) incrementar ingresos financieros percibidos recuperando cartera vencida, refinanciada, extrajudicial, judicial, castigada y FOCMAC.

### 2.1 Responsables (según el documento)

- **Promoción:** Asesores de Negocios (incl. Líder de Equipo), Administradores de Tienda, Jefes Zonales de Tiendas, personal de Recuperaciones (con apoyo de locadores externos).
- **Evaluación:** los mismos de promoción.
- **Control y seguimiento:** Administradores de Tienda, Jefes Zonales de Tiendas, Coordinadores Zonales de Recuperaciones, Coordinador de Normalización, Jefatura de Recuperaciones.

### 2.2 Lineamientos generales

- Solo clientes en la **base de aptos** que Recuperaciones envía cada mes. El **Jefe de Recuperaciones** puede incluir clientes fuera de base con sustento y autorizar incrementos de descuento.
- Se evalúa: voluntad de pago, afectación del negocio por eventos externos, efecto inflacionario.
- Se consideran días de mora o **proyección de atraso al cierre de mes** y la calificación crediticia, respetando los rangos del Manual de Gestión de Recuperaciones para traslado a Extrajudicial/Judicial.
- Fin: que en el corto plazo el cliente pague su cuota completa.
- Si el cliente tiene **varios créditos en mora**, se negocian todos los que afectan la mora contable.
- **Límite de uso** (según calificación regulatoria al inicio del mes):
  - NORMAL: máximo 4 veces en 12 meses (cuenta cualquier campaña anterior).
  - CPP, DEFICIENTE, DUDOSO, PÉRDIDA: máximo 4 veces en 12 meses. Si excede: CPP/Deficiente → aprueba Jefatura de Recuperaciones; Dudoso/Pérdida → Coordinador Zonal de Recuperaciones por correo con copia al Jefe.
- El % de conciliación de intereses se calcula sobre **interés + interés diferido** (NO incluye interés de gracia).
- **Nunca** se exoneran seguros.
- Todo descuento debe estar sustentado y ser **proporcional** a la situación del cliente y de su garante.

### 2.3 Pago de cuotas (5.1 + Anexo I)

- Lo conciliado de cuotas atrasadas **no supera el 50%** del valor de la(s) cuota(s) pactada(s). Más del 50% solo si se pagan 2+ cuotas y el crédito se pone al día, con autorización de la Jefatura.
- Crédito con 1 cuota en mora al inicio del mes y otra que vence en el mes: el descuento exige que el crédito **cierre el mes vigente**.
- **Alineamiento interno:** no otorgar si el cliente cierra el mes vencido por otro crédito de la Caja, salvo excepción del Jefe.
- Si solo se concilia moratorio, la calificación debe mejorar o mantenerse.

Tabla Anexo I — Tramo Temprano y Contención, No Judicial, calificación según último RCC. **Autoriza: Coordinador Zonal de Recuperaciones.**

| Calificación | Tipo de crédito | Atraso | Cuotas a pagar | Exoneración máx. |
| --- | --- | --- | --- | --- |
| Cualquiera | Todos excepto Pequeña Empresa | 31–60 días | ≥ 1 | 30% intereses* + 100% moratorio |
| Cualquiera | Pequeña Empresa | 31–60 días | ≥ 1 | 40% intereses* + 100% moratorio |
| Deficiente/Dudoso/Pérdida | Todos excepto Hipotecario | ≥ 61 días | 1 | 40% + 100% |
| Deficiente/Dudoso/Pérdida | Todos excepto Hipotecario | 61–90 días | ≥ 2 | 60% + 100% |
| Deficiente/Dudoso/Pérdida | Todos excepto Hipotecario | ≥ 91 días | ≥ 3 | 70% + 100% |
| Cualquiera | Todos | ≥ 61 días | Se pone al día | 80% + 100% |
| CPP | Hipotecario | ≥ 61 días | ≥ 1 | 50% + 100% |
| Deficiente/Dudoso/Pérdida | Hipotecario | ≥ 61 días | ≥ 1 | 60% + 100% |
| Deficiente/Dudoso/Pérdida | Hipotecario | ≥ 61 días | ≥ 2 | 80% + 100% |
| Refinanciado Dudoso/Pérdida | Todos | 9–30 días | 1 | 30% + 100% |
| Refinanciado (cualquiera) | Todos | ≥ 31 días | ≥ 1 | 60% + 100% |

\*Intereses = interés + interés compensatorio.

### 2.4 Cancelación total — créditos vencidos (5.2 + Anexo I)

- Antes de ejecutar, el crédito se traslada al **Módulo de Recuperaciones** del core.
- **SK > 3 UIT:** solo aplica con búsquedas registrales **negativas** (antigüedad ≤ 6 meses). Si son positivas, aplican reglas de cartera judicial.
- **Pago fraccionado:** dentro de la vigencia y/o máximo **90 días** desde la primera amortización, con conformidad de la Jefatura.
- Topes por RCC (texto 5.2): CPP máx. 5% del capital; Deficiente máx. 25%. Ampliables a 25% y 60% respectivamente si se acredita mala calificación en otras entidades (Deficiente o peor). Excepciones mayores: Jefe de Recuperaciones.

| Calificación | Atraso | Exoneración máx. | Autoriza |
| --- | --- | --- | --- |
| CPP | ≥ 31 días | 50% intereses* + 100% moratorio | Jefe Zonal de Tiendas |
| CPP (solo refinanciados) | ≥ 31 días | 70% intereses* + 100% moratorio | Jefe Zonal de Tiendas |
| Deficiente/Dudoso/Pérdida | 31–60 días | 100% intereses* y moratorio | Coordinador Zonal de Recuperaciones (CZR) |
| Deficiente/Dudoso/Pérdida (solo refinanciados) | 31–60 días | 25% SK + 100% intereses* y moratorio | SK < 3 UIT: CZR · SK > 3 UIT: Jefe de Recuperaciones |
| Deficiente/Dudoso/Pérdida | ≥ 61 días | 50% SK + 100% intereses* y moratorio | SK < 3 UIT: CZR · SK > 3 UIT: Jefe de Recuperaciones |

### 2.5 Cartera extrajudicial y judicial (5.3 + Anexo I)

- Dirigido a créditos extrajudiciales y judiciales **no realizables** (2do rango, acciones y derechos, sin bienes inscritos). 1er rango: solo si el Jefe lo evalúa con sustento.
- Pueden incluirse créditos trasladados al Módulo de Recuperaciones durante el mes.
- Créditos > 3 UIT sin días de atraso para traslado: se negocian según el Manual; el Jefe puede autorizar su traslado.
- Extrajudicial con SK > 3 UIT: búsquedas registrales negativas (≤ 6 meses); si positivas, reglas judiciales.
- Judicial: verificar sin cobertura de garantías reales, embargo o bienes embargables, mediante búsquedas registrales de **SUNARP**.
- Búsquedas **positivas** → adjuntar **INFORME** que sustente al menos uno de:
  - El bien/embargo no está en primer rango (cargas de terceros anteriores).
  - El bien no cubre la deuda o la ejecución es incierta a corto/mediano plazo.
  - La garantía o embargo tiene defecto de forma o fondo que impide ejecutarla.
  - La vía judicial es más onerosa y con menor probabilidad de recuperación.
- Judicial además:
  - Ejecución de garantías → negociar tomando como referencia el **monto del gravamen**; medidas cautelares → **monto del embargo**.
  - Si hay etapa de ejecución o fecha de remate → referencia **base del remate**, cancelando antes del acto.
  - Las **consignaciones judiciales** pueden considerarse y deben amortizarse durante la vigencia.

| Cartera | Condición | Exoneración máx. | Autoriza |
| --- | --- | --- | --- |
| Judicial y extrajudicial | Cancelación total | 70% SK + 100% moratorio e intereses* | SK < 3 UIT: CZR · SK > 3 UIT: Jefe de Recuperaciones |
| Castigada ≤ 3 años | Cancelación total | 70% SK + 100% moratorio e intereses* | Coordinador de Normalización de Cartera |
| Castigada > 3 años | Cancelación total | 80% SK + 100% moratorio e intereses* | Coordinador de Normalización de Cartera |
| FOCMAC | Cancelación total | 90% SK + 100% moratorio e intereses* | Coordinador de Normalización de Cartera |

En castigada, la Jefatura puede autorizar **hasta 10 puntos adicionales**.

### 2.6 Procedimiento (sección 6)

- Recuperaciones envía **mensualmente** la data de créditos aptos a toda la red de tiendas.
- Contacto por SMS, cartas courier u otros canales.
- Negocian los responsables de promoción/evaluación y piden autorización al **Nivel de Autonomía**.
- El **Administrador de Tienda** ejecuta la conciliación con el cliente presente en tienda.
- Sustentos obligatorios:
  - Acuerdo de Pago (no aplica a castigados).
  - Nota de Abono.
  - Informe-Propuesta de negociación (si se concilia capital con SK > 3 UIT).
- Operaciones carga **diariamente** en el link compartido el consolidado de documentos (no aplica a castigados), para revisión y entrega a Contabilidad.
- Quien negocia solicita el pase al Módulo de Recuperaciones y pide a Recuperaciones registrar el acuerdo en el core para obtener el monto final.
- Documentos firmados obligatoriamente por el cliente o participantes; pueden remitirse por WhatsApp, correo, etc.
- Propuestas con conciliación parcial de capital: validadas por el **CZR** y elevadas a la **Jefatura**.
- Cancelaciones totales: aprobación en sistema del CZR y/o Jefatura.

### 2.7 Exclusiones (sección 7)

Pignoraticios, leasing, créditos con garantía de depósito en efectivo, cartera Multigas, créditos administrativos (ex trabajadores: revisión previa de Jefatura), carteras adquiridas, créditos registrados como fraude (salvo no reportados o rechazados por la aseguradora), créditos en proceso de venta de cartera, y **créditos con respaldo de programas del Gobierno** (salvo los rechazados para cobertura o con pérdida de eficacia de la garantía; Recuperaciones envía ese listado).

### 2.8 Seguimiento y disposiciones finales

- La Jefatura de Recuperaciones hace seguimiento e informa incidencias a la **Gerencia de División de Negocios** y a la **Gerencia Central de Negocios**.
- Lo no previsto: **Manual de Gestión de Recuperaciones de Créditos**.

### 2.9 Flujo Anexo II (13 actividades, igual para pago de cuotas y cancelación total)

1. Asesor / Gestor / Ejecutivo de Cobranza contacta al cliente (visita, teléfono).
2. Atiende al cliente e informa estado del crédito y cuotas.
3. Verifica si califica (según data de campaña).
4. ¿Verificación positiva? Sí → 5; No → fin.
5. Propone por correo la exoneración al Nivel de Autonomía con el formato **FUD**.
6. Nivel de Autonomía (CZR, Jefe de Recuperaciones o Jefe Zonal de Tiendas) ingresa y aprueba en el sistema. Sí → 7; No → fin.
7. Administrador de Tienda o CZR envía correo a Operaciones con la aprobación, monto a pagar y monto a exonerar (si paga en otra tienda, se envía a esa tienda).
8. Se elabora el Acuerdo de Pago y se remite a Operaciones.
9. Operaciones (Gestor de Servicios / Supervisor) atiende en ventanilla, valida, ejecuta pago, emite Comprobante y Nota de Abono, imprime Acuerdo, toma firmas, entrega copias.
10. Operaciones confirma por correo al Administrador con los escaneos.
11. Administrador / Supervisor **consolida y carga diariamente** los sustentos en el link compartido.
12. CZR **valida diariamente** la carga de su zona y carga el formato de exoneración consolidado.
13. Analista / Asistente / Auxiliar de Recuperaciones genera el **reporte de conciliaciones** en los 2 primeros días hábiles del mes.

---

## 3. Roles y matriz de autonomía

Un usuario puede tener varios roles. Cada rol tiene un **ámbito** (tienda, zona, nacional). Nadie aprueba su propia solicitud.

| Rol en RCT | Cargos | Ámbito | Permisos |
| --- | --- | --- | --- |
| Solicitante N1 | Asesor de Negocios (incl. Líder de Equipo), Gestor de Cobranza, Ejecutivo de Cobranza | Cartera / tienda | Buscar, simular, registrar gestión y negociación, solicitar, subir sustentos |
| Solicitante N2 | Administrador de Tienda, Coordinador Zonal de Recuperaciones, Coordinador de Normalización de Cartera | Tienda / zona / nacional | N1 + casos complejos, reasignar, ejecutar conciliación aprobada |
| Aprobador N1 | Coordinador Zonal de Recuperaciones | Zona | Pago de cuotas; cancelación total Deficiente+ sin capital; capital con SK < 3 UIT |
| Aprobador N2 | Jefe Zonal de Tiendas | Zona | Cancelación total CPP |
| Aprobador N3 | Jefe de Recuperaciones | Nacional | SK > 3 UIT, excepciones a topes, fuera de base, > 50% cuota, 1er rango judicial, +10% castigada, exceso de 4 usos |
| Aprobador N4 | Gerente de División de Negocios | Nacional | Excepciones fuera de autonomía de Jefatura; recibe incidencias |
| Aprobador N5 | Gerencia Central de Negocios (GCN) | Nacional | Última instancia |
| Aprobador especial | Coordinador de Normalización de Cartera | Nacional | Castigada y FOCMAC |
| Revisor | Auxiliares / Analistas / Asistentes de Recuperaciones | Nacional / asignado | Revisión de sustentos de conciliaciones y de programas de gobierno; reporte mensual |
| Operaciones | Gestor de Servicios, Supervisor de Operaciones y Servicios | Tienda | Ver aprobación, cobrar, emitir Nota de Abono, firmas, cargar sustentos |
| Consulta / Auditoría | Auditoría, Riesgos, Contabilidad | Nacional | Solo lectura y exportación |
| Administrador del sistema | TI / Jefatura | Nacional | Usuarios, parámetros, campañas, plantillas |

- La ruta de aprobación la calcula el **motor de reglas** (no el solicitante).
- Escalamiento automático si excede autonomía: N1 → N3 → N4 → N5.
- **Delegación temporal** (vacaciones) con fechas registradas.

---

## 4. Módulo 1: Campañas de recuperación

Cada negociación es un **expediente** con estados, responsables y documentos.

### 4.1 Configuración de campaña
- Nombre, vigencia, objetivo, carteras, exclusiones, tablas de beneficios y autonomía, **versionadas**.
- **UIT** como parámetro anual.
- Metas por zona y tienda.

### 4.2 Base de aptos (mensual)
- Importación (core o archivo): crédito, cliente, tipo, calificación interna y último RCC, días de atraso, situación contable, SK, intereses, diferido, moratorio, seguros, tramo, tienda, asesor, garante.
- Cálculo por crédito: exclusiones, beneficio máximo, autorizador, contador de usos 12 meses, proyección de atraso al cierre.
- Asignación a tiendas/asesores con historial. Inclusión manual solo con aprobación del Jefe.

### 4.3 Flujo del expediente
1. Gestión de contacto (visitas, llamadas, SMS, courier, promesa de pago).
2. Simulación (pago de cuotas o cancelación total): descuento máximo, monto a pagar, quién aprueba.
3. Solicitud (reemplaza el FUD por correo) con justificación de proporcionalidad.
4. Pre-validación automática (motor de reglas + IA de documentos).
5. Aprobación: aprobar / observar / rechazar; escalamiento automático.
6. Registro del acuerdo en el core por Recuperaciones; RCT guarda el monto final.
7. Ejecución en tienda por Operaciones (soporta pago en otra tienda).
8. Carga de sustentos el mismo día.
9. Revisión: CZR valida su zona; Revisores auditan.
10. Cierre y reporte mensual automático.

**Estados:** Borrador → Pre-validado → En aprobación → Observado → Aprobado → Pagado → Sustento cargado → Sustento validado → Cerrado. Además: Rechazado, Vencido (aprobación no ejecutada en X días), Anulado.

### 4.4 Validación IA de documentos (antes de subir)
- Clasificar tipo de documento.
- Extraer datos (N° crédito, DNI, montos, fechas) y cruzar con expediente y core.
- Detectar firmas presentes/faltantes por participante; alertar si difieren entre documentos del mismo cliente.
- Verificar plantilla oficial vigente; búsquedas registrales ≤ 6 meses.
- Resultado: Conforme / Observado con motivo (lo observado pasa a revisión humana).

### 4.5 Pagos fraccionados
Cronograma, alertas antes del vencimiento, control del máximo de 90 días, alerta de incumplimiento.

---

## 5. Motor de reglas

Las reglas viven como **datos versionados** por campaña. Entrada: crédito + propuesta. Salida: elegible, descuento máximo, autorizador, bloqueos y advertencias. Cada decisión se guarda con la versión de reglas usada.

| Código | Regla | Tipo |
| --- | --- | --- |
| R01 | Crédito en base de aptos o inclusión aprobada por el Jefe | Bloqueo |
| R02 | Crédito no está en exclusiones (sección 2.7) | Bloqueo |
| R03 | Operación dentro de la vigencia | Bloqueo |
| R04 | Máx. 4 descuentos en 12 meses (todas las campañas); si excede, escala | Escala |
| R05 | % intereses sobre interés + diferido, sin interés de gracia | Cálculo |
| R06 | Seguros nunca exonerados | Bloqueo |
| R07 | Cuotas: conciliado ≤ 50% de cuota pactada; > 50% solo si 2+ cuotas y queda al día, con Jefe | Escala |
| R08 | 1 cuota en mora + otra que vence en el mes: debe cerrar vigente | Condición |
| R09 | Alineamiento interno | Bloqueo salvo Jefe |
| R10 | Varios créditos en mora: advertir si se negocia solo uno | Advertencia |
| R11 | % máximo según tabla (calificación × tipo × atraso × cuotas/cancelación) | Cálculo |
| R12 | SK > 3 UIT: búsquedas registrales negativas ≤ 6 meses o INFORME + reglas judiciales | Bloqueo |
| R13 | Topes de capital por RCC (CPP 5%/25%, Deficiente 25%/60%) | Escala |
| R14 | Judicial: solo no realizables; 1er rango requiere Jefe | Escala |
| R15 | Fraccionado ≤ 90 días desde la primera amortización | Bloqueo |
| R16 | Castigada: % por antigüedad; +10 pts solo Jefatura | Escala |
| R17 | Sustento completo antes de cerrar | Bloqueo de cierre |
| R18 | Solicitante ≠ aprobador | Bloqueo |

---

## 6. Módulo 2: Programas de gobierno

Créditos con garantía de programas estatales administrados por **COFIDE** (p. ej. Reactiva Perú, FAE-MYPE, PAE-MYPE, Impulso MyPerú y otros). Riesgo central: devolver tarde o mal el dinero de COFIDE cuando el cliente paga después del honramiento.

### 6.1 Expediente de honramiento
- Por operación: programa, % cobertura, saldo a la solicitud, monto solicitado, monto honrado, fecha, estado (en preparación, enviado, observado, honrado, rechazado, pérdida de eficacia).
- Checklist de documentos configurable por programa. Avance por asesor/tienda: completo, incompleto, observado.
- Validación IA: firmas presentes, mismas firmas entre documentos, saldos del documento = saldos del core, formato oficial del programa, fechas coherentes.
- Notificación (correo y app) cuando el asesor sube documentos; recordatorio si faltan.

### 6.2 Devoluciones a COFIDE
Fórmula base (cobertura como parámetro por programa):

```
Devolución_COFIDE = Monto recuperado neto × % cobertura honrada
```

| Escenario | Qué pasa | Acción de RCT |
| --- | --- | --- |
| Pago antes de enviar solicitud de honramiento | No hay devolución | Retirar operación del lote |
| Pago con solicitud enviada, no honrada | Riesgo de honrar deuda pagada | Alerta urgente para actualizar/desistir |
| Pago parcial después del honramiento | Recupero compartido | Calcular parte de COFIDE y fecha límite |
| Cancelación total después del honramiento | Devolución según cobertura | Calcular y programar devolución |
| Garantía rechazada / pérdida de eficacia | Sale del programa | Pasa a base de la campaña |

Registro por devolución: operación, cliente, monto pagado, fecha de pago, % cobertura, monto a devolver, fecha límite, responsable, estado (pendiente, aprobada, transferida, conciliada), constancia.

**Plazos, tratamiento de intereses/gastos y orden de imputación salen del reglamento de cada programa → parámetros, nunca supuestos en código.**

### 6.3 Cuadre automático
- Diario: pagos del core sobre operaciones honradas vs. devoluciones registradas.
- Mensual con COFIDE: por devolver, devuelto, diferencias, antigüedad.
- Alertas: pago detectado en operación honrada, devolución por vencer, devolución vencida.

### 6.4 Analítica
- Distribución de pagos por día del mes (tendencia a pagar cerca del cierre).
- Probabilidad de pago post-honramiento por programa, sector, tienda, asesor.
- Proyección de devoluciones a 30/60/90 días para Tesorería.

---

## 7. Asistente IA y WhatsApp

### 7.1 Asistente (voz y texto)
- Consultas: "¿Cuánto le puedo descontar al crédito X?", "¿Qué me falta para cerrar mis expedientes?", "¿Cuánto devolvemos a COFIDE esta semana?".
- Registro asistido por dictado → borrador que el usuario confirma.
- Resúmenes: expediente para el aprobador, día por rol, campaña para gerencia.
- Redacción del Informe-Propuesta y sustento de proporcionalidad.
- Respuestas sobre lineamientos citando la sección del documento.

### 7.2 WhatsApp (WhatsApp Business API)
- Solo números registrados y vinculados a un usuario.
- Consultar estado de expediente/devolución.
- Enviar fotos/PDF de sustentos → se asocian al expediente, validación IA, respuesta conforme/observado.
- Aprobaciones rápidas de montos bajos con PIN o enlace; casos con capital solo en web.
- Resumen diario por rol.
- (Fase posterior, con consentimiento) recordatorios a clientes, acuerdo para firma digital, constancia.

---

## 8. Notificaciones, métricas y tableros

### 8.1 Notificaciones (app, correo, WhatsApp)

| Evento | Destinatario |
| --- | --- |
| Solicitud nueva | Aprobador del nivel |
| Solicitud sin atender > X horas | Aprobador + nivel superior |
| Expediente observado/aprobado/rechazado | Solicitante y Administrador de Tienda |
| Aprobación lista para cobrar | Operaciones de la tienda de pago |
| Pago registrado sobre crédito con acuerdo | Asesor y CZR |
| Sustento faltante al cierre del día | Administrador de Tienda |
| Cuota de fraccionado por vencer/incumplida | Asesor |
| Pago en operación honrada o en trámite | Revisor de programas + Jefe |
| Devolución COFIDE por vencer/vencida | Revisor, Jefe, Contabilidad |
| Documentos de programa subidos/incompletos | Revisor / Asesor |

### 8.2 Resumen diario por rol
- Asesor: clientes por contactar, promesas de hoy, observados, sustentos pendientes.
- Administrador de Tienda: pagos de hoy, sustentos por cargar, avance vs. meta.
- CZR: bandeja pendiente, validaciones de zona, tiendas rezagadas.
- Jefe de Recuperaciones: excepciones, recuperado del día, devoluciones COFIDE en riesgo.
- Gerencias: resumen semanal.

### 8.3 Métricas
- Recuperación: monto recuperado (capital, intereses), clientes normalizados, reducción de mora y provisiones, ingresos percibidos.
- Costo: condonado por concepto, % condonado/recuperado, recuperado por sol condonado.
- Calidad: recaída a 30/60/90 días, uso de excepciones, cumplimiento de fraccionados.
- Operación: tiempo de aprobación por nivel, observados por motivo, sustentos a tiempo, % documentos conformes al primer intento.
- Programas: % expedientes completos por asesor/tienda, honrado, por devolver, devuelto a tiempo, vencido.
- Cortes: zona, tienda, asesor, tipo de crédito, calificación, cartera, tramo, campaña.

---

## 9. Funcionalidades adicionales recomendadas

| Funcionalidad | Por qué |
| --- | --- |
| Simulador de impacto en provisiones | Objetivo n.° 1 de la campaña; justifica el descuento al aprobador |
| Score de priorización de cartera | A quién contactar primero |
| Control de recaída post-beneficio | Evita repetir descuentos inútiles |
| Contador global de beneficios por cliente | Hace cumplible el límite de 4 en 12 meses |
| Control de búsquedas registrales SUNARP | Vigencia de 6 meses con alertas |
| Gestión de casos judiciales | Gravamen, embargo, remate, consignaciones |
| Firma digital del acuerdo | Trazabilidad y menos firmas inconsistentes |
| Plantillas oficiales generadas por RCT | Acuerdo e Informe prellenados |
| Detección de anomalías | Señales para Auditoría (excepciones frecuentes, topes siempre al máximo, aprobaciones en minutos) |
| Metas y ranking | Promoción de la campaña |
| Base de conocimiento | Campañas, Manual, reglamentos de programas; el asistente cita fuentes |
| Gestión de locadores externos | Acceso limitado y productividad aparte |
| Calendario de cierre de mes | Alertas en los últimos 5 días |
| Modo móvil/offline para visitas | Trabajo de campo |
| Exportes para Contabilidad y SBS | Auditoría y supervisión |

---

## 10. Seguridad, auditoría y cumplimiento

- SSO con el directorio de la Caja, 2FA para aprobadores, permisos por rol + ámbito, segregación de funciones.
- Bitácora inmutable con valores antes/después.
- Documentos cifrados, versionados, con hash.
- Ley N.° 29733 de Protección de Datos Personales y su reglamento.
- La IA no usa datos de clientes para entrenar modelos externos; sus salidas se guardan como sugerencia junto a la decisión humana.
- Revisar con Riesgos y Cumplimiento los requisitos SBS aplicables (riesgo operacional, seguridad de la información).
- RCT no altera saldos; verifica que lo ejecutado en el core coincida con lo aprobado.

---

## 11. Modelo de datos principal

| Entidad | Campos clave |
| --- | --- |
| Usuario | id, nombre, cargo, roles[], ámbito, WhatsApp verificado, delegaciones |
| Tienda / Zona | código, nombre, zona, jefe zonal, coordinador zonal |
| Campaña | nombre, vigencia, carteras, versión de reglas, metas, estado |
| ReglaBeneficio | campaña, versión, calificación, tipo crédito, rango atraso, cuotas, tipo negociación, % intereses, % moratorio, % SK, rango UIT, nivel autorizador |
| ParametroAnual | año, UIT, otros |
| Cliente | DNI/RUC, nombre, calificación interna, último RCC, calificación en otras entidades |
| Credito | número, cliente, tipo, situación contable, cartera, días atraso, SK, intereses, diferido, moratorio, seguros, tienda, asesor, programa de gobierno |
| BaseApta | campaña, mes, crédito, beneficio máximo, autorizador, inclusión manual |
| Expediente | crédito(s), tipo negociación, propuesta, montos, estado, solicitante, ruta de aprobación, resultado del motor |
| Aprobacion | expediente, nivel, aprobador, decisión, comentario, fecha |
| Gestion | crédito, canal, fecha, resultado, promesa de pago |
| CuotaFraccionada | expediente, n°, fecha, monto, estado |
| Documento | expediente (campaña o programa), tipo, archivo, hash, validación IA, firmas detectadas, estado de revisión |
| BusquedaRegistral | crédito, fecha, resultado, vigencia hasta |
| CasoJudicial | crédito, rango, gravamen, embargo, base y fecha de remate, consignaciones |
| ExpedienteHonramiento | operación, programa, % cobertura, monto honrado, fechas, estado, checklist |
| PagoPosterior | operación, fecha, monto, origen |
| DevolucionCofide | operación, pago origen, monto, fecha límite, estado, constancia |
| Notificacion | usuario, evento, canal, enviado, leído |
| Bitacora | entidad, id, acción, usuario, antes, después, fecha, IP/canal |

---

## 12. Puntos ambiguos del documento de campaña (preguntar antes de implementar)

1. Límite de usos: Normal y CPP+ tienen ambos "4 veces en 12 meses"; la excepción para Normal no está definida.
2. SK exactamente = 3 UIT: las tablas solo dicen "< 3 UIT" y "> 3 UIT".
3. Topes de capital: 5.2 (CPP 5%/25%, Deficiente 25%/60%) vs. Anexo I (CPP sin capital; Deficiente/Dudoso/Pérdida 25%/50%). ¿Cuál prevalece? ¿Cómo aplica a Dudoso/Pérdida?
4. La nota del Anexo I cita el "ítem 3.1"; el límite del 50% está en el 5.1.
5. Rangos superpuestos en pago de cuotas (≥ 61 días con 1 cuota / 61–90 con ≥ 2 / ≥ 61 se pone al día). Sugerido: aplicar el mayor beneficio cuyo requisito se cumpla.
6. Refinanciados "≥ 9 a 30 días": ¿correcto o error de tipeo?
7. Hipotecarios "≥ a 61 a Días": rango incompleto.
8. Coordinador de Normalización: solicitante y aprobador de castigada/FOCMAC; ¿puede aprobar lo que propone? (Recomendado: no.)
9. Roles N4 y N5: qué aprueban exactamente.
10. Jefe Zonal aparece como nivel de autonomía en pago de cuotas (Anexo II) pero la tabla solo indica al CZR.
11. Base del 50% de la cuota: ¿incluye seguros?
12. Incumplimiento del fraccionado: qué pasa con el beneficio.

---

## 13. Roadmap sugerido

| Fase | Entregables |
| --- | --- |
| 1. Núcleo de campañas | Usuarios/roles, campaña, base de aptos, motor de reglas, expediente, aprobaciones, sustentos, bitácora, reporte mensual |
| 2. Programas de gobierno | Honramiento, checklist, pagos posteriores, devoluciones COFIDE, cuadre |
| 3. Validación IA de documentos | Clasificación, extracción, firmas, cruce con core, formato |
| 4. Notificaciones y tableros | Alertas, resúmenes por rol, métricas, metas |
| 5. Asistente y WhatsApp | Voz/texto, registro asistido, WhatsApp |
| 6. Analítica avanzada | Priorización, recaída, anomalías, proyección de devoluciones |

---

## 14. Glosario

| Término | Significado |
| --- | --- |
| RCC | Reporte Crediticio Consolidado (SBS); calificación del cliente en el sistema financiero |
| Normal / CPP / Deficiente / Dudoso / Pérdida | Categorías de calificación SBS (CPP = Con Problemas Potenciales) |
| SK | Saldo capital |
| UIT | Unidad Impositiva Tributaria (valor anual, parámetro) |
| Intereses* | Interés + interés compensatorio |
| Conciliación / exoneración / condonación | Descuento sobre intereses, moratorio o capital |
| FUD | Formato de exoneración que hoy se envía por correo |
| CZR | Coordinador Zonal de Recuperaciones |
| GCN | Gerencia Central de Negocios |
| FOCMAC | Fondo de Cajas Municipales; cartera con tabla propia |
| COFIDE | Corporación Financiera de Desarrollo; administra garantías de programas estatales |
| Honramiento | Pago de COFIDE a la Caja por la parte garantizada de un crédito impago |
| Alineamiento interno | Un cliente no debe tener un crédito vigente y otro vencido en la misma entidad |
| Búsquedas registrales | Certificados SUNARP sobre bienes inscritos |
| Castigada | Cartera retirada del balance tras provisionarse al 100% |
| Tramo Temprano y Contención | Gestión de cobranza en etapa no judicial |
| Módulo de Recuperaciones | Módulo del core al que se traslada el crédito para cancelaciones con descuento |
