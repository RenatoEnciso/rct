# Prompt: canal WhatsApp + correo (demo) y kit completo del video

> Antes de pegarlo: copia `GUION_VIDEO_RCT.md` en `E:\laragon\www\RCT\docs\`.
> Pega el bloque de abajo en Claude Code (modo normal). Trabaja de corrido por fases
> y solo se detiene en el punto de validación del video.

---

```
OBJETIVO: dejar RCT listo para grabar el video de venta, con dos frentes:
(A) simular el canal WhatsApp y el correo para que se vean en acción, y
(B) armar el kit completo del video con el diseño REAL del sistema.

Lee primero: docs/RCT_CONTEXTO_NEGOCIO.md (§7.2 WhatsApp), docs/PROCESO_IMPULSO_MYPERU.md,
docs/PANTALLAS_CLAVE_VIDEO.md y docs/GUION_VIDEO_RCT.md (borrador de guion hecho
fuera del proyecto; tú conoces el sistema real, así que corrígelo donde no coincida).

=====================================================================
FASE A — CANAL WHATSAPP Y CORREO (simulación vistosa, lógica real)
=====================================================================
No hay WhatsApp Business API conectado. Simúlalo con una pantalla que parezca un
celular, pero que use los SERVICIOS REALES del sistema: lo que se haga en el chat
debe aparecer registrado en RCT y viceversa.

A1. Pantalla "Canal WhatsApp" (menú Analítica o Herramientas, solo jefe/admin y
    el usuario dueño del número):
    - Maqueta de teléfono a la derecha (estilo de app de mensajería: burbujas verdes
      y blancas, hora, doble check). NO uses el logo oficial de WhatsApp; usa un
      ícono genérico de chat y el nombre "RCT Bot".
    - A la izquierda, la pantalla real de RCT afectada, que se actualiza en vivo
      (polling) para mostrar el "efecto en el otro lado".
    - Cada usuario demo tiene un número vinculado (columna users.whatsapp).

A2. Comandos del bot (texto libre con intención simple + menú de opciones):
    - "estado 12345" / "cómo va mi propuesta" → responde estado del FUD,
      aprobador, fecha y siguiente paso.
    - "pendientes" → lista corta de lo que el usuario debe hacer hoy (usa el
      mismo cálculo que el panel / "Mi prioridad de hoy").
    - "prioridad" → top 3 clientes prioritarios con puntaje y acción sugerida.
    - Enviar una foto o PDF de sustento ("adjuntar" con archivos de ejemplo del
      seeder) → se asocia al expediente, corre la validación demo de documentos
      (REGLAS_DEMO_VALIDACION.md) y responde "Conforme" o "Falta la firma del
      titular". En la pantalla de RCT de la izquierda aparece el documento cargado.
    - "registrar visita 12345 promesa viernes 1500" → registra una gestión de
      contacto / promesa de pago y queda en la trazabilidad del cliente.
    - "cofide" (jefe) → monto por devolver este mes, diferencia del cruce y
      devoluciones por vencer.
    - "resumen" → resumen del día por rol.
    - Respuestas con formato de mensajería: negritas, emojis sobrios, listas
      cortas. Tiempo de "escribiendo…" de 1–2 s antes de cada respuesta.

A3. Notificaciones salientes por WhatsApp (simuladas):
    - Los eventos que hoy generan campana/correo (pago detectado, propuesta
      aprobada/observada, sustento observado, cruce con diferencias, devolución
      por vencer) también generan un mensaje en el chat del usuario.
    - Ejemplo clave para el video: al ejecutar rct:simular-pago-core, en segundos
      llega al teléfono "💰 Pago detectado … Reversión COFIDE S/ 2 700,00",
      a la vez que el toast y la campana en RCT.

A4. Bandeja de correo demo:
    - Pantalla "Correos enviados" (solo admin/jefe) que muestre los correos que
      el sistema generó (canal mail a log o tabla), con vista previa HTML de la
      plantilla institucional: asunto, destinatario, hora y cuerpo.
    - Si las plantillas de correo no tienen el diseño institucional, mejóralas
      (logo, colores del sistema, botón "Ver en RCT").

A5. Todo determinista, con historial de conversación de ejemplo en el seeder,
    incluido en rct:demo-reset, y un botón "Reiniciar conversación" para grabar
    varias veces. Un commit por punto (A1–A5).

=====================================================================
FASE B — KIT DEL VIDEO CON EL DISEÑO REAL DEL SISTEMA
=====================================================================
B1. Identidad visual extraída del sistema (no inventada):
    - Lee public/assets/css/rct.css, layouts y vistas, y genera
      docs/video/identidad.json con: colores exactos (primario, secundarios,
      éxito, alerta, error, fondos, textos), tipografía y pesos, radios,
      sombras, espaciados e íconos usados.
    - Copia a docs/video/assets/ el logo y las fuentes que usa el sistema.

B2. Guion definitivo:
    - Reescribe docs/GUION_VIDEO_RCT.md con las rutas, nombres de menú, usuarios,
      clientes y cifras REALES de la base actual. Máximo 2:00.
    - Debe mostrar el flujo completo: prioridad → propuesta con aprobador
      automático → observado/rechazado → corrección → aprobado → subida de
      sustentos con revisión IA (observado y luego conforme) → trazabilidad →
      pago detectado (toast + campana + correo + WhatsApp a la vez) → reversión
      automática → cruce TXT S/ 0,00 → Centro de Control y simulador →
      recaída y alertas (planos cortos) → WhatsApp: consultar y registrar desde
      el celular y verlo aparecer en RCT → reportes en 1 clic → cierre.
    - Incluye una versión de 60 segundos.

B3. Capturas y grabaciones automáticas:
    - Instala Playwright (solo como herramienta de desarrollo) y crea un script
      que inicie sesión con cada usuario y genere en docs/video/capturas/:
      PNG a 1920×1080 de cada escena y VIDEOS (recordVideo) de las escenas
      animadas: validación de documento, pago detectado, cruce TXT, simulador,
      y la conversación de WhatsApp con su efecto en RCT.
    - Nombra los archivos con el número de escena (escena07_sustento.mp4, etc.).
    - Antes de capturar: modo presentación donde aplique, notificaciones previas
      marcadas como leídas y datos recién generados.

B4. Proyecto de video con Remotion:
    - Crea el proyecto en E:\rct-video (FUERA del repositorio RCT) y copia ahí
      identidad.json, assets y capturas.
    - Si está disponible, usa la skill oficial de Remotion; si no, instálala
      (verifica el comando en remotion.dev/docs/ai).
    - El video DEBE parecerse al sistema: mismos colores, tipografía, radios,
      estilo de tarjetas y badges de RCT. Capturas en un marco de navegador
      minimalista, zoom lento, recuadro animado sobre el elemento clave,
      contadores animados para las cifras, textos de máximo 8 palabras.
    - Escena WhatsApp: el teléfono a un lado y RCT al otro, mostrando el
      registro en ambos lados.
    - Cierre con "Prototipo con datos de demostración".

PUNTO DE VALIDACIÓN (única pausa):
Renderiza SOLO los primeros 20–30 segundos y muéstrame cómo verlos (Remotion
Studio con npm run dev). Espera mi OK antes de renderizar el video completo.

=====================================================================
REGLAS GENERALES
=====================================================================
- No rompas pantallas existentes; prueba con jefe, gerente, czr y ejecutivo.
- No ejecutes rct:demo-reset (te lo bloquean); si hace falta, avísame.
- Si una tarea falla, corrígela y continúa; pregúntame solo si es imposible
  sin una decisión mía.
- Al final de la Fase A dame: qué hizo cada punto, usuario y pantalla para
  probar, y los comandos de WhatsApp para la demo.
- Al llegar al punto de validación dame: dónde quedaron capturas, guion,
  identidad y el proyecto de video, y cómo verlo.
```
