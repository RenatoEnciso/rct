<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title', 'Inicio') · RCT Recuperaciones · Caja Trujillo</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('assets/css/rct.css') }}?v={{ filemtime(public_path('assets/css/rct.css')) }}">
@stack('styles')
</head>
<body>
@php
  $user = auth()->user();
  $pendBandeja = $user->esAprobador()
      ? \App\Models\PropuestaFud::porAprobador($user->id)->enRevision()->count() : 0;
  $pendSustentos = $user->esGrupoFud()
      ? \App\Models\Sustento::where('cargado_por', $user->id)->whereIn('estado', ['Pendiente', 'Observado'])->count() : 0;
  $iniciales = collect(explode(' ', trim($user->name)))->filter()->take(2)
      ->map(fn($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('');
@endphp

{{-- Encabezado --}}
<header class="rct-header">
  <div class="d-flex align-items-center gap-3">
    <button class="navbar-toggler-rct d-lg-none" id="sidebarToggle" type="button" aria-label="Abrir menú">
      <i class="bi bi-list"></i>
    </button>
    <a href="{{ route('dashboard') }}" class="brand">
      <div class="brand-logo-mark">CT</div>
      <div>
        <div class="brand-name">Caja Trujillo</div>
        <div class="brand-sub">RCT · Sistema de Recuperaciones</div>
      </div>
    </a>
  </div>

  <div class="header-right">
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
      <a class="nav-btn {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
        <i class="bi bi-grid"></i> Panel principal
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
      @endif

      @if($user->esAprobador())
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
        <a class="nav-btn {{ request()->routeIs('cartera.*') ? 'active' : '' }}" href="{{ route('cartera.exclusiones') }}">
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
