@extends('layouts.app')
@section('title', 'Inicio')

@section('content')
@php
  $h = (int) now()->format('G');
  $saludo = $h < 12 ? 'Buenos días' : ($h < 19 ? 'Buenas tardes' : 'Buenas noches');
  $pendientesHoy = collect($tareas)->filter(fn($t) => in_array($t[5], ['danger', 'warn']))->sum(fn($t) => $t[1]);
  $noLeidas = app(\App\Services\Notificaciones::class)->noLeidas($user);
  $inicial = fn($n) => collect(explode(' ', $n))->filter()->take(2)->map(fn($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('');
@endphp

{{-- Encabezado del portal --}}
<section class="portal-hero">
  <div class="hero-grid">
    <div>
      <h1>{{ $saludo }}, {{ $user->name }}</h1>
      <p>{{ $user->rol->nombre }}@if($user->tienda) · {{ $user->tienda->nombre }}, zona {{ $user->tienda->zona->nombre ?? '' }}@endif ·
        @if($pendientesHoy)
          tiene <strong>{{ $pendientesHoy }}</strong> tarea(s) que requieren su atención hoy.
        @else
          no tiene tareas urgentes pendientes. Buen trabajo.
        @endif
      </p>
      <div class="hero-actions">
        @if($user->esGrupoFud())
          <a href="{{ route('fud.create') }}" class="btn btn-hero"><i class="bi bi-file-earmark-plus"></i> Nueva propuesta</a>
          <a href="{{ route('sustentos.index') }}" class="btn btn-hero-ghost"><i class="bi bi-paperclip"></i> Cargar sustentos</a>
        @else
          <a href="{{ route('bandeja.index') }}" class="btn btn-hero"><i class="bi bi-inbox"></i> Ir a la bandeja</a>
          <a href="{{ route('revision.index', ['estado' => 'En revisión']) }}" class="btn btn-hero-ghost"><i class="bi bi-clipboard-check"></i> Revisar sustentos</a>
        @endif
        <a href="{{ route('notificaciones.index') }}" class="btn btn-hero-ghost"><i class="bi bi-bell"></i> Notificaciones @if($noLeidas)({{ $noLeidas }})@endif</a>
      </div>
    </div>
    @if($campana && $vigencia)
      <div class="hero-campana">
        <div class="lbl">Campaña vigente</div>
        <div class="nom">«{{ $campana->nombre }}»</div>
        <div class="hero-progress"><span style="width:{{ $vigencia['pct'] }}%"></span></div>
        <div class="meta"><span>{{ $campana->fecha_inicio->format('d/m/Y') }}</span><span>{{ $campana->fecha_fin->format('d/m/Y') }}</span></div>
        <div class="meta mt-1"><span><strong>{{ $vigencia['pct'] }}%</strong> transcurrido</span><span><strong>{{ $vigencia['restantes'] }}</strong> días restantes</span></div>
      </div>
    @endif
  </div>
</section>

{{-- Tareas del día --}}
<div class="card">
  <div class="card-head">
    <div class="card-title"><i class="bi bi-list-check"></i> Mis pendientes</div>
    <a href="{{ route('dashboard') }}" class="btn btn-ghost btn-sm">Ir a mi panel <i class="bi bi-chevron-right"></i></a>
  </div>
  <div class="tareas">
    @foreach($tareas as [$tit, $num, $sub, $ico, $url, $color])
      <a href="{{ $url }}" class="tarea {{ $color }}">
        <div class="tarea-top"><span class="tarea-num">{{ $num }}</span><i class="bi {{ $ico }}"></i></div>
        <span class="tarea-tit">{{ $tit }}</span>
        <span class="tarea-sub">{{ $sub }}</span>
      </a>
    @endforeach
  </div>
</div>

<div class="portal-grid">
  {{-- Columna principal --}}
  <div>
    <div class="card">
      <div class="card-title"><i class="bi bi-grid-3x3-gap"></i> Accesos rápidos</div>
      <div class="tile-grid">
        @foreach($accesos['disponibles'] as [$tit, $desc, $ico, $url])
          <a href="{{ $url }}" class="tile">
            <span class="tile-ico"><i class="bi {{ $ico }}"></i></span>
            <b>{{ $tit }}</b><small>{{ $desc }}</small>
          </a>
        @endforeach
      </div>
    </div>

    <div class="card">
      <div class="card-head">
        <div class="card-title"><i class="bi bi-rocket-takeoff"></i> Próximamente en RCT</div>
        <span class="cell-sub">Módulos de la hoja de ruta del sistema</span>
      </div>
      <div class="tile-grid">
        @foreach($accesos['futuros'] as [$tit, $desc, $ico, $fase])
          <button type="button" class="tile soon" data-futuro="{{ json_encode(['titulo' => $tit, 'desc' => $desc, 'fase' => $fase, 'icono' => $ico]) }}">
            <span class="soon-tag">{{ $fase }}</span>
            <span class="tile-ico"><i class="bi {{ $ico }}"></i></span>
            <b>{{ $tit }}</b><small>{{ $desc }}</small>
          </button>
        @endforeach
      </div>
    </div>

    <div class="card">
      <div class="card-title"><i class="bi bi-megaphone"></i> Comunicados</div>
      @foreach($comunicados as [$tag, $tit, $txt, $f, $ico])
        <div class="news-item">
          <span class="news-ico"><i class="bi {{ $ico }}"></i></span>
          <div>
            <div class="news-tag">{{ $tag }}</div>
            <div class="news-tit">{{ $tit }}</div>
            <div class="news-txt">{{ $txt }}</div>
            <div class="news-fecha">Publicado el {{ $f }} · Departamento de Recuperaciones</div>
          </div>
        </div>
      @endforeach
    </div>
  </div>

  {{-- Columna lateral --}}
  <div>
    <div class="card">
      <div class="card-title"><i class="bi bi-speedometer2"></i> {{ $user->esGrupoFud() ? 'Mi desempeño' : 'Campaña en cifras' }}</div>
      <div class="metric-list">
        @foreach($indicadores as [$lbl, $val, $sub])
          <div class="d-flex justify-content-between align-items-baseline">
            <div><div class="fs-13">{{ $lbl }}</div><div class="cell-sub">{{ $sub }}</div></div>
            <div class="fw-600 num dato-20">{{ $val }}</div>
          </div>
        @endforeach
      </div>
    </div>

    @if($ranking)
      <div class="card">
        <div class="card-head">
          <div class="card-title"><i class="bi bi-trophy"></i> Ranking del mes · Zona {{ $ranking['zona'] }}</div>
          <span class="badge badge-rojo">Puesto {{ $ranking['posicion'] }} de {{ $ranking['total'] }}</span>
        </div>
        @foreach($ranking['tabla'] as $i => $r)
          <div class="ranking-row {{ $r['id'] === $user->id ? 'yo' : '' }}">
            <span class="ranking-pos">{{ $i + 1 }}</span><span class="flex-grow-1">{{ $r['nombre'] }}</span>
            <span class="num">{{ $r['aprobadas'] }} aprobadas</span>
          </div>
        @endforeach
      </div>
    @endif

    <div class="card">
      <div class="card-title"><i class="bi bi-calendar-event"></i> Fechas clave</div>
      @foreach($fechas as $f)
        <div class="agenda-item">
          <div class="agenda-fecha"><span class="mes">{{ $f['fecha']->locale('es')->translatedFormat('M') }}</span><span class="dia">{{ $f['fecha']->format('d') }}</span></div>
          <div>
            <div class="agenda-tit">{{ $f['titulo'] }}</div>
            <div class="agenda-txt">{{ $f['detalle'] }}</div>
            <div class="cell-sub mt-1">{{ $f['dias'] === 0 ? 'Hoy' : ($f['dias'] === 1 ? 'Mañana' : "En {$f['dias']} días") }}</div>
          </div>
        </div>
      @endforeach
    </div>

    <div class="card">
      <div class="card-head">
        <div class="card-title"><i class="bi bi-activity"></i> Actividad reciente</div>
        <a href="{{ route('notificaciones.index') }}" class="btn btn-ghost btn-sm">Ver todo</a>
      </div>
      @forelse($actividad as $n)
        <a href="{{ $n['url'] }}" class="notif-item notif-item-plano" data-clave="{{ $n['clave'] }}">
          <span class="notif-ico {{ $n['color'] }}"><i class="bi {{ $n['icono'] }}"></i></span>
          <span class="notif-body"><span class="notif-title">{{ $n['titulo'] }}</span>
            <span class="notif-msg">{{ \Illuminate\Support\Str::limit($n['mensaje'], 90) }}</span>
            <span class="notif-time">{{ $n['fecha']->diffForHumans() }}</span></span>
        </a>
      @empty
        <div class="empty-state" style="padding:16px"><span>Sin actividad en los últimos días.</span></div>
      @endforelse
    </div>

    <div class="card">
      <div class="card-title"><i class="bi bi-people"></i> Mi equipo de contacto</div>
      @foreach($equipo as $p)
        <div class="persona">
          <span class="persona-av">{{ $inicial($p->name) }}</span>
          <div><b>{{ $p->name }}</b><small>{{ $p->rol->nombre }}@if($p->tienda) · {{ $p->tienda->nombre }}@endif</small></div>
          <div class="acciones">
            <a href="mailto:{{ $p->email }}" class="btn btn-ghost btn-sm btn-icon" title="Enviar correo"><i class="bi bi-envelope"></i></a>
          </div>
        </div>
      @endforeach
    </div>

    <div class="card">
      <div class="card-title"><i class="bi bi-bookmarks"></i> Biblioteca y normativa</div>
      <div class="metric-list fs-13">
        <a href="{{ route('lineamientos') }}" class="d-flex gap-2 align-items-center"><i class="bi bi-journal-text"></i> Lineamientos de la campaña y calculadora</a>
        @foreach(['Documento oficial de la campaña (PDF)', 'Manual de Gestión de Recuperaciones', 'Plantillas oficiales: Acuerdo de Pago e Informe-Propuesta', 'Reglamentos de programas de gobierno'] as $doc)
          <button type="button" class="d-flex gap-2 align-items-center text-gris" style="border:0;background:none;padding:0;text-align:left;font:inherit;cursor:pointer"
                  data-futuro="{{ json_encode(['titulo' => $doc, 'desc' => 'Repositorio documental con búsqueda y versiones vigentes, consultable también por el asistente.', 'fase' => 'Fase 5', 'icono' => 'bi-bookmarks']) }}">
            <i class="bi bi-file-earmark-lock"></i> {{ $doc }} <span class="badge badge-info" style="margin-left:auto">Próximamente</span>
          </button>
        @endforeach
      </div>
    </div>
  </div>
</div>

{{-- Hoja de ruta --}}
<div class="card mt-4">
  <div class="card-head">
    <div class="card-title"><i class="bi bi-signpost-2"></i> Hoja de ruta de RCT</div>
    <span class="cell-sub">Evolución planificada del sistema de recuperaciones</span>
  </div>
  <div class="roadmap">
    @foreach($hoja as $i => [$tit, $txt, $estado])
      <div class="roadmap-step {{ \Illuminate\Support\Str::slug($estado) }}">
        <div class="roadmap-fase">Fase {{ $i + 1 }}</div>
        <div class="roadmap-tit">{{ $tit }}</div>
        <div class="roadmap-txt">{{ $txt }}</div>
        <span class="badge {{ ['Disponible' => 'badge-ok', 'En desarrollo' => 'badge-info', 'Piloto' => 'badge-info'][$estado] ?? 'badge-gray' }} mt-2">{{ $estado }}</span>
      </div>
    @endforeach
  </div>
</div>

{{-- Detalle de módulos futuros --}}
<div class="rct-modal" id="modal-futuro" role="dialog" aria-labelledby="futuro-titulo">
  <div class="rct-modal-box" style="width:min(460px,100%)">
    <div class="rct-modal-head">
      <h3 class="rct-modal-title d-flex align-items-center gap-2" id="futuro-titulo"></h3>
      <button type="button" class="rct-modal-close" data-modal-close aria-label="Cerrar"><i class="bi bi-x-lg"></i></button>
    </div>
    <div class="rct-modal-body">
      <p class="fs-13" id="futuro-desc" style="margin:0 0 12px"></p>
      <div class="notice m-0"><i class="bi bi-info-circle"></i>
        <span>Este módulo forma parte de la hoja de ruta de RCT (<strong id="futuro-fase"></strong>) y estará disponible en una próxima versión del sistema.</span></div>
    </div>
    <div class="rct-modal-foot actions" style="margin-top:0"><button type="button" class="btn btn-primary" data-modal-close>Entendido</button></div>
  </div>
</div>
@endsection

@push('scripts')
<script>
document.querySelectorAll('[data-futuro]').forEach(function (el) {
  el.addEventListener('click', function () {
    var f = JSON.parse(el.getAttribute('data-futuro'));
    document.getElementById('futuro-titulo').innerHTML = '<i class="bi ' + f.icono + ' text-rojo"></i> ' + rctEsc(f.titulo);
    document.getElementById('futuro-desc').textContent = f.desc;
    document.getElementById('futuro-fase').textContent = f.fase;
    rctModal.abrir('modal-futuro');
  });
});
</script>
@endpush
