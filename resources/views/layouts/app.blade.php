<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title', 'Inicio') · RCT Recuperaciones · Caja Trujillo</title>

<link href="{{ asset('vendor/inter/inter.css') }}" rel="stylesheet">
<link href="{{ asset('vendor/bootstrap/bootstrap.min.css') }}" rel="stylesheet">
<link href="{{ asset('vendor/bootstrap-icons/bootstrap-icons.min.css') }}" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('assets/css/rct.css') }}?v={{ filemtime(public_path('assets/css/rct.css')) }}">
@stack('styles')
</head>
<body>
<script>try { if (localStorage.getItem('rct.presentacion') === '1') document.body.classList.add('modo-presentacion'); } catch (e) {}</script>
@php
  $user = auth()->user();
  $pendBandeja = $user->esAprobador()
      ? \App\Models\PropuestaFud::porAprobador($user->id)->enRevision()->count() : 0;
  $pendSustentos = $user->esGrupoFud()
      ? \App\Models\Sustento::where('cargado_por', $user->id)->whereIn('estado', ['Pendiente', 'Observado'])->count() : 0;
  $iniciales = collect(explode(' ', trim($user->name)))->filter()->take(2)
      ->map(fn($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('');
  $notifs = app(\App\Services\Notificaciones::class)->paraUsuario($user);
  $notifNoLeidas = $notifs->where('leida', false);
  // Avisos emergentes: solo al ingresar al sistema
  $avisos = session()->pull('mostrar_avisos') ? $notifNoLeidas->take(4) : collect();
@endphp

{{-- Encabezado --}}
<header class="rct-header">
  <div class="d-flex align-items-center gap-3">
    <button class="navbar-toggler-rct d-lg-none" id="sidebarToggle" type="button" aria-label="Abrir menú">
      <i class="bi bi-list"></i>
    </button>
    <a href="{{ route('inicio') }}" class="brand">
      <div class="brand-logo-mark">CT</div>
      <div>
        <div class="brand-name">Caja Trujillo</div>
        <div class="brand-sub">RCT · Sistema de Recuperaciones</div>
      </div>
    </a>
  </div>

  <div class="header-right">
    {{-- Notificaciones --}}
    <div class="notif" id="notif">
      <button type="button" class="notif-btn" id="notif-btn" aria-label="Notificaciones" aria-expanded="false">
        <i class="bi bi-bell"></i>
        @if($notifNoLeidas->count())<span class="notif-count">{{ $notifNoLeidas->count() > 99 ? '99+' : $notifNoLeidas->count() }}</span>@endif
      </button>
      <div class="notif-panel" id="notif-panel" role="dialog" aria-label="Notificaciones">
        <div class="notif-head">
          <strong>Notificaciones</strong>
          @if($notifNoLeidas->count())
            <button type="button" class="notif-link" id="notif-todas">Marcar todo como leído</button>
          @endif
        </div>
        <div class="notif-tabs" role="tablist">
          @foreach(['' => 'Todas', 'decision' => 'Decisiones', 'solicitud' => 'Solicitudes', 'sustento' => 'Sustentos', 'gobierno' => 'Gobierno', 'recordatorio' => 'Recordatorios'] as $cat => $lbl)
            @php $nCat = $cat ? $notifs->where('categoria', $cat)->where('leida', false)->count() : $notifNoLeidas->count(); @endphp
            @if(!$cat || $notifs->where('categoria', $cat)->count())
              <button type="button" class="{{ $cat === '' ? 'active' : '' }}" data-cat="{{ $cat }}">{{ $lbl }}@if($nCat) <span>{{ $nCat }}</span>@endif</button>
            @endif
          @endforeach
        </div>
        <div class="notif-list">
          @forelse($notifs->take(30) as $n)
            <a href="{{ $n['url'] }}" class="notif-item {{ $n['leida'] ? '' : 'unread' }}" data-clave="{{ $n['clave'] }}" data-cat="{{ $n['categoria'] }}">
              <span class="notif-ico {{ $n['color'] }}"><i class="bi {{ $n['icono'] }}"></i></span>
              <span class="notif-body">
                <span class="notif-title">{{ $n['titulo'] }}</span>
                <span class="notif-msg">{{ $n['mensaje'] }}</span>
                <span class="notif-time">{{ $n['categoria'] === 'recordatorio' ? 'Recordatorio' : $n['fecha']->diffForHumans() }}</span>
              </span>
            </a>
          @empty
            <div class="empty-state"><i class="bi bi-bell-slash"></i><strong>Sin notificaciones</strong><span>Está al día.</span></div>
          @endforelse
        </div>
        <a href="{{ route('notificaciones.index') }}" class="notif-foot">Ver todas las notificaciones</a>
      </div>
    </div>

    <div class="user-chip">
      <div class="user-avatar" aria-hidden="true">{{ $iniciales }}</div>
      <div class="d-none d-sm-block">
        <div class="user-name">{{ $user->name }}</div>
        <div class="user-rol">{{ $user->rol->nombre }}</div>
      </div>
    </div>
    <form method="POST" action="{{ route('logout') }}">
      @csrf
      <button type="submit" class="btn-logout" title="Cerrar sesión">
        <i class="bi bi-box-arrow-right"></i>
        <span class="d-none d-md-inline">Salir</span>
      </button>
    </form>
  </div>
</header>

<div class="rct-layout">
  <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

  {{-- Menú lateral --}}
  <aside class="rct-sidebar" id="sidebar">
    <nav aria-label="Menú principal">
      <div class="nav-section-label">General</div>
      <a class="nav-btn {{ request()->routeIs('inicio') ? 'active' : '' }}" href="{{ route('inicio') }}">
        <i class="bi bi-house"></i> Inicio
      </a>
      <a class="nav-btn {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
        <i class="bi bi-grid"></i> Mi panel
      </a>
      <a class="nav-btn {{ request()->routeIs('notificaciones.*') ? 'active' : '' }}" href="{{ route('notificaciones.index') }}">
        <i class="bi bi-bell"></i> Notificaciones
        @if($notifNoLeidas->count()) <span class="nav-count">{{ $notifNoLeidas->count() }}</span> @endif
      </a>
      <a class="nav-btn {{ request()->routeIs('lineamientos') ? 'active' : '' }}" href="{{ route('lineamientos') }}">
        <i class="bi bi-journal-text"></i> Lineamientos
      </a>

      @if($user->esGrupoFud())
        <div class="nav-section-label">Gestión de campaña</div>
        <a class="nav-btn {{ request()->routeIs('fud.create') ? 'active' : '' }}" href="{{ route('fud.create') }}">
          <i class="bi bi-file-earmark-plus"></i> Nueva propuesta FUD
        </a>
        <a class="nav-btn {{ request()->routeIs('fud.index', 'fud.show', 'fud.edit') ? 'active' : '' }}" href="{{ route('fud.index') }}">
          <i class="bi bi-folder2-open"></i> Mis propuestas
        </a>
        <a class="nav-btn {{ request()->routeIs('sustentos.*') ? 'active' : '' }}" href="{{ route('sustentos.index') }}">
          <i class="bi bi-paperclip"></i> Carga de sustentos
          @if($pendSustentos) <span class="nav-count">{{ $pendSustentos }}</span> @endif
        </a>
        <a class="nav-btn {{ request()->routeIs('gobierno.*') ? 'active' : '' }}" href="{{ route('gobierno.index') }}">
          <i class="bi bi-bank"></i> Expedientes de gobierno
        </a>
      @endif

      @if($user->esAprobador())
        <div class="nav-section-label">Analítica</div>
        <a class="nav-btn {{ request()->routeIs('centro-control') ? 'active' : '' }}" href="{{ route('centro-control') }}">
          <i class="bi bi-display"></i> Centro de control
        </a>

        <div class="nav-section-label">Aprobación y control</div>
        <a class="nav-btn {{ request()->routeIs('bandeja.*') ? 'active' : '' }}" href="{{ route('bandeja.index') }}">
          <i class="bi bi-inbox"></i> Bandeja de aprobación
          @if($pendBandeja) <span class="nav-count">{{ $pendBandeja }}</span> @endif
        </a>
        <a class="nav-btn {{ request()->routeIs('aprobaciones.*') ? 'active' : '' }}" href="{{ route('aprobaciones.index') }}">
          <i class="bi bi-patch-check"></i> Mis aprobaciones
        </a>
        <a class="nav-btn {{ request()->routeIs('revision.index', 'revision.show') ? 'active' : '' }}" href="{{ route('revision.index') }}">
          <i class="bi bi-clipboard-check"></i> Revisión de sustentos
        </a>
        <a class="nav-btn {{ request()->routeIs('gobierno.*') ? 'active' : '' }}" href="{{ route('gobierno.index') }}">
          <i class="bi bi-bank"></i> Programas de gobierno
        </a>

        <div class="nav-section-label">Reportes</div>
        <a class="nav-btn {{ request()->routeIs('reportes.*') ? 'active' : '' }}" href="{{ route('reportes.dashboard') }}">
          <i class="bi bi-bar-chart-line"></i> Reporte de campaña
        </a>
        <a class="nav-btn {{ request()->routeIs('revision.dashboard') ? 'active' : '' }}" href="{{ route('revision.dashboard') }}">
          <i class="bi bi-file-earmark-bar-graph"></i> Reporte de conciliaciones
        </a>
        <a class="nav-btn {{ request()->routeIs('indicadores.*') ? 'active' : '' }}" href="{{ route('indicadores.index') }}">
          <i class="bi bi-speedometer2"></i> Indicadores
        </a>
      @endif

      <div class="nav-section-label">Trazabilidad</div>
      <a class="nav-btn {{ request()->routeIs('seguimiento.*') ? 'active' : '' }}" href="{{ route('seguimiento.index') }}">
        <i class="bi bi-clock-history"></i> Seguimiento
      </a>
    </nav>
  </aside>

  {{-- Contenido --}}
  <main class="rct-main">
    @if(session('success'))
      <div class="flash flash-success" role="status">
        <i class="bi bi-check-circle"></i><div class="flash-body">{{ session('success') }}</div>
        <button type="button" class="flash-close" aria-label="Cerrar"><i class="bi bi-x-lg"></i></button>
      </div>
    @endif
    @if(session('warning'))
      <div class="flash flash-warning" role="status">
        <i class="bi bi-exclamation-triangle"></i><div class="flash-body">{{ session('warning') }}</div>
        <button type="button" class="flash-close" aria-label="Cerrar"><i class="bi bi-x-lg"></i></button>
      </div>
    @endif
    @if(session('error'))
      <div class="flash flash-error" role="alert">
        <i class="bi bi-x-circle"></i><div class="flash-body">{{ session('error') }}</div>
        <button type="button" class="flash-close" aria-label="Cerrar"><i class="bi bi-x-lg"></i></button>
      </div>
    @endif
    @if($errors->any())
      <div class="flash flash-error" role="alert">
        <i class="bi bi-x-circle"></i>
        <div class="flash-body">
          <strong>Revise la información ingresada:</strong>
          <ul>@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
        <button type="button" class="flash-close" aria-label="Cerrar"><i class="bi bi-x-lg"></i></button>
      </div>
    @endif

    @yield('content')
  </main>
</div>

{{-- Avisos emergentes al ingresar --}}
@if($avisos->count())
  <div class="toasts" id="toasts" aria-live="polite">
    @foreach($avisos as $i => $n)
      <div class="toast-rct {{ $n['color'] }}" style="animation-delay: {{ $i * 350 }}ms">
        <span class="notif-ico {{ $n['color'] }}"><i class="bi {{ $n['icono'] }}"></i></span>
        <a href="{{ $n['url'] }}" class="toast-body" data-clave="{{ $n['clave'] }}">
          <strong>{{ $n['titulo'] }}</strong>
          <span>{{ \Illuminate\Support\Str::limit($n['mensaje'], 130) }}</span>
        </a>
        <button type="button" class="toast-close" aria-label="Cerrar"><i class="bi bi-x-lg"></i></button>
        <span class="toast-bar" style="animation-delay: {{ $i * 350 }}ms"></span>
      </div>
    @endforeach
    @if($notifNoLeidas->count() > $avisos->count())
      <button type="button" class="toast-more" onclick="document.getElementById('notif-btn').click()">
        Ver {{ $notifNoLeidas->count() - $avisos->count() }} notificación(es) más
      </button>
    @endif
  </div>
@endif

{{-- Asistente --}}
<div id="asistente-widget">
  <div class="asistente-box" id="asistente-box" role="dialog" aria-label="Asistente RCT">
    <div class="asistente-head">
      <div>
        <strong>Asistente RCT</strong>
        <small>Consultas sobre su gestión en la campaña</small>
      </div>
      <button type="button" onclick="toggleAsistente()" aria-label="Cerrar asistente"><i class="bi bi-x-lg"></i></button>
    </div>
    <div class="asistente-msgs" id="asistente-mensajes">
      <div class="msg-bot">
        Hola, {{ $user->name }}. Puedo informarle el estado de sus expedientes, pendientes
        y clientes disponibles en la campaña. ¿En qué puedo ayudarle?
      </div>
    </div>
    <div class="asistente-input">
      <input id="asistente-input" type="text" placeholder="Escriba su consulta" autocomplete="off"
             onkeydown="if(event.key==='Enter') enviarMensaje()">
      <button type="button" id="btn-mic" onclick="toggleMic()" title="Dictar por voz" aria-label="Dictar por voz"><i class="bi bi-mic"></i></button>
      <button type="button" class="send" onclick="enviarMensaje()" title="Enviar" aria-label="Enviar"><i class="bi bi-send"></i></button>
    </div>
  </div>
  <button type="button" class="asistente-fab" onclick="toggleAsistente()" aria-label="Abrir asistente">
    <i class="bi bi-chat-square-text"></i><span class="d-none d-sm-inline">Asistente</span>
  </button>
</div>

<script src="{{ asset('assets/js/rct.js') }}?v={{ filemtime(public_path('assets/js/rct.js')) }}"></script>
<script>
(function () {
  // ── Notificaciones ─────────────────────────────────────────
  var CSRF = document.querySelector('meta[name=csrf-token]').content;
  var panel = document.getElementById('notif-panel'), btn = document.getElementById('notif-btn');
  btn.addEventListener('click', function (e) {
    e.stopPropagation();
    var abierto = panel.classList.toggle('open');
    btn.setAttribute('aria-expanded', abierto);
  });
  document.addEventListener('click', function (e) { if (!e.target.closest('#notif')) panel.classList.remove('open'); });
  panel.querySelectorAll('.notif-tabs button').forEach(function (t) {
    t.addEventListener('click', function () {
      panel.querySelectorAll('.notif-tabs button').forEach(function (x) { x.classList.remove('active'); });
      t.classList.add('active');
      var cat = t.getAttribute('data-cat');
      panel.querySelectorAll('.notif-item').forEach(function (i) { i.style.display = !cat || i.getAttribute('data-cat') === cat ? '' : 'none'; });
    });
  });
  function leer(clave) {
    return fetch('{{ route('notificaciones.leer') }}', { method: 'POST', keepalive: true,
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
      body: JSON.stringify({ clave: clave }) });
  }
  // Al abrir una notificación se marca como leída
  document.addEventListener('click', function (e) {
    var a = e.target.closest('a[data-clave]');
    if (!a) return;
    e.preventDefault();
    leer(a.getAttribute('data-clave')).finally(function () { location.href = a.href; });
  });
  var todas = document.getElementById('notif-todas');
  todas && todas.addEventListener('click', function () {
    fetch('{{ route('notificaciones.leer-todas') }}', { method: 'POST', headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF } })
      .then(function () { location.reload(); });
  });
  // Avisos emergentes: se ocultan solos a los 8 segundos o al cerrarlos
  document.querySelectorAll('.toast-rct').forEach(function (t, i) {
    var cerrar = function () {
      t.classList.add('saliendo');
      setTimeout(function () { t.remove(); var c = document.getElementById('toasts'); if (c && !c.querySelector('.toast-rct')) c.remove(); }, 300);
    };
    t.querySelector('.toast-close').addEventListener('click', cerrar);
    setTimeout(cerrar, 8000 + i * 350);
  });
})();

(function () {
  var CSRF = document.querySelector('meta[name=csrf-token]').content;
  var enviando = false, rec = null, micActivo = false;

  window.toggleAsistente = function () {
    var box = document.getElementById('asistente-box');
    box.classList.toggle('open');
    if (box.classList.contains('open')) document.getElementById('asistente-input').focus();
  };

  function agregar(clase, html) {
    var caja = document.getElementById('asistente-mensajes');
    var div = document.createElement('div');
    div.className = clase; div.innerHTML = html;
    caja.appendChild(div); caja.scrollTop = caja.scrollHeight;
    return div;
  }

  window.enviarMensaje = async function () {
    if (enviando) return;
    var input = document.getElementById('asistente-input');
    var mensaje = input.value.trim();
    if (!mensaje) return;
    enviando = true;
    agregar('msg-user', rctEsc(mensaje));
    input.value = '';
    var espera = agregar('msg-bot pending', 'Procesando consulta…');
    try {
      var res = await fetch('{{ route('asistente.chat') }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        body: JSON.stringify({ mensaje: mensaje })
      });
      var data = await res.json();
      espera.remove();
      if (!res.ok) throw new Error();
      agregar('msg-bot', rctEsc(data.respuesta).replace(/\n/g, '<br>'));
      hablar(data.respuesta);
    } catch (e) {
      espera.remove();
      agregar('msg-bot error', 'No fue posible obtener una respuesta en este momento. Intente nuevamente.');
    }
    enviando = false;
  };

  window.toggleMic = function () {
    var SR = window.SpeechRecognition || window.webkitSpeechRecognition;
    if (!SR) { agregar('msg-bot', 'El dictado por voz no está disponible en este navegador.'); return; }
    if (micActivo) { rec && rec.stop(); return; }
    var btn = document.getElementById('btn-mic');
    rec = new SR(); rec.lang = 'es-PE'; rec.interimResults = false; rec.maxAlternatives = 1;
    rec.onstart = function () { micActivo = true; btn.classList.add('listening'); btn.innerHTML = '<i class="bi bi-stop-fill"></i>'; };
    rec.onresult = function (e) { var i = document.getElementById('asistente-input'); i.value = e.results[0][0].transcript; i.focus(); };
    rec.onend = rec.onerror = function () { micActivo = false; btn.classList.remove('listening'); btn.innerHTML = '<i class="bi bi-mic"></i>'; };
    rec.start();
  };

  function hablar(texto) {
    if (!('speechSynthesis' in window)) return;
    speechSynthesis.cancel();
    var u = new SpeechSynthesisUtterance(texto);
    u.lang = 'es-PE'; u.rate = 1.05;
    var voz = speechSynthesis.getVoices().find(function (v) { return v.lang.indexOf('es') === 0; });
    if (voz) u.voice = voz;
    speechSynthesis.speak(u);
  }
  if ('speechSynthesis' in window) speechSynthesis.onvoiceschanged = function () { speechSynthesis.getVoices(); };
})();
</script>
@stack('scripts')
</body>
</html>
