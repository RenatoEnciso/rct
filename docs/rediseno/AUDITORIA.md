# Auditoría visual de RCT

> 14/10/2026 · Método: skill **impeccable** (`audit` técnico más revisión de diseño en modo *Operate*).
> Evidencia: recorrido de 56 pantallas con los usuarios ejecutivo2, czr, jefe y gerente, a 1366×768 y 1920×1080; detector `impeccable detect` sobre `resources/views` y `public/assets/css`; lectura de `rct.css` (1 309 líneas) y de los layouts.
> Capturas de referencia («antes») en `docs/rediseno/antes/`.

## Puntaje de salud

| # | Dimensión | Puntaje | Hallazgo principal |
| --- | --- | --- | --- |
| 1 | Accesibilidad | 2/4 | El gris `#8a94a3` se usa como color de texto (ayudas, subtítulos de KPI, fechas) con contraste de 3,07:1 sobre blanco y 2,81:1 sobre el fondo: no cumple AA. Además, los botones y el menú no tienen un foco de teclado propio |
| 2 | Rendimiento | 3/4 | Todo es local y liviano. Bootstrap completo (≈230 KB) se carga para usar pocas utilidades, y parte de él se pisa con clases propias |
| 3 | Tokens y tema | 1/4 | Hay tokens de color, pero 54 colores distintos escritos a mano en el CSS, 26 tamaños de letra, 14 radios y 14 sombras distintas |
| 4 | Adaptación a pantallas | 2/4 | A 1366 px: KPIs cortados con «…», filtros que se montan sobre el botón, montos que saltan de línea y el botón «Asistente» tapando acciones de las tablas |
| 5 | Integridad del sistema | 1/4 | Conviven dos sistemas visuales (el «base» y el de analítica `cc-*`) y cada módulo nuevo agregó el suyo (`pr-`, `rc-`, `al-`, `lt-`, `rv-`, `mx-`, `dv-`) |
| **Total** | | **9/20** | **Pobre: necesita una revisión de fondo**, aunque el producto funciona y se entiende |

## Veredicto de integridad

**No pasa.** Cada pantalla, vista por separado, se ve cuidada. Juntas no parecen un solo sistema:

- Hay tres maneras de mostrar un KPI:
  - tarjeta con franja lateral de color (Reporte de campaña, Mi panel);
  - tarjeta con ícono en recuadro (Centro de control, Pagos, Lotes);
  - número suelto dentro de una tarjeta de sección (Recaída).
- El mismo estado se pinta distinto según el módulo: badge, «semáforo», chip o franja.
- El detector encontró 9 hallazgos:
  - franjas laterales gruesas como acento (`side-tab`): 3 casos;
  - bordes superiores de 4 px sobre tarjetas redondeadas: 2 casos;
  - una animación con rebote: 1 caso;
  - fuera de RCT: Breeze, `welcome` y la demo de WhatsApp.

## Resumen

- **Problemas por severidad:**

  | Severidad | Cantidad |
  | --- | --- |
  | P0 | 0 |
  | P1 | 7 |
  | P2 | 9 |
  | P3 | 4 |

- **Los cinco más importantes:**
  1. Texto gris sin contraste suficiente.
  2. Datos cortados o descuadrados a 1366 px.
  3. El asistente tapa acciones.
  4. Dos sistemas visuales en paralelo.
  5. Tarjetas dentro de tarjetas y adornos que compiten con los datos.

## Hallazgos

### P1 · Importantes

1. **Texto gris ilegible (contraste 2,8–3,1:1).**
   - Dónde: `--gris-claro` en `.kpi-sub`, `.field-hint`, `.cc-kpi-sub`, `.notif-time`, `.pr-lbl`, `.dv-cifra-sub`, `.cc-rango-escala` y los rótulos del menú.
   - Impacto: los subtítulos que explican cada cifra son los primeros que se pierden en una laptop o un proyector.
   - Norma: WCAG 1.4.3.
   - Arreglo: usar `#8a94a3` solo para íconos, bordes y texto sobre fondo oscuro (5,09:1 sobre `#1c2430`). Todo texto secundario sobre claro pasa a `#5f6b7a` (5,43:1).
2. **Montos cortados en los KPI a 1366 px.**
   - Dónde: Lote 010 («S/ 4 866 377,…», «S/ 4 592 442,…»), `.cc-kpi-valor` con `text-overflow: ellipsis`.
   - Impacto: la cifra principal de la pantalla queda incompleta.
   - Arreglo: tamaño del valor según el ancho de la grilla, sin cortar texto; la grilla pasa a menos columnas antes de truncar.
3. **Filtros descuadrados.**
   - Dónde: Programas de gobierno → Expedientes. A 1366 px el segundo campo de fecha se monta bajo el botón «Filtrar». En Pagos, «Buscar» cae solo a una segunda fila.
   - Arreglo: una barra de filtros con grilla propia y acciones alineadas al final.
4. **El botón «Asistente» tapa contenido.**
   - Dónde: `.asistente-fab`, fijo abajo a la derecha. Tapa el «Revisar» de la quinta fila de la Bandeja y la columna derecha de las tablas.
   - Arreglo: botón compacto (solo ícono) con margen reservado, o integrado a la barra superior.
5. **Sin foco de teclado consistente.**
   - Dónde: `.btn`, `.nav-btn`, `.im-tabs a`, `.cc-segmento a` redefinen el estilo de Bootstrap sin un `:focus-visible` propio.
   - Norma: WCAG 2.4.7.
   - Arreglo: un anillo de foco único, rojo, de 2 px con separación, para todo control.
6. **Dos sistemas de componentes.**
   - Dónde: `.card` y `.kpi-card` (radio 8, sombra mínima) frente a `.cc-card`, `.cc-kpi`, `.pr-card`, `.al-sev-card`, `.dv-cifra` y `.rv-estado`. Estos usan radio 10–14, otra sombra (`--cc-sombra`) y otro borde (`#e9ecef`).
   - Impacto: al pasar de Bandeja a Centro de control parece otra aplicación.
   - Arreglo: una sola familia de superficies y un solo componente de KPI.
7. **Montos que saltan de línea y formato de moneda mixto.**
   - Dónde: Bandeja («S/» en una línea y «147,541.03» en otra) y Trazabilidad. Conviven «S/ 40,889.31», «S/ 40 889» y «S/ 2 700,00».
   - Arreglo: montos sin salto de línea y alineados a la derecha con cifras tabulares. El formato numérico de cada vista se respeta, porque viene del cálculo.

### P2 · Menores

1. **Tarjetas dentro de tarjetas.** «Mis pendientes» y «Accesos rápidos» (Inicio), firmas del lote y documentos de la Revisión de sustentos. Se reemplazan por filas o celdas divididas por líneas.
2. **Franjas laterales de color como acento** (detector `side-tab`). Están en `.kpi-card::before`, `.al-item::before`, `.toast-rct` (4 px), `.quote` y el ítem activo del menú, y en los bordes superiores de 4 px de `.rv-estado` y `.dv-item`. El estado pasa a un indicador propio, sin franjas.
3. **Rótulos encima del título («eyebrow»).** «Analítica», «Modelo de priorización», «Impulso MyPerú · Etapa 5», «Analítica · Detección de anomalías». Repiten lo que ya dice el menú. Donde hay jerarquía real se convierten en migas de pan; los demás se eliminan.
4. **Íconos en recuadro de color sobre cada KPI** (`.cc-kpi-ico`, `.tile-ico`, `.al-sev-ico`). Son decoración que compite con la cifra; se reducen a la etiqueta.
5. **Degradados decorativos.** Portada de Inicio (degradado y brillo radial), `.cc-sim`, `.rc-hero`, `.dv-item.en-curso` y login.
6. **Menú lateral largo y con saltos de línea.** «Bandeja de aprobación» ocupa dos líneas por el contador. A 1366×768 el menú del jefe (22 entradas) no entra y obliga a desplazarse.
7. **Encabezado duplicado.** El nombre y el rol del Jefe dicen lo mismo («Jefe de Recuperaciones» dos veces); la barra superior se lleva 60 px y repite la marca que no aporta.
8. **Selects nativos sin estilo** fuera de `.field` (`.cc-select`, filtros de Recaída y Alertas): flecha y alto distintos al resto.
9. **Animación con rebote** en la diferencia del cruce TXT (`cubic-bezier(.2,.9,.3,1.4)`) y una entrada en cascada en cada tarjeta de analítica. En un sistema de trabajo el movimiento debe indicar estado, no decorar.

### P3 · Pulido

1. Scrollbars, selección de texto y caret con los colores del navegador.
2. Tamaños de 10 a 11,5 px en rótulos de tabla y contadores; el mínimo legible es 12 px.
3. Estados vacíos dispares (algunos con ícono, otros solo texto).
4. Bootstrap y `rct.css` definen `.btn`, `.card`, `.badge` y `.table` a la vez; algunos estilos dependen del orden de carga.

## Lo que funciona y se conserva

- La paleta institucional y los colores de estado (fijos por pedido de la Jefatura) son sobrios y tienen buen contraste en los badges: de 4,8:1 a 7,4:1.
- Inter local, cifras tabulares en casi todos los montos, migas de pan en las pantallas de detalle.
- Modo presentación, avisos en vivo, simulador y cruce TXT animado: son momentos propios del producto.
- Cero errores de JavaScript y cero desbordes horizontales de página en las 56 pantallas recorridas.

## Qué se va a cambiar

Dirección elegida (modo *Operate*): **«Ciudadela»**. Se inspira en las ciudadelas de Chan Chan, en Trujillo, que eran recintos administrativos donde los patios con muros controlaban el acceso a los depósitos. RCT hace lo mismo con propuestas, sustentos y devoluciones: controla cada paso. Del mundo de referencia se toma solo la disciplina, nunca adornos ni motivos:

1. **Riel oscuro y plano de trabajo claro.**
   - El menú pasa a un riel tinta `#1c2430` de alto completo, con la marca CT arriba.
   - La barra superior queda blanca y delgada, con la ubicación, la campana y el usuario.
   - El rojo queda reservado para la acción principal, la selección y el foco.
2. **Patios, no tarjetas flotantes.**
   - Las secciones son superficies blancas delimitadas por muros de 1 px y sin sombras apiladas.
   - Las tablas, los KPI y los formularios viven dentro del patio, divididos por líneas, sin tarjetas anidadas.
3. **Friso de etapas como sello del sistema.**
   - Es una banda de módulos cuadrados, uno por etapa del expediente. Cada módulo se rellena con su color de estado al completarse, y la etapa actual se marca en rojo.
   - Unifica en un solo componente los pasos del lote, el estado del expediente y la trazabilidad.
4. **Escala tipográfica fija.**
   - 12 · 13 · 14 · 16 · 20 · 24 · 32 px (razón ≈1,2), en Inter, con cifras tabulares y montos alineados a la derecha sin salto de línea.
5. **Un solo sistema de tokens:**
   - rampa de neutros definida una vez;
   - radios 4 · 6 · 8;
   - dos sombras, solo para elementos que flotan (menús, modales, avisos);
   - ritmo de espaciado de 4 px;
   - un foco único.
6. **Componentes unificados:**
   - un KPI;
   - un badge de estado;
   - una barra de filtros;
   - una tabla densa;
   - un estado vacío;
   - un aviso;
   - el friso.
7. **Disciplinas incorporadas de las alternativas descartadas:**
   - conectores rectos en líneas de tiempo;
   - un solo acento;
   - líneas de exactamente 1 px y tres tintas de texto;
   - barras proporcionales al valor real;
   - cifras alineadas a la derecha;
   - una sola rampa tonal.

Orden de trabajo (un commit por paso):

1. Esta auditoría.
2. Sistema de diseño (tokens y componentes en `rct.css`, documentado en `docs/SISTEMA_DISENO.md`).
3. Layout general y login.
4. Pantallas clave.
5. Resto de pantallas.
6. Pulido final.
