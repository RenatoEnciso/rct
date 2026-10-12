<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title', 'RCT Recuperaciones') – Caja Trujillo</title>

{{-- Fuente --}}
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

{{-- Bootstrap 5 CSS --}}
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

{{-- Lucide (una sola carga, al final del head) --}}
<link rel="stylesheet" href="{{ asset('assets/css/rct.css') }}">
@stack('styles')
</head>
<body>

{{-- ── HEADER ── --}}
<header class="rct-header">
  <div class="d-flex align-items-center gap-3">

    {{-- Toggler mobile --}}
    <button class="navbar-toggler-rct d-lg-none"
            id="sidebarToggle"
            type="button"
            aria-label="Abrir menú">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none"
           stroke="currentColor" stroke-width="2.5">
        <line x1="3" y1="6" x2="21" y2="6"/>
        <line x1="3" y1="12" x2="21" y2="12"/>
        <line x1="3" y1="18" x2="21" y2="18"/>
      </svg>
    </button>

    <div class="brand-logo-mark">CT</div>
    <div>
      <div class="brand-name">CAJA TRUJILLO</div>
      <div class="brand-sub">Campaña: Sin Atrasos que Atrasan</div>
    </div>
  </div>

  <div class="header-right">
    <div class="user-chip">
      <div class="user-avatar">
        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
      </div>
      <div class="d-none d-sm-block">
        <div class="user-name">{{ strtoupper(auth()->user()->name) }}</div>
        <div class="user-rol">{{ auth()->user()->rol->nombre }}</div>
      </div>
    </div>

    <form method="POST" action="{{ route('logout') }}">
      @csrf
      <button type="submit" class="btn-logout">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none"
             stroke="currentColor" stroke-width="2.5">
          <path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/>
          <polyline points="16 17 21 12 16 7"/>
          <line x1="21" y1="12" x2="9" y2="12"/>
        </svg>
        <span class="d-none d-sm-inline">Salir</span>
      </button>
    </form>
  </div>
</header>

{{-- ── LAYOUT ── --}}
<div class="rct-layout">

  {{-- Backdrop mobile --}}
  <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

  {{-- SIDEBAR --}}
  <aside class="rct-sidebar" id="sidebar">

    @php $user = auth()->user(); @endphp

    <div class="nav-section-label">Gestión de Campaña</div>

    <a class="nav-btn {{ request()->routeIs('dashboard') ? 'active' : '' }}"
       href="{{ route('dashboard') }}">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
        <rect x="3" y="3" width="7" height="7" rx="1"/>
        <rect x="14" y="3" width="7" height="7" rx="1"/>
        <rect x="3" y="14" width="7" height="7" rx="1"/>
        <rect x="14" y="14" width="7" height="7" rx="1"/>
      </svg>
      Panel Principal
    </a>
    <a class="nav-btn {{ request()->routeIs('lineamientos') ? 'active' : '' }}"
       href="{{ route('lineamientos') }}">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
        <rect x="3" y="3" width="7" height="7" rx="1"/>
        <rect x="14" y="3" width="7" height="7" rx="1"/>
        <rect x="3" y="14" width="7" height="7" rx="1"/>
        <rect x="14" y="14" width="7" height="7" rx="1"/>
      </svg>
      Lineamientos
    </a>

    @if($user->esGrupoFud())
      <a class="nav-btn {{ request()->routeIs('fud.create') ? 'active' : '' }}"
         href="{{ route('fud.create') }}">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
          <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/>
          <polyline points="17 8 12 3 7 8"/>
          <line x1="12" y1="3" x2="12" y2="15"/>
        </svg>
        Envío de FUD
      </a>

      <a class="nav-btn {{ request()->routeIs('fud.index') ? 'active' : '' }}"
         href="{{ route('fud.index') }}">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
          <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2"/>
          <rect x="9" y="3" width="6" height="4" rx="1"/>
          <line x1="9" y1="12" x2="15" y2="12"/>
          <line x1="9" y1="16" x2="12" y2="16"/>
        </svg>
        Mis Propuestas
      </a>
    @endif

    @if($user->esAprobador())
    <a class="nav-btn {{ request()->routeIs('reportes.*') ? 'active' : '' }}"
   href="{{ route('reportes.dashboard') }}">
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
    <rect x="2" y="3" width="6" height="18" rx="1"/>
    <rect x="9" y="8" width="6" height="13" rx="1"/>
    <rect x="16" y="13" width="6" height="8" rx="1"/>
  </svg>
  Reporte Campaña
</a>
      <a class="nav-btn {{ request()->routeIs('bandeja.*') ? 'active' : '' }}"
         href="{{ route('bandeja.index') }}">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
          <circle cx="12" cy="12" r="10"/>
          <polyline points="9 12 11 14 15 10"/>
        </svg>
        Bandeja de Aprobación
      </a>
      <a class="nav-btn {{ request()->routeIs('revision.index', 'revision.show') ? 'active' : '' }}"
         href="{{ route('revision.index') }}">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
          <circle cx="12" cy="12" r="10"/>
          <polyline points="9 12 11 14 15 10"/>
        </svg>
        Revisión
      </a>
            <a class="nav-btn {{ request()->routeIs('revision.dashboard') ? 'active' : '' }}"
         href="{{ route('revision.dashboard') }}">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
    <rect x="2" y="3" width="6" height="18" rx="1"/>
    <rect x="9" y="8" width="6" height="13" rx="1"/>
    <rect x="16" y="13" width="6" height="8" rx="1"/>
  </svg>
        Reporte Conciliaciones
      </a>
      <a class="nav-btn {{ request()->routeIs('indicadores.*') ? 'active' : '' }}"
         href="{{ route('indicadores.index') }}">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
          <line x1="18" y1="20" x2="18" y2="10"/>
          <line x1="12" y1="20" x2="12" y2="4"/>
          <line x1="6" y1="20" x2="6" y2="14"/>
        </svg>
        Indicadores
      </a>

      <a class="nav-btn {{ request()->routeIs('cartera.*') ? 'active' : '' }}"
         href="{{ route('cartera.exclusiones') }}">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
          <circle cx="12" cy="12" r="10"/>
          <line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/>
        </svg>
        Exclusiones
      </a>
    @endif

    @if($user->esGrupoFud())
    <a class="nav-btn {{ request()->routeIs('sustentos.*') ? 'active' : '' }}"
       href="{{ route('sustentos.index') }}">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
        <path d="M22 19a2 2 0 01-2 2H4a2 2 0 01-2-2V5a2 2 0 012-2h5l2 3h9a2 2 0 012 2z"/>
      </svg>
      Carga de Sustentos
    </a>
    @endif

    <a class="nav-btn {{ request()->routeIs('seguimiento.*') ? 'active' : '' }}"
       href="{{ route('seguimiento.index') }}">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
        <circle cx="11" cy="11" r="8"/>
        <line x1="21" y1="21" x2="16.65" y2="16.65"/>
      </svg>
      Seguimiento
    </a>

    <div class="nav-section-label" style="margin-top:12px">Sesión</div>

    <form method="POST" action="{{ route('logout') }}">
      @csrf
      <button type="submit" class="nav-btn nav-btn--danger">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
          <path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/>
          <polyline points="16 17 21 12 16 7"/>
          <line x1="21" y1="12" x2="9" y2="12"/>
        </svg>
        Cerrar sesión
      </button>
    </form>
  </aside>

  {{-- MAIN --}}
  <main class="rct-main">

    {{-- Alertas globales --}}
    @if(session('success'))
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        ✅ {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    @endif

    @if(session('warning'))
      <div class="alert alert-warning alert-dismissible fade show" role="alert">
        ⚠️ {{ session('warning') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    @endif

    @if(session('error'))
      <div class="alert alert-danger alert-dismissible fade show" role="alert">
        ❌ {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    @endif

    @if($errors->any())
      <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <strong>Por favor corrige los siguientes errores:</strong>
        <ul class="mb-0 mt-1" style="font-size:13px">
          @foreach($errors->all() as $e)
            <li>{{ $e }}</li>
          @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    @endif

    @yield('content')
  </main>

</div>{{-- .rct-layout --}}

{{-- Bootstrap 5 JS --}}
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

{{-- Sidebar toggle mobile --}}
<script>
(function () {
  const toggle   = document.getElementById('sidebarToggle');
  const sidebar  = document.getElementById('sidebar');
  const backdrop = document.getElementById('sidebarBackdrop');

  function open()  { sidebar.classList.add('open');  backdrop.classList.add('open');  }
  function close() { sidebar.classList.remove('open'); backdrop.classList.remove('open'); }

  toggle  ?.addEventListener('click',   () => sidebar.classList.contains('open') ? close() : open());
  backdrop?.addEventListener('click',   close);

  // Cierra al navegar en mobile
  sidebar.querySelectorAll('a.nav-btn').forEach(a =>
    a.addEventListener('click', () => { if (window.innerWidth < 992) close(); })
  );
})();
</script>

@stack('scripts')
{{-- ── Asistente flotante ──────────────────────────────────── --}}
@auth
<div id="asistente-widget" style="position:fixed;bottom:24px;right:24px;z-index:9999;font-family:inherit">

  {{-- Burbuja de chat --}}
  <div id="asistente-box"
       style="display:none;width:340px;background:#fff;border-radius:16px;
              box-shadow:0 8px 32px rgba(0,0,0,0.18);overflow:hidden;
              border:1px solid var(--borde);margin-bottom:12px">

    {{-- Header --}}
    <div style="background:var(--rojo);padding:14px 16px;
                display:flex;align-items:center;justify-content:space-between">
      <div style="display:flex;align-items:center;gap:10px">
        <div style="width:32px;height:32px;background:rgba(255,255,255,0.2);
                    border-radius:50%;display:flex;align-items:center;justify-content:center">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
               stroke="white" stroke-width="2">
            <path d="M12 2a10 10 0 100 20A10 10 0 0012 2z"/>
            <path d="M12 16v-4M12 8h.01"/>
          </svg>
        </div>
        <div>
          <div style="color:white;font-weight:700;font-size:13px">Asistente RCT</div>
          <div style="color:rgba(255,255,255,0.75);font-size:10px">Powered by Gemini</div>
        </div>
      </div>
      <button onclick="toggleAsistente()"
              style="background:none;border:none;cursor:pointer;
                     color:white;font-size:20px;line-height:1;padding:0">×</button>
    </div>

    {{-- Mensajes --}}
    <div id="asistente-mensajes"
         style="height:300px;overflow-y:auto;padding:16px;
                display:flex;flex-direction:column;gap:10px;background:#f8f9fa">
      <div class="msg-bot">
        👋 Hola, <strong>{{ auth()->user()->name }}</strong>.<br>
        Soy tu asistente RCT. Puedo contarte el estado de tus FUDs,
        clientes disponibles y más. ¿En qué te ayudo?
      </div>
    </div>

    {{-- Input ── ÚNICO, sin duplicado --}}
    <div style="padding:12px;border-top:1px solid var(--borde);
                display:flex;gap:8px;background:#fff;align-items:center">

      <input id="asistente-input" type="text"
             placeholder="Escribe o habla..."
             style="flex:1;border:1px solid #dee2e6;border-radius:8px;
                    padding:8px 12px;font-size:13px;outline:none"
             onkeydown="if(event.key==='Enter') enviarMensaje()">

      {{-- Micrófono --}}
      <button id="btn-mic" onclick="toggleMic()"
              title="Hablar"
              style="background:#f0f0f0;border:none;border-radius:8px;
                     padding:8px 11px;cursor:pointer;font-size:15px;
                     flex-shrink:0">
        🎙️
      </button>

      {{-- Enviar --}}
      <button onclick="enviarMensaje()"
              style="background:#c0392b;color:white;border:none;
                     border-radius:8px;padding:8px 14px;cursor:pointer;
                     font-size:14px;font-weight:700;flex-shrink:0">➤</button>
    </div>
  </div>

  {{-- Botón flotante --}}
  <button onclick="toggleAsistente()"
          id="asistente-btn"
          style="width:52px;height:52px;border-radius:50%;
                 background:var(--rojo);color:white;border:none;
                 cursor:pointer;box-shadow:0 4px 16px rgba(180,0,0,0.35);
                 display:flex;align-items:center;justify-content:center;
                 margin-left:auto;transition:transform 0.2s"
          onmouseover="this.style.transform='scale(1.08)'"
          onmouseout="this.style.transform='scale(1)'">
    <svg width="22" height="22" viewBox="0 0 24 24" fill="none"
         stroke="white" stroke-width="2">
      <path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/>
    </svg>
  </button>
</div>

<style>
.msg-bot {
  background: white;
  border: 1px solid #e8e8e8;
  border-radius: 12px 12px 12px 2px;
  padding: 10px 13px;
  font-size: 12.5px;
  color: #333;
  max-width: 90%;
  line-height: 1.5;
}
.msg-user {
  background: var(--rojo);
  color: white;
  border-radius: 12px 12px 2px 12px;
  padding: 10px 13px;
  font-size: 12.5px;
  max-width: 90%;
  align-self: flex-end;
  line-height: 1.5;
}
</style>

<script>
const _CSRF = document.querySelector('meta[name=csrf-token]').content;

// ── Toggle del chat ─────────────────────────────────────────
function toggleAsistente() {
  const box = document.getElementById('asistente-box');
  const abierto = box.style.display === 'block';
  box.style.display = abierto ? 'none' : 'block';
  if (!abierto) document.getElementById('asistente-input').focus();
}

// ── Helpers de mensajes ─────────────────────────────────────
function _esc(s) {
  return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

function _msgBot(html) {
  return `<div class="msg-bot">${html}</div>`;
}

function _msgUser(texto) {
  return `<div class="msg-user">${_esc(texto)}</div>`;
}

// ── Enviar mensaje ──────────────────────────────────────────
let _enviando = false;

async function enviarMensaje() {
  if (_enviando) return;
  const input   = document.getElementById('asistente-input');
  const caja    = document.getElementById('asistente-mensajes');
  const mensaje = input.value.trim();
  if (!mensaje) return;

  _enviando = true;
  caja.innerHTML += _msgUser(mensaje);
  input.value = '';

  const tid = 'tp' + Date.now();
  caja.innerHTML += `<div id="${tid}" class="msg-bot" style="color:#999">⏳ Escribiendo...</div>`;
  caja.scrollTop = caja.scrollHeight;

  try {
    const res  = await fetch('{{ route("asistente.chat") }}', {
      method:  'POST',
      headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN': _CSRF },
      body:    JSON.stringify({ mensaje }),
    });
    const data = await res.json();
    document.getElementById(tid)?.remove();
    const respuesta = data.respuesta;
    caja.innerHTML += _msgBot(respuesta.replace(/\n/g,'<br>'));
    hablar(respuesta);
  } catch(e) {
    document.getElementById(tid)?.remove();
    caja.innerHTML += _msgBot('<span style="color:#c0392b">Error de conexión. Intenta de nuevo.</span>');
  }

  _enviando = false;
  caja.scrollTop = caja.scrollHeight;
}

// ── Voz: reconocimiento STT ─────────────────────────────────
let _rec = null;
let _micActivo = false;

function toggleMic() {
  if (!('webkitSpeechRecognition' in window || 'SpeechRecognition' in window)) {
    alert('Tu navegador no soporta reconocimiento de voz. Usa Chrome.');
    return;
  }

  // Si el mic está activo, detenerlo manualmente → onend limpia el estado
  if (_micActivo) {
    _rec?.stop();
    return;
  }

  const SR = window.SpeechRecognition || window.webkitSpeechRecognition;
  _rec = new SR();
  _rec.lang            = 'es-PE';
  _rec.interimResults  = false;
  _rec.maxAlternatives = 1;

  _rec.onstart = () => {
    _micActivo = true;
    const btn = document.getElementById('btn-mic');
    btn.textContent      = '⏹';        // ← indica "toca para detener"
    btn.title            = 'Detener';
    btn.style.background = '#fde8e8';
  };

  // ── CAMBIO CLAVE: solo llena el input, NO envía automáticamente ──
  _rec.onresult = (e) => {
    const texto = e.results[0][0].transcript;
    const input = document.getElementById('asistente-input');
    input.value = texto;
    input.focus();
    // El usuario revisa el texto y presiona Enter o ➤ para enviar
  };

  _rec.onend = () => {
    _micActivo = false;
    const btn = document.getElementById('btn-mic');
    btn.textContent      = '🎙️';
    btn.title            = 'Hablar';
    btn.style.background = '#f0f0f0';
  };

  _rec.onerror = (e) => {
    _micActivo = false;
    const btn = document.getElementById('btn-mic');
    btn.textContent      = '🎙️';
    btn.title            = 'Hablar';
    btn.style.background = '#f0f0f0';
    if (e.error !== 'no-speech') alert('Error de micrófono: ' + e.error);
  };

  _rec.start();
}

// ── Voz: síntesis TTS ───────────────────────────────────────
function hablar(texto) {
  if (!('speechSynthesis' in window)) return;
  window.speechSynthesis.cancel();

  const utt  = new SpeechSynthesisUtterance(texto);
  utt.lang   = 'es-PE';
  utt.rate   = 1.05;
  utt.pitch  = 1;

  const voces = window.speechSynthesis.getVoices();
  const voz   = voces.find(v => v.lang.startsWith('es')) ?? null;
  if (voz) utt.voice = voz;

  window.speechSynthesis.speak(utt);
}

// Precarga de voces en Chrome (carga asíncrona)
window.speechSynthesis.onvoiceschanged = () => {
  window.speechSynthesis.getVoices();
};
</script>
@endauth
</body>
</html>