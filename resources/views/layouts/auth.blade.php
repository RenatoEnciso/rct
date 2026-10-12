<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title', 'Acceso') · RCT Recuperaciones · Caja Trujillo</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
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

    <div>
      <h1>Sistema de Recuperaciones RCT</h1>
      <p>Gestión de campañas de conciliación y expedientes de programas de gobierno, con trazabilidad de cada decisión.</p>
      <ul class="auth-points">
        <li><i class="bi bi-diagram-3"></i><span>Aprobación según el nivel de autonomía de cada operación.</span></li>
        <li><i class="bi bi-shield-check"></i><span>Validación automática de sustentos y documentos.</span></li>
        <li><i class="bi bi-graph-up"></i><span>Indicadores de la campaña por zona y tienda.</span></li>
      </ul>
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
