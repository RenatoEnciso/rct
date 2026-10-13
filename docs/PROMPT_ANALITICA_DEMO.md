# Prompt: Analítica avanzada (simulada) + vitrina comercial de RCT

> Guarda este archivo en `docs\` y pega en Claude Code (modo **Plan**) el bloque de abajo.

---

```
NUEVO OBJETIVO: Analítica avanzada simulada + preparar RCT para venderlo.

CONTEXTO
- RCT es una demo para presentar a la Jefatura y Gerencia. De las pantallas se
  tomarán capturas y se hará un video tipo "motion" para vender el proyecto.
- Por eso las pantallas nuevas deben ser lo más vistoso del sistema: limpias,
  con números grandes, gráficos claros y animaciones suaves al cargar.
- La analítica será SIMULADA: los resultados quedan precalculados en la base de
  datos (columnas y tablas nuevas llenadas por seeder), pero deben verse 100%
  reales y ser coherentes con el resto de datos del sistema.
- Lee docs/RCT_CONTEXTO_NEGOCIO.md (secciones 8.3 y 9) y docs/GAP_ANALISIS.md.

PRINCIPIOS DE LA SIMULACIÓN
- Todo DETERMINISTA: mismo seed = mismos números en cada demo (nada de rand()
  en tiempo real). Si se necesita variación, usar un hash del ID del crédito.
- Coherencia total: los créditos, asesores, tiendas, zonas y montos que aparezcan
  en la analítica son los mismos del resto del sistema. Un número que se ve en
  una pantalla debe cuadrar con el de otra.
- Los puntajes se calculan con una fórmula simple sobre datos reales de la
  cartera (saldo, días de atraso, calificación, tramo, historial) + un ajuste
  determinista, para que se vean "inteligentes" y no aleatorios.
- En la interfaz se presentan como "Modelo de priorización", "Análisis de
  recaída", "Alertas de control". La palabra "simulado" no aparece en pantalla.
- Documenta internamente cómo se generan en docs/REGLAS_DEMO_ANALITICA.md
  (igual que REGLAS_DEMO_VALIDACION.md), y deja todo detrás de un servicio con
  interfaz para reemplazarlo por un modelo real después.
- Todos los nombres de clientes y personas son ficticios.

1. PRIORIZACIÓN DE CARTERA — "Prioridad de hoy"
   Datos (columnas o tabla nueva por crédito):
   score_prioridad (0–100), segmento (Alta / Media / Baja), probabilidad_pago (%),
   monto_recuperable (S/), provision_liberable (S/), motivos (2–3 etiquetas, ej.
   "Cerca de pasar a Dudoso", "Cumplió última promesa", "Cierre de mes en 5 días",
   "Saldo alto"), accion_sugerida (Llamar / Visitar / Ofrecer cancelación /
   Ofrecer pago de cuotas), mejor_horario_contacto.
   Pantallas:
   - Para el asesor: "Mi prioridad de hoy" con top 20 en tarjetas: puntaje en
     anillo de color, cliente, saldo, motivos como chips, acción sugerida y botón
     "Gestionar" que abre Nueva propuesta con el cliente cargado.
   - Para el CZR/Jefe: mapa de calor zona × segmento, monto recuperable por zona,
     y cuánto de la cartera prioritaria ya fue gestionada.
   - Comparativo: "Recuperado gestionando por prioridad vs. orden normal"
     (gráfico que muestre la ventaja).

2. RECAÍDA — "¿El beneficio funcionó?"
   Tabla de seguimiento post-beneficio para cada FUD aprobado/pagado:
   estado a 30, 60 y 90 días (Al día / Atraso leve / Recayó), fecha de recaída.
   Pantallas:
   - KPI grande: "Recuperación sostenida a 90 días: XX%".
   - Curvas de supervivencia/cohortes por mes de beneficio.
   - Recaída por tipo de negociación, por calificación, por tienda y por asesor.
   - Lista "Clientes en riesgo de recaer" con semáforo.
   - Aviso en la propuesta: si el cliente ya recayó antes, mostrar alerta
     "Este cliente recayó tras su último beneficio (hace N días)".
   Haz que los resultados cuenten una historia creíble (ej. "se pone al día"
   recae menos que "pago de 1 cuota"; refinanciados recaen más).

3. DETECCIÓN DE ANOMALÍAS — "Alertas de control"
   Tabla alertas: tipo, severidad (Alta/Media/Baja), entidad (asesor, aprobador,
   tienda, cliente), descripción, métrica observada vs. promedio, fecha, estado
   (Nueva / En revisión / Descartada / Confirmada), comentario.
   Tipos a sembrar (~20 alertas plausibles):
   - Asesor que propone siempre el descuento al tope.
   - Aprobador que aprueba en minutos todo lo de un mismo asesor.
   - Excepciones concentradas en una tienda.
   - Cliente con beneficios en campañas consecutivas.
   - Documentos con misma firma en clientes distintos.
   - Carga de sustentos fuera de horario.
   - Devolución a COFIDE próxima a vencer sin gestionar.
   Pantallas (Jefe de Recuperaciones / Auditoría):
   - Bandeja de alertas con filtros y contador por severidad.
   - Detalle con gráfico "esta persona vs. promedio de la zona" y botón para
     cambiar estado con comentario (esto sí se guarda de verdad).

4. CENTRO DE CONTROL (pantalla estrella para Gerencia)
   Un solo dashboard ejecutivo que resuma todo el sistema:
   - KPIs grandes con contador animado: monto recuperado, provisión liberada,
     monto condonado, soles recuperados por cada sol condonado, expedientes
     de programas completos, devoluciones COFIDE al día.
   - Tendencia mensual, avance vs. meta por zona, top tiendas.
   - Bloque de analítica: cartera prioritaria pendiente, recaída a 90 días,
     alertas abiertas.
   - SIMULADOR DE IMPACTO: un control deslizante "Si gestionamos el X% de la
     cartera prioritaria…" que recalcula en vivo cuánto se recuperaría y cuánta
     provisión se liberaría. Es el momento "wow" de la demo.

5. ASPECTO VISUAL (pensado para capturas y video)
   - Diseño para 1920×1080 (16:9) que también funcione en laptop.
   - Colores institucionales, mucho espacio en blanco, tarjetas con sombra
     suave, íconos coherentes, tipografía grande en KPIs.
   - Animaciones al cargar: números que cuentan hacia arriba, gráficos que se
     dibujan, tarjetas que aparecen en secuencia. Suaves, nada exagerado.
   - "Modo presentación": botón que oculta el menú lateral, agranda la
     tipografía y deja la pantalla limpia para capturar.
   - Sin datos vacíos, sin textos de prueba, sin números con decimales raros:
     montos en formato S/ 1 234 567, porcentajes con un decimal.

6. VITRINA PARA VENDER EL PROYECTO
   - Pantalla de inicio de sesión renovada con el nombre RCT, una frase de
     valor y una imagen/ilustración institucional.
   - Página "Acerca de RCT" (o pantalla de bienvenida) con el "antes vs. después":
     Hoy: correos con FUD, link compartido, revisión manual, reporte en 2 días
     hábiles, devoluciones COFIDE en Excel.
     Con RCT: aprobación automática, validación inteligente de documentos,
     reporte en 1 clic, alertas de devolución, priorización con analítica.
   - Crea docs/GUION_VENTA.md con:
     a) Mensaje principal del proyecto en una frase.
     b) 5–7 beneficios clave con una métrica cada uno (con los datos de la demo).
     c) Lista de capturas a tomar: pantalla, usuario, qué filtros o estado
        dejar visible, y qué destacar en cada una.
     d) Guion del video motion por escenas (10–15 escenas): qué pantalla,
        qué texto en pantalla, duración sugerida y transición.
     e) Guion de demo en vivo de 10 minutos.

REGLAS DE TRABAJO
- Primero dame el plan (tablas/columnas, fórmulas de puntaje, pantallas,
  archivos, librerías de gráficos/animación que usarás) y espera mi OK.
- Implementa por partes en este orden: Centro de Control → Priorización →
  Recaída → Anomalías → Vitrina y guion. Un commit por parte.
- Al terminar cada parte: con qué usuario entrar y qué pantalla abrir.
- Usa recursos locales (public/vendor), sin depender de internet.
- Incluye todo en el comando rct:demo-reset para regenerar los datos iguales.
```
