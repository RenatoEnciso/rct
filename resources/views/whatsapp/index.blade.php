<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="theme-color" content="#008069">
<title>Caja Trujillo · Recuperaciones</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<style>
  :root {
    --wa-verde: #008069; --wa-verde-2: #00a884; --wa-fondo: #efeae2; --wa-mio: #d9fdd3; --wa-otro: #ffffff;
    --wa-texto: #111b21; --wa-meta: #667781; --wa-azul: #027eb5; --wa-check: #53bdeb; --wa-linea: #e9edef;
  }
  * { box-sizing: border-box; -webkit-tap-highlight-color: transparent; }
  html, body { height: 100%; margin: 0; }
  body { font-family: 'Roboto', 'Segoe UI', Helvetica, Arial, sans-serif; background: #d1d7db; color: var(--wa-texto);
         display: flex; align-items: center; justify-content: center; }

  /* Marco de teléfono en escritorio */
  .telefono { position: relative; width: 400px; height: min(840px, calc(100vh - 76px)); margin-bottom: 28px; background: var(--wa-fondo);
              border-radius: 34px; box-shadow: 0 0 0 10px #1f2326, 0 0 0 11px #3a3f43, 0 24px 60px rgba(0,0,0,.35);
              display: flex; flex-direction: column; overflow: hidden; }
  .barra-sistema { height: 26px; background: #017561; color: #fff; font-size: 12px; display: flex; align-items: center;
                   justify-content: space-between; padding: 0 18px; flex-shrink: 0; }
  .barra-sistema .bi { font-size: 12px; margin-left: 4px; }
  .pie-demo { position: fixed; bottom: 12px; left: 0; right: 0; text-align: center; font-size: 12px; color: #54656f; }
  .pie-demo a { color: #54656f; }

  /* Encabezado del chat */
  .cabecera { background: var(--wa-verde); color: #fff; display: flex; align-items: center; gap: 8px; padding: 8px 6px 8px 4px; flex-shrink: 0; position: relative; }
  .cabecera .icono { border: 0; background: none; color: #fff; font-size: 20px; padding: 6px; cursor: pointer; border-radius: 50%; }
  .cabecera .icono:hover { background: rgba(255,255,255,.1); }
  .avatar { width: 38px; height: 38px; border-radius: 50%; background: #fff; color: #a3182c; display: grid; place-items: center; font-weight: 700; font-size: 14px; flex-shrink: 0; }
  .contacto { flex: 1; min-width: 0; line-height: 1.2; }
  .contacto b { font-weight: 500; font-size: 16px; display: flex; align-items: center; gap: 4px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
  .contacto b .bi { color: #25d366; font-size: 14px; background: #fff; border-radius: 50%; }
  .contacto small { font-size: 12.5px; color: rgba(255,255,255,.85); }
  .menu-opc { position: absolute; top: 46px; right: 8px; background: #fff; color: var(--wa-texto); border-radius: 6px; box-shadow: 0 4px 18px rgba(0,0,0,.2); min-width: 190px; display: none; z-index: 20; padding: 6px 0; }
  .menu-opc.abierto { display: block; }
  .menu-opc button, .menu-opc a { display: block; width: 100%; text-align: left; border: 0; background: none; padding: 11px 18px; font-size: 14.5px; color: var(--wa-texto); text-decoration: none; cursor: pointer; font-family: inherit; }
  .menu-opc button:hover, .menu-opc a:hover { background: #f5f6f6; }

  /* Mensajes */
  .chat { flex: 1; overflow-y: auto; padding: 10px 12px 14px; display: flex; flex-direction: column; gap: 3px;
          background-color: var(--wa-fondo);
          background-image: radial-gradient(rgba(0,0,0,.035) 1px, transparent 1px), radial-gradient(rgba(0,0,0,.03) 1px, transparent 1px);
          background-size: 22px 22px, 22px 22px; background-position: 0 0, 11px 11px; }
  .chip { align-self: center; background: #fff; color: #54656f; font-size: 12.5px; padding: 5px 12px; border-radius: 8px; margin: 6px 0; box-shadow: 0 1px .5px rgba(11,20,26,.13); text-align: center; max-width: 88%; }
  .chip.aviso { background: #ffeecd; color: #54656f; font-size: 12px; }
  .grupo { display: flex; flex-direction: column; max-width: 86%; margin-top: 6px; }
  .grupo.bot { align-self: flex-start; }
  .grupo.yo { align-self: flex-end; align-items: flex-end; }
  .burbuja { position: relative; padding: 6px 8px 8px 9px; border-radius: 8px; font-size: 14.2px; line-height: 1.38;
             box-shadow: 0 1px .5px rgba(11,20,26,.13); word-wrap: break-word; white-space: normal; min-width: 72px; }
  .bot .burbuja { background: var(--wa-otro); border-top-left-radius: 0; }
  .yo .burbuja { background: var(--wa-mio); border-top-right-radius: 0; }
  .bot .burbuja::before, .yo .burbuja::before { content: ''; position: absolute; top: 0; width: 8px; height: 13px; }
  .bot .burbuja::before { left: -8px; background: linear-gradient(225deg, var(--wa-otro) 50%, transparent 50%); }
  .yo .burbuja::before { right: -8px; background: linear-gradient(135deg, var(--wa-mio) 50%, transparent 50%); }
  .hora { float: right; margin: 6px 0 -4px 10px; font-size: 11px; color: var(--wa-meta); display: inline-flex; align-items: center; gap: 3px; }
  .hora .bi { color: var(--wa-check); font-size: 15px; line-height: 1; }
  .burbuja strong { font-weight: 500; }
  /* Botones de respuesta */
  .botones { display: flex; flex-direction: column; gap: 2px; margin-top: 2px; }
  .botones button { border: 0; background: var(--wa-otro); color: var(--wa-azul); font-size: 14.2px; font-family: inherit; padding: 10px; border-radius: 8px;
                    box-shadow: 0 1px .5px rgba(11,20,26,.13); cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 6px; }
  .botones button:hover { background: #f7f8fa; }
  .lista-btn { display: flex; align-items: center; justify-content: center; gap: 6px; border-top: 1px solid var(--wa-linea); margin: 8px -8px -8px -9px;
               padding: 10px; color: var(--wa-azul); font-size: 14.2px; cursor: pointer; border-radius: 0 0 8px 8px; }
  .lista-btn:hover { background: #f7f8fa; }
  /* Documento */
  .doc { display: flex; align-items: center; gap: 10px; background: rgba(11,20,26,.05); border-radius: 6px; padding: 10px; margin-bottom: 2px; min-width: 230px; }
  .doc .bi { font-size: 28px; color: #e2574c; }
  .doc div { min-width: 0; }
  .doc b { display: block; font-weight: 400; font-size: 14px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 180px; }
  .doc small { color: var(--wa-meta); font-size: 12px; }
  /* Escribiendo */
  .escribiendo { display: inline-flex; gap: 4px; padding: 4px 2px; }
  .escribiendo span { width: 7px; height: 7px; border-radius: 50%; background: #9aa5ab; animation: punto 1.2s infinite; }
  .escribiendo span:nth-child(2) { animation-delay: .2s; } .escribiendo span:nth-child(3) { animation-delay: .4s; }
  @keyframes punto { 0%, 60%, 100% { opacity: .35; transform: translateY(0); } 30% { opacity: 1; transform: translateY(-3px); } }

  /* Barra de escritura */
  .entrada { display: flex; align-items: flex-end; gap: 6px; padding: 6px 6px 8px; background: transparent; flex-shrink: 0; }
  .campo { flex: 1; display: flex; align-items: flex-end; background: #fff; border-radius: 24px; padding: 4px 6px 4px 14px; box-shadow: 0 1px .5px rgba(11,20,26,.13); min-height: 46px; }
  .campo textarea { flex: 1; border: 0; outline: 0; resize: none; font: inherit; font-size: 15px; line-height: 20px; max-height: 110px; padding: 9px 0; background: transparent; color: var(--wa-texto); }
  .campo button { border: 0; background: none; color: #54656f; font-size: 21px; padding: 8px; cursor: pointer; border-radius: 50%; }
  .enviar { width: 48px; height: 48px; border-radius: 50%; border: 0; background: var(--wa-verde-2); color: #fff; font-size: 20px; cursor: pointer; flex-shrink: 0; display: grid; place-items: center; }
  .enviar.grabando { background: #d33; }

  /* Selector de lista */
  .velo { position: absolute; inset: 0; background: rgba(11,20,26,.4); display: none; z-index: 30; }
  .velo.abierto { display: block; }
  .hoja { position: absolute; left: 0; right: 0; bottom: 0; background: #fff; border-radius: 14px 14px 0 0; max-height: 75%; display: flex; flex-direction: column; transform: translateY(100%); transition: transform .22s ease; z-index: 31; }
  .velo.abierto + .hoja { transform: none; }
  .hoja-cab { display: flex; align-items: center; gap: 14px; padding: 16px; border-bottom: 1px solid var(--wa-linea); font-size: 17px; }
  .hoja-cab button { border: 0; background: none; font-size: 20px; color: #54656f; cursor: pointer; padding: 0; }
  .hoja-cuerpo { overflow-y: auto; padding-bottom: 76px; }
  .hoja-seccion { color: var(--wa-verde); font-size: 14px; padding: 14px 20px 6px; }
  .fila { display: flex; align-items: center; gap: 12px; padding: 12px 20px; cursor: pointer; }
  .fila:hover { background: #f5f6f6; }
  .fila div { flex: 1; min-width: 0; }
  .fila b { display: block; font-weight: 400; font-size: 15.5px; }
  .fila small { color: var(--wa-meta); font-size: 13.5px; }
  .radio { width: 20px; height: 20px; border: 2px solid #8696a0; border-radius: 50%; flex-shrink: 0; display: grid; place-items: center; }
  .fila.sel .radio { border-color: var(--wa-verde-2); }
  .fila.sel .radio::after { content: ''; width: 10px; height: 10px; border-radius: 50%; background: var(--wa-verde-2); }
  .hoja-enviar { position: absolute; right: 18px; bottom: 18px; width: 56px; height: 56px; border-radius: 16px; border: 0; background: var(--wa-verde-2); color: #fff; font-size: 22px; cursor: pointer; display: none; box-shadow: 0 2px 8px rgba(0,0,0,.25); }
  .hoja-enviar.visible { display: grid; place-items: center; }

  @media (max-width: 520px) {
    body { display: block; background: var(--wa-fondo); }
    .telefono { width: 100%; height: 100vh; height: 100dvh; border-radius: 0; box-shadow: none; }
    .barra-sistema, .pie-demo { display: none; }
  }
</style>
</head>
<body>
<div class="telefono" id="telefono">
  <div class="barra-sistema"><span id="reloj">{{ now()->format('H:i') }}</span><span><i class="bi bi-reception-4"></i><i class="bi bi-wifi"></i><i class="bi bi-battery-full"></i></span></div>

  <div class="cabecera">
    <a class="icono" href="{{ url('/') }}" title="Ir a RCT web" style="text-decoration:none"><i class="bi bi-arrow-left"></i></a>
    <div class="avatar">CT</div>
    <div class="contacto">
      <b>Caja Trujillo · Recuperaciones <i class="bi bi-patch-check-fill" title="Cuenta de empresa verificada"></i></b>
      <small id="subtitulo">Cuenta de empresa</small>
    </div>
    <button class="icono" type="button" id="btn-opciones" aria-label="Más opciones"><i class="bi bi-three-dots-vertical"></i></button>
    <div class="menu-opc" id="menu-opc">
      <button type="button" data-texto="menú">Menú principal</button>
      <button type="button" id="vaciar">Vaciar chat</button>
      <a href="{{ url('/') }}" target="_blank">Abrir RCT web</a>
    </div>
  </div>

  <div class="chat" id="chat">
    <div class="chip aviso"><i class="bi bi-lock-fill"></i> Las solicitudes y consultas de este canal quedan registradas en el sistema RCT de Caja Trujillo.</div>
    <div class="chip">Hoy</div>
  </div>

  <form class="entrada" id="form" autocomplete="off">
    <div class="campo">
      <textarea id="texto" rows="1" placeholder="Mensaje"></textarea>
      <button type="button" id="btn-adjuntar" aria-label="Adjuntar documento"><i class="bi bi-paperclip"></i></button>
      <input type="file" id="archivo" accept=".pdf,.jpg,.jpeg,.png" hidden>
    </div>
    <button class="enviar" id="btn-enviar" type="submit" aria-label="Enviar"><i class="bi bi-mic-fill"></i></button>
  </form>

  <div class="velo" id="velo"></div>
  <div class="hoja" id="hoja" role="dialog" aria-modal="true">
    <div class="hoja-cab"><button type="button" id="cerrar-hoja" aria-label="Cerrar"><i class="bi bi-x-lg"></i></button><span id="hoja-titulo">Opciones</span></div>
    <div class="hoja-cuerpo" id="hoja-cuerpo"></div>
    <button class="hoja-enviar" id="hoja-enviar" type="button" aria-label="Enviar"><i class="bi bi-send-fill"></i></button>
  </div>
</div>
<div class="pie-demo">Canal WhatsApp de RCT · {{ $numero }} · operando como {{ config('rct.whatsapp.usuario') }} · <a href="{{ url('/') }}">RCT web</a></div>

<script>
(function () {
  const URL_MSG = '{{ route('whatsapp.mensaje') }}', URL_HIST = '{{ route('whatsapp.historial') }}', URL_RESET = '{{ route('whatsapp.reiniciar') }}';
  const CSRF = document.querySelector('meta[name=csrf-token]').content;
  const chat = document.getElementById('chat'), texto = document.getElementById('texto'), btnEnviar = document.getElementById('btn-enviar');
  const subtitulo = document.getElementById('subtitulo');
  let ocupado = false;

  const esc = s => String(s ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
  // Formato de WhatsApp: *negrita*, _cursiva_, ~tachado~
  const formato = s => esc(s)
    .replace(/\*([^*\n]+)\*/g, '<strong>$1</strong>')
    .replace(/(^|\s)_([^_\n]+)_/g, '$1<em>$2</em>')
    .replace(/~([^~\n]+)~/g, '<s>$1</s>')
    .replace(/\n/g, '<br>');
  const ahora = () => new Date().toLocaleTimeString('es-PE', { hour: '2-digit', minute: '2-digit', hour12: false });
  const bajar = () => chat.scrollTop = chat.scrollHeight;

  function pintar(m) {
    const g = document.createElement('div');
    g.className = 'grupo ' + (m.lado === 'yo' ? 'yo' : 'bot');
    const hora = `<span class="hora">${esc(m.hora || ahora())}${m.lado === 'yo' ? '<i class="bi bi-check2-all"></i>' : ''}</span>`;
    let cuerpo;
    if (m.tipo === 'documento') {
      cuerpo = `<div class="doc"><i class="bi bi-file-earmark-pdf-fill"></i><div><b>${esc(m.texto)}</b><small>${esc(m.meta || '')}</small></div></div>${hora}`;
    } else {
      cuerpo = `${formato(m.texto)}${hora}`;
      if (m.tipo === 'lista') cuerpo += `<div class="lista-btn" data-lista><i class="bi bi-list-ul"></i>${esc(m.boton)}</div>`;
    }
    g.innerHTML = `<div class="burbuja">${cuerpo}</div>`;
    if (m.tipo === 'botones') {
      const b = document.createElement('div'); b.className = 'botones';
      m.botones.forEach(x => {
        const el = document.createElement('button'); el.type = 'button';
        el.innerHTML = `<i class="bi bi-reply-fill"></i>${esc(x.titulo)}`;
        el.addEventListener('click', () => enviar({ accion: x.id, etiqueta: x.titulo }));
        b.appendChild(el);
      });
      g.appendChild(b);
    }
    if (m.tipo === 'lista') g.querySelector('[data-lista]').addEventListener('click', () => abrirLista(m));
    chat.appendChild(g);
    bajar();
  }

  function escribiendo(si) {
    subtitulo.textContent = si ? 'escribiendo…' : 'Cuenta de empresa';
    const previo = document.getElementById('typing');
    if (!si) { previo && previo.remove(); return; }
    if (previo) return;
    const g = document.createElement('div'); g.className = 'grupo bot'; g.id = 'typing';
    g.innerHTML = '<div class="burbuja"><span class="escribiendo"><span></span><span></span><span></span></span></div>';
    chat.appendChild(g); bajar();
  }
  const pausa = ms => new Promise(r => setTimeout(r, ms));

  async function enviar({ texto: t = null, accion = null, etiqueta = null, archivo = null }, silencioso = false) {
    if (ocupado) return;
    ocupado = true;
    if (!silencioso) {
      if (archivo) pintar({ lado: 'yo', tipo: 'documento', texto: archivo.name, meta: archivo.name.split('.').pop().toUpperCase() + ' · ' + Math.max(1, Math.round(archivo.size / 1024)) + ' KB' });
      else pintar({ lado: 'yo', tipo: 'texto', texto: etiqueta || t });
    }
    const fd = new FormData();
    if (t) fd.append('texto', t);
    if (accion) fd.append('accion', accion);
    if (etiqueta) fd.append('etiqueta', etiqueta);
    if (archivo) fd.append('archivo', archivo);

    await pausa(350);
    escribiendo(true);
    const inicio = Date.now();
    try {
      const res = await fetch(URL_MSG, { method: 'POST', body: fd, headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' } });
      const data = await res.json();
      if (!res.ok) throw new Error(data.message || 'error');
      await pausa(Math.max(0, 800 - (Date.now() - inicio)));
      for (let i = 0; i < data.mensajes.length; i++) {
        escribiendo(false); pintar(data.mensajes[i]);
        if (i < data.mensajes.length - 1) { escribiendo(true); await pausa(700); }
      }
    } catch (e) {
      escribiendo(false);
      pintar({ lado: 'bot', tipo: 'texto', texto: 'No pudimos procesar su mensaje en este momento. Intente nuevamente.' });
    }
    escribiendo(false);
    ocupado = false;
  }

  // ── Mensaje de lista ───────────────────────────────────────
  let seleccion = null;
  const velo = document.getElementById('velo'), hojaEnviar = document.getElementById('hoja-enviar');
  function abrirLista(m) {
    seleccion = null; hojaEnviar.classList.remove('visible');
    document.getElementById('hoja-titulo').textContent = m.boton;
    const cuerpo = document.getElementById('hoja-cuerpo'); cuerpo.innerHTML = '';
    m.secciones.forEach(s => {
      cuerpo.insertAdjacentHTML('beforeend', `<div class="hoja-seccion">${esc(s.titulo)}</div>`);
      s.filas.forEach(f => {
        const el = document.createElement('div'); el.className = 'fila';
        el.innerHTML = `<div><b>${esc(f.titulo)}</b>${f.descripcion ? `<small>${esc(f.descripcion)}</small>` : ''}</div><span class="radio"></span>`;
        el.addEventListener('click', () => {
          cuerpo.querySelectorAll('.fila').forEach(x => x.classList.remove('sel'));
          el.classList.add('sel'); seleccion = f; hojaEnviar.classList.add('visible');
        });
        cuerpo.appendChild(el);
      });
    });
    velo.classList.add('abierto');
  }
  const cerrarLista = () => velo.classList.remove('abierto');
  velo.addEventListener('click', cerrarLista);
  document.getElementById('cerrar-hoja').addEventListener('click', cerrarLista);
  hojaEnviar.addEventListener('click', () => { if (!seleccion) return; cerrarLista(); enviar({ accion: seleccion.id, etiqueta: seleccion.titulo }); });

  // ── Escritura, adjuntos y voz ──────────────────────────────
  const ajustar = () => {
    texto.style.height = 'auto'; texto.style.height = Math.min(texto.scrollHeight, 110) + 'px';
    btnEnviar.innerHTML = texto.value.trim() ? '<i class="bi bi-send-fill"></i>' : '<i class="bi bi-mic-fill"></i>';
  };
  texto.addEventListener('input', ajustar);
  texto.addEventListener('keydown', e => { if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); document.getElementById('form').requestSubmit(); } });

  let rec = null;
  document.getElementById('form').addEventListener('submit', e => {
    e.preventDefault();
    const t = texto.value.trim();
    if (t) { texto.value = ''; ajustar(); enviar({ texto: t }); return; }
    // Botón de micrófono: dictado por voz
    const SR = window.SpeechRecognition || window.webkitSpeechRecognition;
    if (!SR) { pintar({ lado: 'bot', tipo: 'texto', texto: 'El dictado por voz no está disponible en este navegador. Escriba su mensaje.' }); return; }
    if (rec) { rec.stop(); return; }
    rec = new SR(); rec.lang = 'es-PE'; rec.interimResults = false;
    rec.onstart = () => btnEnviar.classList.add('grabando');
    rec.onresult = ev => { texto.value = ev.results[0][0].transcript; ajustar(); texto.focus(); };
    rec.onend = rec.onerror = () => { btnEnviar.classList.remove('grabando'); rec = null; };
    rec.start();
  });

  const archivo = document.getElementById('archivo');
  document.getElementById('btn-adjuntar').addEventListener('click', () => archivo.click());
  archivo.addEventListener('change', () => { if (archivo.files[0]) enviar({ archivo: archivo.files[0] }); archivo.value = ''; });

  // ── Menú de opciones ───────────────────────────────────────
  const menu = document.getElementById('menu-opc');
  document.getElementById('btn-opciones').addEventListener('click', e => { e.stopPropagation(); menu.classList.toggle('abierto'); });
  document.addEventListener('click', () => menu.classList.remove('abierto'));
  menu.querySelector('[data-texto]').addEventListener('click', () => enviar({ texto: 'menú' }));
  document.getElementById('vaciar').addEventListener('click', async () => {
    await fetch(URL_RESET, { method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' } });
    chat.querySelectorAll('.grupo').forEach(g => g.remove());
    enviar({ accion: 'inicio' }, true);
  });

  setInterval(() => document.getElementById('reloj').textContent = ahora(), 30000);

  // ── Inicio: recupera la conversación o saluda ──────────────
  fetch(URL_HIST, { headers: { Accept: 'application/json' } }).then(r => r.json()).then(d => {
    if (d.mensajes && d.mensajes.length) d.mensajes.forEach(pintar);
    else enviar({ accion: 'inicio' }, true);
  });
})();
</script>
</body>
</html>
