---
name: RCT · Sistema de Recuperaciones
description: Sistema interno de Recuperaciones de Caja Trujillo; diseño «Ciudadela», patios con muros de 1 px sobre un solo plano y el friso de etapas como sello.
colors:
  rojo: "#a3182c"
  rojo-dark: "#7d1221"
  rojo-soft: "#fbf0f1"
  rojo-line: "#efd2d6"
  tinta: "#1c2430"
  gris: "#5f6b7a"
  gris-claro: "#8a94a3"
  borde-fuerte: "#cfd4da"
  borde: "#e3e6ea"
  linea: "color-mix(in srgb, #e3e6ea 55%, #fff)"
  fondo: "#f4f5f7"
  sutil: "#f8f9fa"
  superficie: "#ffffff"
  riel-texto: "#cfd4da"
  verde: "#1f7a4d"
  verde-soft: "#ecf6f0"
  verde-line: "#c6e3d2"
  ambar: "#a15c07"
  ambar-soft: "#fdf5e7"
  ambar-line: "#f1dbb1"
  ambar-barra: "#d08a1e"
  azul: "#1f4e8c"
  azul-soft: "#edf2f9"
  azul-line: "#c9d7ec"
  peligro: "#b42318"
  peligro-soft: "#fdf0ef"
  peligro-line: "#f3cdc9"
typography:
  display:
    fontFamily: "Inter, system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif"
    fontSize: "56px"
    fontWeight: 700
    lineHeight: 1.05
    letterSpacing: "-0.03em"
    fontFeature: "'tnum', 'cv11', 'ss01'"
  headline:
    fontFamily: "Inter, system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif"
    fontSize: "24px"
    fontWeight: 600
    lineHeight: 1.25
    letterSpacing: "-0.015em"
  figure:
    fontFamily: "Inter, system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif"
    fontSize: "24px"
    fontWeight: 700
    lineHeight: 1.15
    letterSpacing: "-0.02em"
    fontFeature: "'tnum', 'cv11', 'ss01'"
  title:
    fontFamily: "Inter, system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif"
    fontSize: "14px"
    fontWeight: 600
    lineHeight: 1.35
  body:
    fontFamily: "Inter, system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif"
    fontSize: "14px"
    fontWeight: 400
    lineHeight: 1.5
    fontFeature: "'cv11', 'ss01'"
  ui:
    fontFamily: "Inter, system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif"
    fontSize: "13px"
    fontWeight: 500
    lineHeight: 1.35
  label:
    fontFamily: "Inter, system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif"
    fontSize: "12px"
    fontWeight: 600
    lineHeight: 1.4
    letterSpacing: "0.01em"
  group-label:
    fontFamily: "Inter, system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif"
    fontSize: "12px"
    fontWeight: 600
    letterSpacing: "0.06em"
rounded:
  modulo: "2px"
  sm: "4px"
  md: "6px"
  lg: "8px"
spacing:
  s-1: "4px"
  s-2: "8px"
  s-3: "12px"
  s-4: "16px"
  s-5: "20px"
  s-6: "24px"
  s-8: "32px"
  s-10: "40px"
  s-12: "48px"
components:
  button-primary:
    backgroundColor: "{colors.rojo}"
    textColor: "{colors.superficie}"
    typography: "{typography.ui}"
    rounded: "{rounded.md}"
    padding: "7px 14px"
    height: "36px"
  button-primary-hover:
    backgroundColor: "{colors.rojo-dark}"
    textColor: "{colors.superficie}"
  button-secondary:
    backgroundColor: "{colors.superficie}"
    textColor: "{colors.tinta}"
    rounded: "{rounded.md}"
    padding: "7px 14px"
    height: "36px"
  button-secondary-hover:
    backgroundColor: "{colors.sutil}"
    textColor: "{colors.tinta}"
  button-ghost:
    backgroundColor: "transparent"
    textColor: "{colors.gris}"
    rounded: "{rounded.md}"
    padding: "7px 14px"
    height: "36px"
  button-success:
    backgroundColor: "{colors.verde}"
    textColor: "{colors.superficie}"
    rounded: "{rounded.md}"
    padding: "7px 14px"
    height: "36px"
  button-warning:
    backgroundColor: "{colors.superficie}"
    textColor: "{colors.ambar}"
    rounded: "{rounded.md}"
    padding: "7px 14px"
    height: "36px"
  button-danger:
    backgroundColor: "{colors.superficie}"
    textColor: "{colors.peligro}"
    rounded: "{rounded.md}"
    padding: "7px 14px"
    height: "36px"
  input:
    backgroundColor: "{colors.superficie}"
    textColor: "{colors.tinta}"
    rounded: "{rounded.md}"
    padding: "7px 11px"
    height: "36px"
  input-readonly:
    backgroundColor: "{colors.sutil}"
    textColor: "{colors.tinta}"
  patio:
    backgroundColor: "{colors.superficie}"
    textColor: "{colors.tinta}"
    rounded: "{rounded.lg}"
    padding: "{spacing.s-5}"
  kpi-celda:
    backgroundColor: "{colors.superficie}"
    textColor: "{colors.tinta}"
    typography: "{typography.figure}"
    padding: "16px 20px"
  badge-ok:
    backgroundColor: "{colors.verde-soft}"
    textColor: "{colors.verde}"
    rounded: "{rounded.sm}"
    padding: "2px 8px"
  badge-pending:
    backgroundColor: "{colors.ambar-soft}"
    textColor: "{colors.ambar}"
    rounded: "{rounded.sm}"
    padding: "2px 8px"
  badge-danger:
    backgroundColor: "{colors.peligro-soft}"
    textColor: "{colors.peligro}"
    rounded: "{rounded.sm}"
    padding: "2px 8px"
  badge-info:
    backgroundColor: "{colors.azul-soft}"
    textColor: "{colors.azul}"
    rounded: "{rounded.sm}"
    padding: "2px 8px"
  badge-gray:
    backgroundColor: "{colors.sutil}"
    textColor: "{colors.gris}"
    rounded: "{rounded.sm}"
    padding: "2px 8px"
  nav-item:
    backgroundColor: "{colors.tinta}"
    textColor: "{colors.riel-texto}"
    rounded: "{rounded.md}"
    padding: "6px 10px"
    height: "34px"
  nav-item-active:
    backgroundColor: "{colors.rojo}"
    textColor: "{colors.superficie}"
  friso-modulo:
    backgroundColor: "{colors.superficie}"
    rounded: "{rounded.modulo}"
    size: "12px"
  friso-modulo-actual:
    backgroundColor: "{colors.rojo}"
  friso-modulo-completo:
    backgroundColor: "{colors.tinta}"
---

# Design System: RCT · Sistema de Recuperaciones

> Fuente única de tokens y componentes: `public/assets/css/rct.css`. Documento humano equivalente (no normativo para máquinas): `docs/SISTEMA_DISENO.md`. Si este archivo y el CSS difieren, manda el CSS.

## Overview

**Creative North Star: "La Ciudadela"**

RCT se construye como una ciudadela administrativa inspirada en Chan Chan: cada sección es un **patio** blanco delimitado por **muros de 1 px** sobre un único **plano** gris claro. No hay tarjetas flotando: la profundidad se declara una vez, con el muro. A la izquierda, el **riel** en tinta recorre la pantalla a alto completo con la marca CT; arriba, una barra blanca de 56 px con la fecha, el asistente, la campana y el usuario. El estado de cada expediente se lee en el **friso**, una banda de módulos cuadrados, uno por etapa: es el sello del sistema.

Es una herramienta de trabajo diario para asesores, CZR, Jefatura y Gerencia, en escritorio (1920 y 1366 px). La densidad es alta pero ordenada: el usuario ve primero lo que debe decidir hoy, lee cada cifra de un vistazo (tabular, a la derecha, nunca truncada) y puede seguir un expediente hasta su cierre. El rojo institucional es escaso a propósito: marca la acción principal, la selección y el foco; los colores de estado aparecen solo donde significan algo.

Rechaza explícitamente la grilla gris de tarjetas blancas redondeadas con sombra, los KPIs con ícono dentro de un recuadro de color, las franjas laterales gruesas como acento y las coreografías de entrada.

**Key Characteristics:**
- Un solo plano (`fondo`) con patios blancos y muros de 1 px; sin sombras en reposo.
- Riel de navegación en tinta a alto completo; ítem activo en rojo.
- Un solo acento: el rojo, para acción, selección y foco.
- Bandas de indicadores: un patio dividido en celdas por muros internos.
- Cifras tabulares alineadas a la derecha, nunca cortadas con «…».
- Geometría cuadrada: módulos de 2 px de radio, conectores rectos, hitos cuadrados.
- Movimiento solo para comunicar estado (160 ms).

## Colors

Paleta institucional fija de Caja Trujillo: un rojo como único acento, una rampa de neutros fríos derivada de la tinta y cuatro familias de estado (verde, ámbar, azul, peligro), cada una con base, fondo suave y muro.

### Primary
- **Rojo Caja Trujillo** (`rojo`): botón principal de cada vista (uno solo), ítem activo del riel, contadores del riel y de la campana, pestaña activa, anillo de foco, cursor y casillas marcadas, módulo «actual» del friso. Nunca como adorno ni para pintar la prioridad alta.
- **Rojo profundo** (`rojo-dark`): hover y presión del botón principal; hover de enlaces.
- **Rojo suave / muro rojo** (`rojo-soft`, `rojo-line`): fondo y borde de lo seleccionado (opción marcada, filtro activo, paso actual del lote, notificación no leída, selección de texto).

### Secondary
- **Tinta** (`tinta`): texto principal, fondo del riel y del panel de acceso, cabecera del asistente, página activa del paginador, segmento activo, módulos «completos» del friso del expediente y números de paso. Es el segundo color de peso de la interfaz: lo que es importante pero no es acción va en tinta.

### Tertiary (estados)
- **Verde** (`verde`, `verde-soft`, `verde-line`): aprobado, conforme, honrado, devuelto, «cuadra»; botón Aprobar.
- **Ámbar** (`ambar`, `ambar-soft`, `ambar-line`): observado, en revisión, pendiente de firma; botón Observar. **Ámbar de barra** (`ambar-barra`) solo para rellenos (barras, módulos, marcadores), nunca para texto.
- **Azul** (`azul`, `azul-soft`, `azul-line`): información, agregado, en curso, avisos neutros informativos.
- **Peligro** (`peligro`, `peligro-soft`, `peligro-line`): rechazado, con diferencias, error de campo; botón Rechazar.

### Neutral
- **Gris** (`gris`): texto secundario, etiquetas de KPI, encabezados de tabla, ayudas. Único gris permitido para texto sobre fondo claro (5,4:1).
- **Gris claro** (`gris-claro`): íconos, bordes en hover, rótulos de grupo del riel y texto **sobre tinta**.
- **Texto del riel** (`riel-texto`): texto de los ítems del riel sobre tinta.
- **Borde fuerte** (`borde-fuerte`): contorno de controles (campos, botones secundarios, segmentados), módulos pendientes del friso.
- **Borde / muro** (`borde`): muros de patios, separadores de secciones, pista de barras.
- **Línea** (`linea`): división entre filas de tabla y listas; el borde al 55 % sobre blanco.
- **Plano** (`fondo`): fondo de la aplicación.
- **Sutil** (`sutil`): fondos internos (encabezado de tabla, hover de fila, campo de solo lectura, pie de modal, `card-plain`).
- **Superficie** (`superficie`): patios, campos, botones secundarios.

### Named Rules
**The One Voice Rule.** El rojo marca una sola acción principal por vista, la selección y el foco. Las acciones repetidas por fila son secundarias y la prioridad alta se pinta en tinta, no en rojo.

**The Meaningful Color Rule.** Verde, ámbar, azul y peligro aparecen solo cuando nombran un estado, y siempre acompañados de texto: el estado nunca depende solo del color.

**The Ink-Only Light Gray Rule.** `gris-claro` nunca es texto sobre fondo claro; sobre blanco el texto secundario es `gris`.

## Typography

**Display Font:** Inter (con system-ui, -apple-system, Segoe UI, Roboto)
**Body Font:** Inter (misma familia)
**Label/Mono Font:** Inter con cifras tabulares; no hay monoespaciada.

**Character:** Una sola familia sobria y técnica, servida localmente (`public/vendor/inter`), con las alternativas `cv11` y `ss01` activadas. La jerarquía sale del tamaño y el peso, no de familias distintas. Escala fija de razón ≈1,2, sin tamaños fluidos salvo el ajuste de cifras al ancho de su celda.

### Hierarchy
- **Display** (700, 56 px, 1,05, −0,03 em): excepción de display. Una sola cifra héroe por pantalla (recuperación sostenida en Recaída, diferencia del cruce TXT). En celdas estrechas se ajusta con consulta de contenedor, nunca se trunca.
- **Headline** (600, 24 px, 1,25, −0,015 em): título de página y del panel de acceso (20 px en móvil; 32 px en el lateral del acceso).
- **Figure** (700, 24 px, 1,15, −0,02 em, tabular): valor de KPI y montos protagonistas; con consulta de contenedor baja hasta 18 px. Cifras de patio de 32 px (700) para un dato protagonista por patio.
- **Title** (600, 14 px, 1,35): título de patio, de sección de formulario y de estado vacío. Títulos de modal en 16 px.
- **Body** (400, 14 px, 1,5): texto base. Descripciones a un máximo de ~92 ch.
- **UI** (500–600, 13 px): celdas de tabla, botones, etiquetas de campo y de KPI, ítems del riel.
- **Label** (500–600, 12 px): encabezados de tabla, ayudas, metadatos, sellos. **Mínimo legible del sistema.**
- **Group label** (600, 12 px, 0,06 em, mayúsculas): solo rótulos de grupo del riel y títulos de sección (`section-title`) que encabezan contenido propio; nunca encima de un título.

### Named Rules
**The Tabular Figures Rule.** Toda cifra usa `tabular-nums`, se alinea a la derecha en tablas y no salta de línea. Una cifra nunca se corta con «…»: se ajusta al contenedor.

**The 12-Pixel Floor Rule.** Ningún texto baja de 12 px.

## Layout

Estructura fija de tres zonas: **riel** de 256 px a la izquierda (fijo, a alto completo, con desplazamiento propio), **barra superior** de 56 px (fija, blanca, con muro inferior) y **plano de trabajo** con relleno de 24 px arriba, 32 px a los lados y 48 px abajo. Debajo de 992 px el riel se oculta tras un botón de menú y entra como capa con fondo oscurecido; el plano pasa a 20/16/40 px.

Ritmo de espaciado de 4 px (`s-1` a `s-12`). Reglas observadas: 20 px entre patios y de relleno interno; 24 px bajo el encabezado de página; 16 px de separación en rejillas; siempre más aire sobre un título que debajo.

Cada pantalla abre con el **encabezado de página**: título de 24 px con descripción gris de 13 px a la izquierda y acciones a la derecha (la principal, roja). Las pantallas de detalle llevan migas de pan sobre el título solo cuando hay jerarquía real («Mis propuestas / Propuesta FUD-00012»). Debajo, la banda de indicadores y el patio de trabajo (tabla o tarjetas) con su barra de filtros.

Rejillas: `grid2/3/4` que colapsan a 2 columnas bajo 1200 px y a 1 bajo 768 px; bandas de KPI que ajustan sus columnas a 3, 4, 5 o 6 indicadores para no dejar celdas vacías; filas asimétricas 2:1, 1,2:1 y 1,4:1 para gráfico + resumen. Las tablas desplazan horizontalmente dentro de su envoltorio, nunca la página. En celular, la tabla de la Bandeja se convierte en registros apilados.

Modo presentación: oculta riel, barra y asistente, amplía el plano (zoom 1,08) para capturas y video de Gerencia.

## Elevation & Depth

El sistema es **plano por defecto**: los patios no llevan sombra; la elevación se declara con el muro de 1 px y el contraste entre `fondo` y `superficie`. Dentro de un patio, la jerarquía se marca con `sutil` (encabezados, bloques internos) y con muros internos, nunca con otra tarjeta. Solo lo que realmente flota sobre el plano lleva sombra.

### Shadow Vocabulary
- **Flota** (`box-shadow: 0 2px 4px rgba(16, 24, 40, .06), 0 12px 32px rgba(16, 24, 40, .14)`): panel de notificaciones, avisos emergentes, asistente y su botón.
- **Modal** (`box-shadow: 0 4px 8px rgba(16, 24, 40, .08), 0 24px 64px rgba(16, 24, 40, .24)`): ventanas modales y riel abierto en móvil, sobre velo `rgba(16, 24, 40, .5)`.
- **Foco** (`box-shadow: 0 0 0 2px #ffffff, 0 0 0 4px #a3182c`): anillo único para todo control con teclado. Sobre el riel, anillo blanco sobre tinta.
- **Foco de campo** (`box-shadow: 0 0 0 3px color-mix(in srgb, #a3182c 16%, transparent)`): halo de campos con borde rojo.

### Named Rules
**The Wall-Not-Shadow Rule.** Un patio se separa del plano con su muro de 1 px, no con sombra. Si algo necesita sombra, es porque flota (capa, aviso, modal).

**The No Nesting Rule.** Un patio no contiene otros patios. Para dividirlo se usan muros internos: bandas, filas, `form-section`, bloques `sutil`.

## Shapes

Lenguaje cuadrado y contenido, como muros y adobes. Radios cortos y escalonados por tamaño de pieza: **2 px** para módulos del friso, hitos de línea de tiempo, barras y marcadores; **4 px** para sellos, contadores y chips; **6 px** para botones, campos, avisos y bloques; **8 px** para patios, modales y paneles flotantes. Los únicos círculos son los avatares y el botón del asistente (píldora de 44 px).

Todas las líneas son de 1 px: muros, separadores, conectores de línea de tiempo y del circuito de acceso. Las únicas líneas más gruesas son funcionales: el subrayado de 2 px de la pestaña activa, la barra de tiempo de 3 px de los avisos emergentes y la marca superior de 4 px de los pasos rotulados. Las barras de cantidad (6–8 px de alto, radio 2) codifican magnitud por largo.

## Components

### Buttons
Sobrios y firmes: semibold de 13 px, alto fijo, sin sombra.
- **Shape:** esquinas suaves (6 px); alto 36 px (`sm` 30 px, `lg` 42 px).
- **Primary:** fondo rojo, texto blanco, 7×14 px. Una sola por vista.
- **Hover / Focus:** el principal pasa a rojo profundo; los secundarios a fondo `sutil` con borde `gris-claro`. Transición de 160 ms en color y borde. Foco con el anillo rojo de 2 px separado por blanco. Deshabilitado: 50 % de opacidad, conserva su color.
- **Secondary / Ghost:** secundario en superficie con borde fuerte y texto tinta (500); fantasma transparente en gris.
- **Decisión:** Aprobar (verde lleno), Observar (blanco con borde y texto ámbar), Rechazar (blanco con borde y texto peligro). Solo el aprobar va lleno: rechazar no compite con la acción positiva.

### Chips
- **Style:** sellos (`badge`) de 12 px, radio 4, relleno 2×8, con fondo suave, texto y borde de su familia de estado; variante `dot` con un módulo cuadrado de 6 px. Chips de dato (`pr-chip`) en `sutil` con borde.
- **State:** ok / pendiente / peligro / info / gris / rojo (prioridad). El texto del sello siempre nombra el estado.

### Cards / Containers
- **Corner Style:** 8 px.
- **Background:** `superficie` (patio) o `sutil` (`card-plain`).
- **Shadow Strategy:** ninguna (ver Elevation & Depth).
- **Border:** muro de 1 px `borde`.
- **Internal Padding:** 20 px (16 px en móvil). `card-flush` sin relleno para que la tabla llegue a los muros.

### Inputs / Fields
- **Style:** etiqueta de 13 px (500) sobre control de 36 px, borde `borde-fuerte`, radio 6, 13 px; flecha de select propia en gris.
- **Focus:** borde rojo con halo suave de 3 px.
- **Error / Disabled:** error con borde y mensaje de 12 px en peligro; solo lectura y deshabilitado en `sutil` con borde `borde`.

### Navigation
- **Riel:** fondo tinta, marca CT fija arriba, rótulos de grupo de 12 px en mayúsculas, ítems de 13 px (500) en `riel-texto` con ícono en gris claro. Hover: tinta aclarada 7 % y texto blanco. Activo: fondo rojo, texto blanco 600. Contadores cuadrados rojos (blancos sobre el ítem activo).
- **Pestañas internas:** subrayado de 2 px; la activa en rojo.
- **Segmentado:** contenedor con borde fuerte; el segmento activo en tinta con texto blanco.
- **Paginador:** botones de 32 px con borde; página actual en tinta.
- **Móvil:** el riel entra desde la izquierda sobre velo oscuro; la barra superior conserva marca, campana y salir.

### Banda de indicadores
Un solo patio dividido en celdas por muros internos de 1 px; es el único componente de KPI. Cada celda: etiqueta de 13 px gris (con ícono gris claro, o con un módulo cuadrado de 8 px del color de estado en vez del ícono), valor de 24 px 700 tabular que se ajusta al ancho de la celda, y subtexto de 12 px. Las celdas enlazadas toman fondo `sutil` en hover.

### Friso de etapas (sello del sistema)
Banda de cinco módulos cuadrados de 12 px (16 px en `friso-lg`), radio 2, separados 3 px: propuesta enviada, decisión, sustento cargado, revisión del sustento, expediente cerrado. Completa en tinta, actual en rojo, observada en ámbar de barra, rechazada en peligro, pendiente vacía con borde fuerte. Lleva `aria-label` con todas las etapas y, opcionalmente, el nombre de la etapa actual en 12 px gris o una versión rotulada. Va en trazabilidad, detalle del expediente y Mis propuestas; no en la Bandeja (todas sus filas están en la misma etapa). El mismo vocabulario cuadrado se repite en los pasos del lote de honramiento (`lt-pasos`), la línea de tiempo (hitos cuadrados de 11 px sobre conector de 1 px) y el circuito del acceso.

### Tablas
Encabezado de 12 px semibold gris sobre `sutil`; celdas de 13 px con relleno 10×12 y división `linea`; hover de fila suave; numéricas a la derecha, tabulares y sin salto; dato principal en 600 tinta y dato secundario en 12 px gris. Estado vacío centrado con ícono gris claro, título en tinta y una línea que explica qué falta.

### Avisos
Fondo suave, muro y texto del color de su estado, ícono de 16 px, radio 6. Sin franjas laterales. Los avisos emergentes marcan su estado con una barra de tiempo inferior de 3 px.

## Do's and Don'ts

### Do:
- **Do** separar cada sección con un patio blanco y muro de 1 px `#e3e6ea` sobre el plano `#f4f5f7`.
- **Do** usar un único botón rojo `#a3182c` por vista para la acción principal; el resto, secundario o fantasma.
- **Do** mostrar indicadores en una sola banda dividida por muros internos, con valores de 24 px 700 tabulares.
- **Do** alinear las cifras a la derecha con `tabular-nums` y ajustarlas al contenedor en vez de truncarlas.
- **Do** mostrar el estado del expediente con el friso de módulos cuadrados y nombrarlo también en texto.
- **Do** reservar la sombra para lo que flota (notificaciones, avisos emergentes, asistente, modales).
- **Do** usar 160 ms con `cubic-bezier(.2, .7, .2, 1)` para hover y foco, y respetar `prefers-reduced-motion`.

### Don't:
- **Don't** anidar tarjetas dentro de tarjetas.
- **Don't** usar franjas laterales o superiores gruesas como acento de color.
- **Don't** poner íconos dentro de recuadros de color sobre cada cifra de KPI.
- **Don't** usar degradados decorativos (los únicos degradados son funcionales: relleno del control deslizante y leyenda del mapa de calor).
- **Don't** poner rótulos decorativos encima del título de página; arriba solo van migas con jerarquía real.
- **Don't** animar entradas en cascada o con rebote; el movimiento solo comunica estado.
- **Don't** escribir texto en `gris-claro` sobre fondo claro ni usar tamaños menores a 12 px.
- **Don't** escribir colores a mano fuera de los tokens.
- **Don't** cortar una cifra con «…».
