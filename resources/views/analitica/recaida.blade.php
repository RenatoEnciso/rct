@extends('layouts.app')
@section('title', 'Análisis de recaída')

@use('App\Http\Controllers\RecaidaController')

@section('content')
@php
  $zonaNombre = $zonaId ? optional($zonas->firstWhere('id', $zonaId))->nombre : null;
  $filtro = fn($id) => route('recaida.index', array_filter(['zona_id' => $id]));
  $semaforo = ['rojo' => ['Alto', 'bi-exclamation-octagon-fill'], 'ambar' => ['Medio', 'bi-exclamation-triangle-fill'], 'verde' => ['Bajo', 'bi-check-circle-fill']];
  $maxTipo = max(1, $tipos->max('recayo'));
@endphp
<div class="cc">
  <div class="page-head cc-head">
    <div>
      <h2>¿El beneficio funcionó?</h2>
      <p>Comportamiento de los clientes a 30, 60 y 90 días del beneficio pagado{{ $zonaNombre ? ' · Zona '.$zonaNombre : ' · Todas las zonas' }}.</p>
    </div>
    <div class="page-head-actions">
      @unless($zonaFija)
        <select class="input-rct cc-select" aria-label="Zona" onchange="location.href=this.value">
          <option value="{{ $filtro(null) }}">Todas las zonas</option>
          @foreach($zonas as $z)<option value="{{ $filtro($z->id) }}" @selected($zonaId == $z->id)>Zona {{ $z->nombre }}</option>@endforeach
        </select>
      @endunless
      <button type="button" class="btn btn-secondary" data-modo-presentacion><i class="bi bi-arrows-fullscreen"></i> Modo presentación</button>
    </div>
  </div>

  <div class="cc-fila rc-fila-1-2">
    {{-- KPI principal --}}
    <div class="card cc-card rc-hero">
      <div class="rc-hero-label">Recuperación sostenida a 90 días</div>
      @if($kpi['sostenida'] !== null)
        <div class="rc-hero-valor" data-contar="{{ $kpi['sostenida'] }}" data-formato="pct">{{ number_format($kpi['sostenida'], 1) }}%</div>
        <p class="rc-hero-texto">de los clientes que recibieron un beneficio <strong>siguen al día</strong> tres meses después.</p>
        <div class="cc-barra rc-hero-barra" aria-hidden="true"><div class="cc-barra-fill ok" style="--w:{{ $kpi['sostenida'] }}%"></div></div>
        <div class="rc-hero-datos">
          <div><strong>{{ $kpi['base'] }}</strong><span>beneficios con 90 días cumplidos</span></div>
          <div><strong class="text-rojo">{{ $kpi['recayeron'] }}</strong><span>recayeron</span></div>
          <div><strong class="text-ambar">{{ $kpi['leve'] }}</strong><span>con atraso leve</span></div>
          <div><strong>{{ number_format($kpi['sostenida30'] ?? 0, 1) }}%</strong><span>al día a 30 días</span></div>
        </div>
      @else
        <div class="rc-hero-valor text-gris">En curso</div>
        <p class="rc-hero-texto">Los beneficios aún no cumplen 90 días.</p>
      @endif
    </div>

    {{-- Curva por mes --}}
    <div class="card cc-card">
      <div class="card-head">
        <div>
          <div class="card-title"><i class="bi bi-graph-down"></i> Recuperación sostenida por mes del beneficio</div>
          <p class="card-sub m-0">% de clientes al día a los 30, 60 y 90 días, según el mes en que pagaron su beneficio.</p>
        </div>
        <button type="button" class="btn btn-ghost btn-sm" data-ver-tabla="tabla-curva"><i class="bi bi-table"></i> Ver tabla</button>
      </div>
      <div class="cc-chart"><canvas id="chart-curva" role="img" aria-label="Recuperación sostenida por mes del beneficio"></canvas></div>
      <div class="table-wrap" id="tabla-curva" hidden>
        <table>
          <thead><tr><th>Mes del beneficio</th><th class="num">Beneficios</th><th class="num">30 días</th><th class="num">60 días</th><th class="num">90 días</th></tr></thead>
          <tbody>
            @foreach($curva as $c)
              <tr><td>{{ $c['mes'] }}</td><td class="num">{{ $c['n'] }}</td>
                @foreach(['d30', 'd60', 'd90'] as $k)<td class="num">{{ $c[$k] !== null ? number_format($c[$k], 1).'%' : 'En curso' }}</td>@endforeach
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="cc-fila rc-fila-1-2">
    {{-- Por tipo de beneficio --}}
    <div class="card cc-card">
      <div class="card-title"><i class="bi bi-diagram-3"></i> Recaída a 90 días por tipo de beneficio</div>
      <p class="card-sub">% de clientes que volvieron a atrasarse.</p>
      <div class="rc-tipos">
        @foreach($tipos as $t)
          <div>
            <div class="cc-meta-head"><span class="fw-600">{{ $t['tipo'] }}</span><span class="cc-meta-pct">{{ number_format($t['recayo'], 1) }}%</span></div>
            <div class="cc-barra"><div class="cc-barra-fill rc-recaida" style="--w:{{ round($t['recayo'] / $maxTipo * 100, 1) }}%"></div></div>
            <div class="cc-meta-sub">{{ $t['n'] }} beneficios con 90 días</div>
          </div>
        @endforeach
      </div>
      @php
        $alDia = $tipos->firstWhere('tipo', 'Pago de cuotas que se pone al día');
        $parcial = $tipos->firstWhere('tipo', 'Pago de algunas cuotas vencidas');
      @endphp
      @if($alDia && $parcial && $alDia['recayo'] > 0 && $parcial['recayo'] > $alDia['recayo'])
        <div class="rc-lectura"><i class="bi bi-lightbulb"></i>
          <span>Los clientes que <strong>se ponen al día</strong> recaen <strong>{{ number_format($parcial['recayo'] / $alDia['recayo'], 1) }} veces menos</strong>
          que los que pagan solo algunas cuotas vencidas ({{ number_format($alDia['recayo'], 1) }}% frente a {{ number_format($parcial['recayo'], 1) }}%).</span>
        </div>
      @endif
    </div>

    {{-- Clientes en riesgo --}}
    <div class="card cc-card card-flush">
      <div class="card-head">
        <div class="card-title"><i class="bi bi-person-exclamation"></i> Clientes en riesgo de recaer</div>
        <span class="cell-sub">Beneficios de los últimos 90 días con mayor riesgo</span>
      </div>
      <div class="table-wrap">
        <table>
          <thead><tr><th>Riesgo</th><th>Cliente</th><th>Beneficio</th><th>Asesor</th><th class="num">Días desde el pago</th><th class="num">Prob. de recaer</th></tr></thead>
          <tbody>
            @forelse($riesgo as $s)
              @php [$nivel, $ico] = $semaforo[$s->semaforo] ?? $semaforo['verde']; @endphp
              <tr>
                <td><span class="rc-semaforo {{ $s->semaforo }}"><i class="bi {{ $ico }}"></i> {{ $nivel }}</span></td>
                <td><div class="cell-main">{{ $s->cliente->nombre_cliente }}</div><div class="cell-sub">{{ $s->cliente->credito_sicmact }} · {{ $s->cliente->tienda->nombre ?? '' }}</div></td>
                <td><div>{{ RecaidaController::tipo($s) }}</div><div class="cell-sub">Pagado el {{ $s->fecha_beneficio->format('d/m/Y') }}</div></td>
                <td>{{ $s->asesor->name ?? '—' }}</td>
                <td class="num">{{ (int) $s->fecha_beneficio->diffInDays($hoy) }}</td>
                <td class="num fw-600">{{ $s->riesgo_recaida }}%</td>
              </tr>
            @empty
              <tr><td colspan="6"><div class="empty-state"><span>No hay beneficios en seguimiento.</span></div></td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('vendor/chartjs/chart.umd.min.js') }}"></script>
<script src="{{ asset('assets/js/rct-analitica.js') }}?v={{ filemtime(public_path('assets/js/rct-analitica.js')) }}"></script>
<script>RCTAnalitica.recaida({ curva: {{ Js::from($curva) }} });</script>
@endpush
