# RCT — Pantallas clave para el video y las capturas

> Recomendación basada en lo que existe hoy en el sistema.
> Cifras de la base de demo regenerada con `rct:demo-reset`, con corte al 12/10/2026.
> La contraseña de todos los usuarios es `password`.
> Si se regenera la demo otro día, las fechas se desplazan y algunas cifras pueden variar.

---

## 1. Mensaje principal

**"RCT convierte la cobranza de la Caja en una gestión priorizada, controlada y medida en soles: se recupera más, se libera provisión y se detectan los riesgos antes de que cuesten."**

Versión corta para el cierre del video: **"Recuperar más, con control."**

---

## 2. Top 8 pantallas "main"

Ordenadas por impacto.

### 1. Centro de control

| | |
|---|---|
| **Ruta** | `/centro-control` (menú Analítica → Centro de control) |
| **Usuario y estado** | `gerente@cajatrujillo.pe`. Periodo **Año 2026**, **Todas las zonas**. Simulador en 30 %. |
| **Antes** | El reporte de campaña tarda 2 días hábiles y se arma a mano en Excel; nadie ve en un solo lugar recupero, provisión y costo del beneficio. |
| **Dato más fuerte** | **S/ 7 005 140 recuperados en 2026** y **S/ 6 159 702 de provisión liberada**. **S/ 1.36 recuperados por cada S/ 1 condonado**. Promedio mensual en campaña **+106 %** frente a enero–agosto: septiembre es el mejor mes del año, con S/ 1.38 M. |
| **Qué destacar** | Los contadores animados al cargar (empezar la toma con la página recién abierta) y las barras de avance contra la meta por zona: 4 de 5 zonas superan la meta, y Sur va en 121.5 %. |
| **Modo presentación** | **Sí.** Es la toma de apertura y de cierre. |

### 2. Simulador de impacto

| | |
|---|---|
| **Ruta** | `/centro-control`, bloque "Simulador de impacto" |
| **Usuario y estado** | `gerente@cajatrujillo.pe`, todas las zonas. Partir en 20 % y deslizar hasta 50 %. Terminar en **30 %**. |
| **Antes** | La cartera se gestiona en el orden en que llega la base de aptos. No hay forma de estimar qué se gana priorizando. |
| **Dato más fuerte** | Gestionando el 30 % de la cartera pendiente (**359 de 1 197 créditos**) en el orden del modelo: **S/ 3 825 646 de recupero esperado**, **+74.4 % (+S/ 1 632 352)** frente al orden de la base, y **S/ 2 218 769 de provisión liberada**. |
| **Qué destacar** | El movimiento del deslizador: la curva roja se separa de la gris y los números cambian en vivo. Es el momento "wow". |
| **Modo presentación** | **Sí.** |

### 3. Mi prioridad de hoy → Nueva propuesta

| | |
|---|---|
| **Ruta** | `/mi-prioridad` (menú Gestión de campaña → Mi prioridad de hoy) y luego el botón **Gestionar** |
| **Usuario y estado** | `ejecutivo2@cajatrujillo.pe` (María Fernanda Castillo, Chiclayo). Filtro **Todos**. |
| **Antes** | El asesor elige a quién llamar por intuición o por antigüedad, arma el FUD en un formato aparte y lo envía por correo eligiendo él mismo al aprobador. |
| **Dato más fuerte** | **86 créditos pendientes en su cartera (16 de prioridad alta)**. Los 20 de la lista suman **S/ 240 724 de recupero esperado**. El n.º 1 es **Guajardo Gómez Sara Hidalgo** (puntaje 77), con acción sugerida y mejor horario. |
| **Qué destacar** | Los anillos de puntaje que se llenan al cargar y los motivos como chips. Después, **clic en "Gestionar"** de la primera tarjeta: Nueva propuesta se abre con el cliente cargado, el aviso del modelo y el **aprobador asignado automáticamente** (Jefe de Recuperaciones). |
| **Modo presentación** | **Sí** para las tarjetas. **No** para Nueva propuesta: conviene que se vea el menú del asesor. |

### 4. Análisis de recaída

| | |
|---|---|
| **Ruta** | `/recaida` (menú Analítica → Análisis de recaída) |
| **Usuario y estado** | `jefe@cajatrujillo.pe`, **Todas las zonas**. |
| **Antes** | Se otorgan beneficios sin saber si funcionaron. Nadie mide si el cliente vuelve a atrasarse. |
| **Dato más fuerte** | **74.8 % de recuperación sostenida a 90 días** (147 beneficios con 90 días cumplidos; 95.5 % al día a 30 días). Los clientes que **se ponen al día recaen 2.9 veces menos** que los que pagan solo algunas cuotas: **13.8 % frente a 39.5 %**. |
| **Qué destacar** | El número grande, el contador y la caja de lectura "recaen 2.9 veces menos". Complemento: el aviso en Nueva propuesta con `ejecutivo2` en `/fud/crear?cliente=1108` ("Este cliente recayó tras su último beneficio, hace 38 días"). |
| **Modo presentación** | **Sí** para la pantalla de recaída. |

### 5. Alertas de control

| | |
|---|---|
| **Ruta** | `/alertas` (bandeja) y `/alertas/1` (detalle) |
| **Usuario y estado** | `jefe@cajatrujillo.pe`. Bandeja en **Abiertas**: 9 alertas (4 alta, 3 media, 2 baja). Detalle de la alerta "Descuento al tope" de Diego Alonso Vásquez. |
| **Antes** | Las anomalías solo se ven en auditorías posteriores, revisando expedientes uno por uno. |
| **Dato más fuerte** | Diego Alonso Vásquez (Piura) propuso el **descuento máximo en 23 de 23 propuestas (100 % frente a 29.3 % de su zona)**. El coordinador de Centro Oriente aprueba las propuestas de un mismo asesor en **6 minutos, frente a 49.9 horas** con el resto. |
| **Qué destacar** | Clic en la tarjeta "Severidad alta" (filtra la bandeja), luego el detalle con el gráfico "esta persona vs. promedio de la zona". Elegir "Confirmada", escribir un comentario y guardar: aparece en el historial. |
| **Modo presentación** | **No.** La bandeja se entiende mejor con el contador del menú; el detalle tiene un formulario. |

### 6. Carga de sustentos con validación inteligente

| | |
|---|---|
| **Ruta** | `/sustentos` (menú Gestión de campaña → Carga de sustentos) |
| **Usuario y estado** | `ejecutivo1@cajatrujillo.pe`. Abrir un sustento pendiente y subir los archivos de prueba preparados según `docs/REGLAS_DEMO_VALIDACION.md`: uno conforme, uno con monto que no coincide, uno ilegible y uno sin firma. |
| **Antes** | Los sustentos llegan por un link compartido y se revisan a mano. Los errores (firma, monto, documento ilegible) se descubren días después. |
| **Dato más fuerte** | Cada documento muestra "Analizando…" y en segundos queda conforme, observado o rechazado con el hallazgo concreto. En la demo hay 355 sustentos aprobados y 17 observados. |
| **Qué destacar** | La animación de análisis y los resultados de distinto color apareciendo uno tras otro. |
| **Modo presentación** | **No** (es una pantalla de trabajo). Tomar solo el panel de carga, de cerca. |

### 7. Programas de gobierno: bandeja e indicadores de honramiento

| | |
|---|---|
| **Ruta** | `/gobierno` (bandeja) y `/gobierno/indicadores` |
| **Usuario y estado** | `jefe@cajatrujillo.pe`, sin filtros. Opcional: mostrar **Importar Excel** con la vista previa. |
| **Antes** | La revisión de expedientes de COFIDE vive en un Excel del área, con estado y faltantes escritos a mano. |
| **Dato más fuerte** | **150 expedientes, S/ 13 553 426 honrados**. **46.7 % completos** con los documentos que exige COFIDE (70) y 29 conformes en revisión central. El mismo 46.7 % aparece en el Centro de control. |
| **Qué destacar** | Los íconos ✓ ✗ por documento en la bandeja, y en indicadores el ranking de tiendas con más faltantes y "documentos que más faltan". |
| **Modo presentación** | **Sí** para indicadores. **No** para la bandeja. |

### 8. Priorización de cartera (mapa de calor)

| | |
|---|---|
| **Ruta** | `/priorizacion` (menú Analítica → Priorización de cartera) |
| **Usuario y estado** | `jefe@cajatrujillo.pe`, todas las zonas. |
| **Antes** | La Jefatura no sabe dónde está la cartera con mejor posibilidad de recupero ni cuánto de ella ya se trabajó. |
| **Dato más fuerte** | **725 créditos prioritarios pendientes (177 de prioridad alta) con S/ 5 944 044 de recupero esperado**. Solo el **15.6 % de la cartera prioritaria ya tiene propuesta** en campaña. **Norte concentra S/ 2.28 M.** |
| **Qué destacar** | El mapa de calor: la celda más intensa es Norte en prioridad media. Luego la columna "Cartera prioritaria gestionada". |
| **Modo presentación** | **Sí.** |

---

## 3. Los 5 insights que más venden

Las cinco cifras son coherentes entre sí y con las pantallas de origen.

| # | Insight | Pantalla de origen | Coherencia |
|---|---|---|---|
| 1 | **Priorizar rinde +74 %**: gestionando 359 de 1 197 créditos pendientes en el orden del modelo se esperan **S/ 3.83 M**, frente a S/ 2.19 M en el orden de la base (**+S/ 1.63 M**). | Centro de control → Simulador | Los 1 197 pendientes son los mismos del mapa de calor de Priorización. |
| 2 | **La campaña duplicó el ritmo**: el recupero mensual en campaña es **+106 %** frente a enero–agosto. Septiembre es el mejor mes del año (**S/ 1.38 M**). En el año suman **S/ 7.0 M recuperados y S/ 6.16 M de provisión liberada**. | Centro de control (indicadores y tendencia mensual) | Las tiendas líderes (Pucallpa S/ 765 mil, Montenegro S/ 678 mil) suman sobre el mismo total. |
| 3 | **El beneficio paga**: por cada **S/ 1 condonado se recuperan S/ 1.36**, y además se libera **S/ 1.20 de provisión**. | Centro de control | S/ 7 005 140 recuperados / S/ 5 154 384 condonados; S/ 6 159 702 de provisión liberada. |
| 4 | **Ponerse al día funciona**: el **74.8 %** sigue al día a los 90 días; quienes se ponen al día recaen **2.9 veces menos** (13.8 % frente a 39.5 %). | Análisis de recaída | El mismo 74.8 % aparece en la tarjeta de analítica del Centro de control. |
| 5 | **Control en tiempo real**: **9 alertas abiertas (4 de severidad alta)**. Un asesor propone el descuento máximo en el 100 % de sus casos (zona: 29.3 %) y un aprobador resuelve en 6 minutos lo que al resto le toma ~50 horas. | Alertas de control | El 9 coincide con el contador del menú y con la tarjeta de alertas del Centro de control. |

Insight de respaldo, si hay tiempo:

- **Queda mucho por ganar.** Solo el 15.6 % de la cartera prioritaria tiene propuesta; quedan **725 créditos** con **S/ 5.94 M** de recupero esperado (Priorización y Centro de control).

---

## 4. Guion del video (≈ 1 min 58 s)

Historia: **problema → solución → resultado**.

| # | Tiempo | Pantalla | Acción | Texto en pantalla (máx. 8 palabras) | Transición |
|---|---|---|---|---|---|
| 1 | 0:00–0:09 | Inicio (`/`, jefe) desenfocado, con íconos de correo y Excel superpuestos | Fondo quieto; el texto entra en dos líneas | **Correos, Excel y reportes de dos días** | Fundido a negro |
| 2 | 0:09–0:19 | Centro de control, modo presentación (gerente) | Página recién cargada: contadores subiendo y tarjetas apareciendo | **Toda la recuperación en una pantalla** | Zoom lento a "Monto recuperado" |
| 3 | 0:19–0:29 | Mi prioridad de hoy (ejecutivo2) | Las tarjetas aparecen y los anillos se llenan; zoom a la tarjeta #1 y sus chips | **Cada asesor sabe a quién gestionar hoy** | Corte directo |
| 4 | 0:29–0:39 | Nueva propuesta con el cliente cargado | Clic en "Gestionar"; se ve el aviso del modelo y el aprobador asignado | **Propuesta lista y aprobador asignado automáticamente** | Barrido lateral |
| 5 | 0:39–0:49 | Carga de sustentos (ejecutivo1) | Subir 4 archivos; "Analizando…" y resultados de distinto color | **Documentos validados en segundos** | Corte directo |
| 6 | 0:49–0:59 | Programas de gobierno → Indicadores | Paneo por los KPIs y el ranking de tiendas con faltantes | **Expedientes COFIDE bajo control, sin Excel** | Fundido |
| 7 | 0:59–1:11 | Centro de control → Simulador (modo presentación) | Deslizar de 20 % a 50 % y volver a 30 % | **Priorizando: +74 % de recupero** | Zoom a la cifra "+S/ 1 632 352" |
| 8 | 1:11–1:21 | Análisis de recaída (modo presentación) | Contador del 74.8 % y luego zoom a la caja de lectura | **Ponerse al día: 3 veces menos recaída** | Corte directo |
| 9 | 1:21–1:32 | Alertas de control → detalle de Diego Alonso Vásquez | Clic en "Severidad alta", abrir la alerta y mostrar el gráfico | **Alertas antes de que cuesten** | Corte directo |
| 10 | 1:32–1:42 | Detalle de alerta | Elegir "Confirmada", escribir el comentario y guardar; aparece en el historial | **Cada decisión queda registrada** | Fundido |
| 11 | 1:42–1:52 | Centro de control, modo presentación | Toma amplia del avance de metas por zona y de "S/ 1.36 por S/ 1 condonado" | **S/ 7 millones recuperados, S/ 6 millones liberados** | Fundido a fondo rojo institucional |
| 12 | 1:52–1:58 | Pantalla de cierre (fondo rojo, logo RCT) | Logo y frase | **RCT: recuperar más, con control** | Fin |

Notas de producción:

- Grabar a **1920×1080** y en Chrome sin barras: F11, y modo presentación donde se indica.
- Abrir cada pantalla **recién cargada** para captar las animaciones; duran entre 1 y 1.5 segundos.
- La escena 1 es de edición (imagen y texto). No hace falta una pantalla nueva.

---

## 5. Pantallas que no recomiendo mostrar

| Pantalla | Por qué no |
|---|---|
| **Reporte de campaña** (`/reportes/dashboard`) e **Indicadores** (`/indicadores`) | Son los tableros anteriores a la analítica: cuentan FUD, no soles, y su lógica no se revisó contra la nueva capa. Pueden mostrar cifras que no cuadran con el Centro de control. El Centro de control las reemplaza para la presentación. |
| **Mi panel** (`/panel`) | Resumen operativo anterior. No aporta frente al Centro de control ni a "Mi prioridad de hoy". |
| **Lineamientos** (`/lineamientos`) | Tabla y calculadora técnica. Es útil en una demo en vivo, pero no vende en un video. |
| **Seguimiento** (`/seguimiento`) | Bitácora en tabla plana. Mejor contar la trazabilidad con el historial de una alerta o con la trazabilidad de un expediente. |
| **Canal WhatsApp** (`/whatsapp`) | Es una réplica visual del canal, sin integración real con WhatsApp Business. Puede interpretarse como algo ya conectado o como una maqueta. Mostrarla solo en demo en vivo y aclarando que es la vista del canal. |
| **Lotes y plazos** (`/gobierno/lotes`) e **Importar Excel** | Son pantallas de configuración y de carga. Correctas, pero sin impacto visual. La importación puede mencionarse en una demo en vivo. |
| **Centro de control filtrado por zona (vista CZR)** | La tendencia de octubre muestra el mes incompleto frente a la meta del mes completo y parece una caída (ver ajuste 1). |
| **Inicio de sesión** | Queda para el rediseño gráfico que hará el equipo. |

---

## 6. Ajustes rápidos antes de tomar capturas

No están hechos; solo se listan.

| # | Ajuste | Pantalla | Esfuerzo |
|---|---|---|---|
| 1 | En la tendencia mensual, octubre compara 12 días de recupero (S/ 460 mil) con la meta del mes completo (S/ 1.28 M): parece una caída. Prorratear la meta del mes en curso o marcar la barra como "en curso". | Centro de control | Bajo (15 min) |
| 2 | Hay usuarios con nombres genéricos que salen en pantalla: "Coordinador Zonal" (czr@) en la alerta de aprobación en minutos y "Ejecutivo 1" (ejecutivo1@) en la lista de riesgo de recaída. Darles nombres ficticios realistas. | Alertas, Recaída, encabezado | Bajo (10 min + `rct:demo-reset`) |
| 3 | Los contadores de notificaciones (57 en jefe, 19 en ejecutivo2) y de bandeja (43) saturan las tomas con menú. Marcar las notificaciones como leídas antes de grabar, o grabar en modo presentación. | Todas las pantallas con menú | Sin código (5 min) |
| 4 | El botón flotante "Asistente" tapa la última columna de algunas tablas (recupero esperado en Priorización, probabilidad en Recaída). Ocultarlo en las capturas sin modo presentación. | Priorización, Recaída, Alertas | Bajo (10 min) |
| 5 | La "provisión liberable" del mapa de calor (S/ 15.8 M) no se pondera por probabilidad, mientras el recupero esperado sí. Puede confundir al compararla con el simulador. Usar la provisión esperada (× probabilidad) o aclarar el rótulo. | Priorización | Bajo (15 min) |
| 6 | En el detalle de "Excepciones concentradas" las otras tiendas de la zona salen en 0 % y el gráfico se ve casi vacío. Comparar con todas las tiendas o añadir la barra del promedio nacional. | Alerta de excepciones | Bajo (20 min) |
| 7 | "Tiendas con mayor recupero" usa el periodo elegido (Año 2026) y "Avance frente a la meta" usa la campaña. Indicar el periodo en ambos títulos para evitar preguntas. | Centro de control | Muy bajo (5 min) |
| 8 | En la vista CZR del simulador, el recuadro de ventaja corta el texto en tres líneas a 1366 px. Acortar el texto o apilar el bloque antes. | Centro de control (laptop) | Muy bajo (10 min) |
| 9 | Preparar y dejar a mano los 4 archivos de prueba de la validación (conforme, monto distinto, ilegible y sin firma) con los marcadores internos, para que la toma salga a la primera. | Carga de sustentos | Sin código (10 min) |
| 10 | Antes de grabar, ejecutar `rct:demo-reset` el mismo día, para que "Mi prioridad de hoy", los días de atraso y las fechas relativas coincidan con la fecha de grabación. Después revisar que las cifras de este documento se mantengan. | Todas | Sin código (1 min + revisión) |
