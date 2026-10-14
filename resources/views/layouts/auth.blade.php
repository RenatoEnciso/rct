<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title', 'Acceso') · RCT Recuperaciones · Caja Trujillo</title>
<link href="{{ asset('vendor/inter/inter.css') }}" rel="stylesheet">
<link href="{{ asset('vendor/bootstrap-icons/bootstrap-icons.min.css') }}" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('assets/css/rct.css') }}?v={{ filemtime(public_path('assets/css/rct.css')) }}">
</head>
<body>
<div class="auth-shell">
  <section class="auth-aside" aria-hidden="true">
    <div class="brand">
      <div class="brand-logo-mark">CT</div>
      <div>
        <div class="brand-name">Caja Trujillo</div>
        <div class="brand-sub">Departamento de Recuperaciones</div>
      </div>
    </div>

    <div class="auth-centro">
      <h1>Sistema de Recuperaciones</h1>
      <p>Cada expediente avanza etapa por etapa, con el aprobador que le corresponde y la trazabilidad de cada decisión.</p>
      <ol class="auth-circuito">
        <li><span class="friso-m hecho"></span><span><strong>Priorizar</strong> la cartera del día</span></li>
        <li><span class="friso-m hecho"></span><span><strong>Proponer</strong> la conciliación</span></li>
        <li><span class="friso-m hecho"></span><span><strong>Aprobar</strong> según el nivel de autonomía</span></li>
        <li><span class="friso-m actual"></span><span><strong>Sustentar</strong> con validación automática</span></li>
        <li><span class="friso-m"></span><span><strong>Trazar</strong> y reportar a la Gerencia y a COFIDE</span></li>
      </ol>
    </div>

    <div class="auth-legal">Uso exclusivo del personal autorizado de Caja Municipal de Ahorro y Crédito de Trujillo.</div>
  </section>

  <main class="auth-main">
    <div class="auth-card">
      @yield('content')
    </div>
  </main>
</div>
@stack('scripts')
</body>
</html>
