# Sistema de diseño de RCT · «Ciudadela»

> Implementado en `public/assets/css/rct.css`. Sirve para mantener coherencia de pantalla a pantalla.
> Diagnóstico de origen: `docs/rediseno/AUDITORIA.md`.

## Idea

RCT funciona como una ciudadela administrativa. Las secciones son **patios** blancos delimitados por **muros de 1 px** sobre un solo plano gris. No se usan tarjetas flotantes con sombra. El estado de cada expediente se lee en el **friso**, una banda de módulos cuadrados.

Reglas de fondo:

- **Un solo acento.** El rojo institucional marca la acción principal de cada pantalla (un botón), la selección y el foco. Nunca se usa como adorno. Las acciones repetidas por fila son secundarias, y la prioridad alta se pinta en tinta, no en rojo.
- **Color con significado.** Los colores de estado aparecen solo cuando significan algo.
- **Números alineados.** Cifras tabulares, alineadas a la derecha y sin salto de línea.
- **Movimiento solo para el estado.** Nada de coreografías de entrada.

## Tokens

### Color (paleta institucional, fija)

| Token | Valor | Uso |
| --- | --- | --- |
| `--rojo` | `#a3182c` | Acción principal, selección, foco, alertas de prioridad |
| `--rojo-dark` | `#7d1221` | Hover del botón principal |
| `--rojo-soft` / `--rojo-line` | `#fbf0f1` / `#efd2d6` | Fondo y borde de lo seleccionado |
| `--tinta` | `#1c2430` | Texto principal; riel de navegación; selección en controles segmentados |
| `--gris` | `#5f6b7a` | Texto secundario. Es el único gris permitido para texto sobre fondo claro (5,4:1) |
| `--gris-claro` | `#8a94a3` | Íconos, bordes en hover y texto **sobre tinta** (5,1:1). Nunca texto sobre claro |
| `--borde-fuerte` | `#cfd4da` | Borde de controles |
| `--borde` | `#e3e6ea` | Muros de patios y separadores |
| `--linea` | `--borde` al 55 % | División entre filas |
| `--fondo` | `#f4f5f7` | Plano de la aplicación |
| `--sutil` | `#f8f9fa` | Fondos internos (encabezados de tabla, hover) |
| `--superficie` | `#ffffff` | Patios |
| `--verde` · `-soft` · `-line` | `#1f7a4d` | Aprobado, conforme, honrado, cuadra |
| `--ambar` · `-soft` · `-line` · `--ambar-barra` | `#a15c07` · `#d08a1e` | Observado, en revisión, pendiente; `--ambar-barra` solo para rellenos |
| `--azul` · `-soft` · `-line` | `#1f4e8c` | Información, agregado, en curso |
| `--peligro` · `-soft` · `-line` | `#b42318` | Rechazado, con diferencias, error |

Los colores de los gráficos (Chart.js) siguen en `rct-analitica.js`:

| Serie | Color |
| --- | --- |
| Recuperado | `#3567ad` |
| Condonado | `#b8761a` |
| Neutro | `#8a94a3` |

### Tipografía

Inter (local, `public/vendor/inter`), con escala fija de razón ≈1,2 y sin tamaños fluidos.

| Token | Tamaño | Uso |
| --- | --- | --- |
| `--t-12` | 12 px | Rótulos de tabla, ayudas, metadatos. **Mínimo legible** |
| `--t-13` | 13 px | Texto de interfaz, celdas, botones, etiquetas de KPI |
| `--t-14` | 14 px | Texto base, títulos de patio |
| `--t-16` | 16 px | Títulos de modal, subtítulos destacados |
| `--t-20` | 20 px | Cifras secundarias |
| `--t-24` | 24 px | Título de página, valor de KPI |
| `--t-32` | 32 px | Cifra protagonista de un patio |
| `--t-44` · `--t-56` | 44 / 56 px | **Excepción de display**: una sola cifra héroe por pantalla (recuperación sostenida en Recaída, diferencia del cruce TXT) |

- **Pesos:** 400 texto, 500 etiquetas, 600 títulos y botones, 700 cifras.
- **Cifras:** siempre `font-variant-numeric: tabular-nums`.
- **Espaciado de letras:** −0,02 em en cifras grandes y −0,015 em en títulos. No se usan valores menores a −0,04 em.

### Espaciado

Ritmo de 4 px:

| Token | Valor |
| --- | --- |
| `--s-1` | 4 |
| `--s-2` | 8 |
| `--s-3` | 12 |
| `--s-4` | 16 |
| `--s-5` | 20 |
| `--s-6` | 24 |
| `--s-8` | 32 |
| `--s-10` | 40 |
| `--s-12` | 48 |

Reglas:

- Entre patios: `--s-5`.
- Dentro de un patio: `--s-5` de relleno.
- Encabezado de página: `--s-6` debajo.
- Siempre más espacio sobre un título que debajo de él.

### Forma y profundidad

| Token | Valor | Uso |
| --- | --- | --- |
| `--r-sm` | 4 px | Badges, contadores, chips, módulos |
| `--r` | 6 px | Botones, campos, avisos |
| `--r-lg` | 8 px | Patios, modales, paneles |
| `--sombra-flota` | — | Solo lo que flota: menú de notificaciones, avisos emergentes, asistente |
| `--sombra-modal` | — | Ventanas modales |
| `--foco` | anillo rojo de 2 px con separación blanca | Todo control con teclado (`:focus-visible`) |
| `--foco-campo` | halo rojo suave | Campos de formulario |

Los patios no llevan sombra: la elevación se declara una sola vez, con el muro.

## Estructura

- **Riel** (`.rct-sidebar`, 256 px). Agrupa la navegación por tarea. Cada grupo lleva un rótulo de sección (`.nav-section-label`) y el ítem activo se marca con `.nav-btn.active`. Los contadores (`.nav-count`) son sellos rojos cuadrados.
- **Barra superior** (`.rct-header`, 56 px). Contiene la marca, las notificaciones y el usuario.
- **Plano de trabajo** (`.rct-main`). Arriba va el encabezado de página (`.page-head`): título 24 px, descripción y acciones a la derecha. La acción principal es un único botón rojo.
- **Migas de pan** (`.page-eyebrow`). Se usan solo con jerarquía real («Lotes de honramiento / 010 Lote»). Están prohibidos los rótulos decorativos encima del título.

## Componentes

### Patio · `.card`

Superficie blanca con muro de 1 px, radio 8 y relleno de 20.

- **Partes:** `.card-head` (título más acciones), `.card-title` (14 px semibold, con ícono gris opcional), `.card-sub` (descripción).
- **Patio para tablas:** `.card-flush`. La tabla llega hasta los bordes.
- **Sin anidar:** un patio no contiene otros patios. Para dividirlo se usan muros internos (bandas, filas, `.form-section`).

### Banda de indicadores · `.kpi-grid` > `.kpi-card`

Un solo patio dividido en celdas por muros internos. Es el **único** componente de KPI del sistema. Cada celda tiene tres partes:

- `.kpi-label`: 13 px gris. Con variante de estado (`.verde`, `.rojo`, `.ambar`, `.azul`), un módulo cuadrado de color reemplaza al ícono.
- `.kpi-value`: 24 px, 700, tabular. Se ajusta al ancho de la celda (consulta de contenedor) y **nunca** se corta con «…».
- `.kpi-sub`: 12 px gris.

Las variantes por módulo (`.cc-kpis`, `.mx-kpis`, `.rv-estados`, `.al-sev`, `.tareas`) usan la misma banda. Con 4 o 5 indicadores la banda ajusta sus columnas para no dejar celdas vacías.

### Friso de etapas · `<x-friso-expediente>`

Es el sello del sistema: cinco módulos cuadrados, uno por etapa fija del expediente: propuesta enviada, decisión, sustento cargado, revisión del sustento y expediente cerrado.

| Estado de la etapa | Relleno |
| --- | --- |
| Completa | Tinta |
| Actual | Rojo |
| Observada | Ámbar |
| Rechazada | Peligro |
| Pendiente | Vacía (solo el borde) |

- **Datos que usa:** el componente Blade (`resources/views/components/friso-expediente.blade.php`) recibe el estado del FUD y el del sustento y no ejecuta lógica de negocio. Cada módulo lleva su nombre en `title` y el conjunto tiene `aria-label`.
- **Dónde va:**
  - Trazabilidad.
  - Detalle de la propuesta.
  - Mis propuestas: siempre acompañado del sello con el estado en texto.
- **Variantes:** con `.friso-lg` los módulos pasan de 12 a 16 px. Con `etiquetas` se muestra el nombre de la etapa actual.
- **Versiones con rótulos:**
  - `.steps > .step`: genérica.
  - `.lt-pasos > .lt-paso`: lote de honramiento. El número cuadrado se rellena según el estado.
- **Línea de tiempo** (`.timeline`): usa el mismo vocabulario, con hitos cuadrados y un conector recto de 1 px.
- **No va en la Bandeja:** todas sus filas están en la misma etapa.

### Sellos de estado · `.badge`

Radio 4, 12 px, borde de su color.

| Variante | Estados |
| --- | --- |
| `badge-ok` | Aprobado, Conforme, Honrado, Devuelto |
| `badge-pending` | Observado, En revisión, Pendiente de firma |
| `badge-danger` | Rechazado, Con diferencias |
| `badge-info` | Agregado, En curso, Cargado en COFIDE |
| `badge-gray` | Neutros |
| `badge-rojo` | Prioridad |

El estado nunca depende solo del color: el texto del sello lo nombra.

### Botones · `.btn`

Alto 36 (`.btn-sm` 30, `.btn-lg` 42), radio 6, 13 px semibold.

| Clase | Uso |
| --- | --- |
| `btn-primary` | **Una** acción principal por vista |
| `btn-secondary` | Acciones secundarias |
| `btn-ghost` | Acciones terciarias |
| `btn-success` | Aprobar |
| `btn-warning` | Observar |
| `btn-danger` | Rechazar |

Todos tienen hover, foco visible y estado deshabilitado (50 %).

### Formularios · `.field`

- Etiqueta 13 px (`label`) más control de 36 px. La ayuda (`.field-hint`) y el error (`.field-error`) van en 12 px.
- Foco con borde rojo y halo suave. Los campos de solo lectura usan fondo `--sutil`.
- Los `select` sueltos dentro del contenido (filtros) toman el mismo estilo.
- **Barra de filtros:** `.filtros` (grilla automática) con las acciones al final en `.filtros-acciones`.

### Tablas

- **Encabezado:** 12 px semibold gris sobre `--sutil`.
- **Celdas:** 13 px, filas de 10 px de relleno vertical, división `--linea` y hover suave.
- **Numéricas** (`td.num`): a la derecha, tabulares y sin salto de línea.
- **Partes:** `.cell-main` (dato principal), `.cell-sub` (dato secundario), `.table-tools` (búsqueda y contador) y `.rct-pager` (página actual en tinta).
- **Desplazamiento horizontal:** siempre dentro de `.table-wrap`, nunca la página.

### Avisos · `.notice`, `.notice-warning`, `.notice-danger`, `.notice-success`, `.notice-neutral`

Fondo suave, muro de su color, ícono y texto en el color del estado. **Sin franjas laterales gruesas.** Los mensajes de sesión (`.flash-*`) siguen el mismo patrón.

### Estado vacío · `.empty-state`

Ícono gris, título en tinta y una línea que explica qué falta o qué hacer.

### Navegación interna · `.im-tabs`

Pestañas con subrayado de 2 px; la activa va en rojo. Se usa dentro de Programas de gobierno.

### Capas que flotan

- Menú de notificaciones, avisos emergentes (`.toast-rct`) y asistente.
- Llevan `--sombra-flota` y radio 8.
- Los avisos emergentes marcan su estado con la barra de tiempo inferior, no con una franja lateral.

## Lo que no se hace

- Tarjetas dentro de tarjetas.
- Franjas laterales o superiores gruesas como acento.
- Íconos en recuadro de color sobre cada cifra.
- Degradados decorativos.
- Rótulos encima del título.
- Animaciones de entrada en cascada o con rebote.
- Texto en `--gris-claro` sobre fondo claro.
- Tamaños menores a 12 px.
- Colores escritos a mano fuera de los tokens.
- Cortar una cifra con «…».

## Movimiento

- **Duración y curva:** 160 ms con `--ease` para hover y foco.
- **Barras:** se llenan en 0,4–0,6 s, solo como confirmación de dato.
- **Aviso en vivo y cruce TXT:** son los únicos momentos animados. La diferencia cambia de color al cuadrar.
- **`prefers-reduced-motion`:** desactiva las transiciones de las barras y la animación del cruce.
