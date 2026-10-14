@extends('layouts.app')
@section('title', 'Mi prioridad de hoy')

@use('App\Support\Estilo')
@use('App\Models\PriorizacionCartera')

@section('content')
<div class="cc">
  <div class="page-head cc-head">
    <div>
      <h2>Mi prioridad de hoy</h2>
      <p>{{ ucfirst($hoy->translatedFormat('l d \d\e F')) }} · Tienda {{ $tienda->nombre ?? '' }} · Los 20 créditos pendientes con mayor puntaje</p>
    </div>
    <div class="page-head-actions">
      <div class="cc-segmento" role="group" aria-label="Segmento">
        <a href="{{ route('prioridad.mia') }}" class="{{ $segmento ? '' : 'active' }}">Todos</a>
        @foreach(['Alta', 'Media', 'Baja'] as $s)
          <a href="{{ route('prioridad.mia', ['segmento' => $s]) }}" class="{{ $segmento === $s ? 'active' : '' }}">{{ $s }}</a>
        @endforeach
      </div>
      <button type="button" class="btn btn-secondary" data-modo-presentacion><i class="bi bi-arrows-fullscreen"></i> Modo presentación</button>
    </div>
  </div>

  <div class="pr-resumen">
    <div class="pr-dato">
      <span class="pr-dato-valor" data-contar="{{ $resumen['pendientes'] }}" data-formato="num">{{ $resumen['pendientes'] }}</span>
      <span class="pr-dato-label">créditos pendientes en su cartera</span>
    </div>
    <div class="pr-segbar" aria-label="Distribución por segmento">
      @foreach(['Alta' => 'alta', 'Media' => 'media', 'Baja' => 'baja'] as $s => $c)
        @if($resumen[$c])<div class="pr-segbar-item {{ $c }}" style="flex:{{ $resumen[$c] }}" title="Prioridad {{ strtolower($s) }}: {{ $resumen[$c] }}"><span>{{ $s }} · {{ $resumen[$c] }}</span></div>@endif
      @endforeach
    </div>
    <div class="pr-dato">
      <span class="pr-dato-valor" data-contar="{{ round($resumen['esperado']) }}" data-formato="soles">{{ Estilo::solesEntero($resumen['esperado']) }}</span>
      <span class="pr-dato-label">recupero esperado de esta lista</span>
    </div>
    <div class="pr-dato">
      <span class="pr-dato-valor verde" data-contar="{{ round($resumen['provision']) }}" data-formato="soles">{{ Estilo::solesEntero($resumen['provision']) }}</span>
      <span class="pr-dato-label">provisión que se liberaría</span>
    </div>
  </div>

  @if($top->isEmpty())
    <div class="card"><div class="empty-state"><i class="bi bi-check2-circle"></i><strong>Sin créditos pendientes</strong><span>No hay créditos pendientes en este segmento.</span></div></div>
  @else
    <div class="pr-grid">
      @foreach($top as $i => $p)
        @php
          $c = $p->cliente;
          $seg = strtolower($p->segmento);
          $circ = 2 * M_PI * 26;
        @endphp
        <article class="pr-card">
          <header class="pr-card-head">
            <div class="pr-anillo {{ $seg }}" role="img" aria-label="Puntaje {{ $p->score }} de 100">
              <svg viewBox="0 0 64 64" aria-hidden="true">
                <circle cx="32" cy="32" r="26" class="pr-anillo-fondo"/>
                <circle cx="32" cy="32" r="26" class="pr-anillo-valor" style="--total:{{ round($circ, 2) }};--valor:{{ round($circ * $p->score / 100, 2) }}"/>
              </svg>
              <span>{{ $p->score }}</span>
            </div>
            <div class="pr-card-titulo">
              <div class="pr-rank">#{{ $i + 1 }} · Prioridad {{ strtolower($p->segmento) }}</div>
              <div class="pr-cliente">{{ $c->nombre_cliente }}</div>
              <div class="pr-sub">{{ $c->credito_sicmact }} · {{ $c->dias_atraso }} días · {{ $c->calificacion_rcc === 'CPP' ? 'CPP' : ucfirst(mb_strtolower($c->calificacion_rcc)) }}</div>
            </div>
          </header>

          <div class="pr-montos">
            <div><span class="pr-lbl">Saldo capital</span><strong>{{ Estilo::solesEntero($c->saldo_capital) }}</strong></div>
            <div><span class="pr-lbl">Prob. de pago</span><strong>{{ number_format($p->probabilidad_pago, 1) }}%</strong></div>
            <div><span class="pr-lbl">Recupero esperado</span><strong>{{ Estilo::solesEntero($p->recupero_esperado) }}</strong></div>
          </div>

          <div class="pr-chips">
            @foreach($p->motivos as $m)<span class="pr-chip">{{ $m }}</span>@endforeach
          </div>

          <footer class="pr-card-pie">
            <div class="pr-accion">
              <i class="bi {{ PriorizacionCartera::iconoAccion($p->accion_sugerida) }}"></i>
              <div><strong>{{ $p->accion_sugerida }}</strong><span>Mejor horario: {{ $p->mejor_horario }}</span></div>
            </div>
            <a href="{{ route('fud.create', ['cliente' => $c->id]) }}" class="btn btn-secondary btn-sm">Gestionar <i class="bi bi-arrow-right"></i></a>
          </footer>
        </article>
      @endforeach
    </div>
  @endif
</div>
@endsection

@push('scripts')
<script src="{{ asset('assets/js/rct-analitica.js') }}?v={{ filemtime(public_path('assets/js/rct-analitica.js')) }}"></script>
<script>RCTAnalitica.contadores(); requestAnimationFrame(function () { document.body.classList.add('cc-listo'); });</script>
@endpush
