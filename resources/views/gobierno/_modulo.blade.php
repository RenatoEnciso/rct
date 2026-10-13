{{-- Navegación del módulo Programas de gobierno · Impulso MyPerú --}}
@php
  $u = auth()->user();
  $central = \App\Http\Controllers\Impulso\CoreController::esCentral($u);
  // [ruta, etiqueta, ícono, visible, rutas que la marcan como activa]
  $tabs = [
    ['gobierno.indicadores',  'Indicadores', 'bi-bar-chart-line',   $u->esAprobador(), ['gobierno.indicadores']],
    ['impulso.candidatos',    'Candidatos',  'bi-hourglass-split',  $central, ['impulso.candidatos']],
    ['impulso.lotes',         'Lotes',       'bi-collection',       $central, ['impulso.lotes*', 'impulso.honramiento*', 'gobierno.lotes']],
    ['gobierno.index',        'Expedientes', 'bi-folder2-open',     true,     ['gobierno.index', 'gobierno.show', 'gobierno.importar*']],
    ['gobierno.revision',     'Revisión',    'bi-clipboard-check',  $u->esAprobador(), ['gobierno.revision']],
    ['impulso.matriz',        'Base matriz', 'bi-table',            $central, ['impulso.matriz*']],
    ['impulso.pagos',         'Pagos',       'bi-cash-coin',        $central, ['impulso.pagos*']],
    ['impulso.devoluciones',  'Devolución',  'bi-arrow-left-right', $central, ['impulso.devoluciones*']],
  ];
  $ultima = \App\Services\Impulso\SincronizadorCore::textoUltima();
@endphp
<div class="im-nav">
  <nav class="im-tabs" aria-label="Módulo Impulso MyPerú">
    @foreach($tabs as [$ruta, $lbl, $ico, $ver, $activas])
      @if($ver && Route::has($ruta))
        <a href="{{ route($ruta) }}" class="{{ request()->routeIs(...$activas) ? 'active' : '' }}"><i class="bi {{ $ico }}"></i> {{ $lbl }}</a>
      @endif
    @endforeach
  </nav>
  <div class="im-core">
    <span class="im-core-chip" title="El core financiero se sincroniza automáticamente cada 30 minutos">
      <span class="im-core-punto {{ $ultima ? '' : 'off' }}"></span>
      Core {{ $ultima ? 'sincronizado '.$ultima : 'sin sincronizar' }}
    </span>
    @if($central)
      <form method="POST" action="{{ route('impulso.core.sincronizar') }}">@csrf
        <button class="btn btn-ghost btn-sm" title="Sincronizar ahora con el core"><i class="bi bi-arrow-repeat"></i> Sincronizar</button>
      </form>
    @endif
  </div>
</div>
