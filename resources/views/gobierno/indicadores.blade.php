@extends('layouts.app')
@section('title', 'Indicadores de honramiento')

@use('App\Support\Estilo')

@section('content')
@include('gobierno._modulo')
@php
  $pct = fn($a, $b) => $b ? round($a / $b * 100, 1) : 0;
  $barra = fn($p) => $p >= 70 ? 'ok' : ($p >= 40 ? 'warn' : 'danger');
@endphp

<div class="page-head">
  <div>
    <div class="page-eyebrow"><a href="{{ route('gobierno.index') }}">Expedientes de honramiento</a> / Indicadores</div>
    <h2>Indicadores de expedientes de honramiento</h2>
    <p>Avance documentario y de revisión de los créditos honrados{{ auth()->user()->nivel() === 1 ? ' de su zona' : '' }}.</p>
  </div>
  <div class="page-head-actions">
    <button type="button" class="btn btn-secondary" onclick="window.print()"><i class="bi bi-printer"></i> Imprimir</button>
  </div>
</div>

<form method="GET" class="card" style="padding:16px 20px">
  <div class="grid4" style="align-items:end">
    <div class="field"><label for="lote_id">Lote</label>
      <select id="lote_id" name="lote_id" onchange="this.form.submit()"><option value="">Todos</option>
        @foreach($lotes as $l)<option value="{{ $l->id }}" @selected(request('lote_id') == $l->id)>{{ $l->codigo }}{{ $l->programa ? ' · '.$l->programa : '' }}</option>@endforeach</select></div>
    <div class="field"><label for="zona_id">Zona</label>
      <select id="zona_id" name="zona_id" onchange="this.form.submit()"><option value="">Todas</option>
        @foreach($zonas as $z)<option value="{{ $z->id }}" @selected(request('zona_id') == $z->id)>{{ $z->nombre }}</option>@endforeach</select></div>
    <div class="field"><label for="gestion_id">Gestión</label>
      <select id="gestion_id" name="gestion_id" onchange="this.form.submit()"><option value="">Todas</option>
        @foreach($gestiones as $g)<option value="{{ $g->id }}" @selected(request('gestion_id') == $g->id)>{{ $g->nombre }}</option>@endforeach</select></div>
    <div><a href="{{ route('gobierno.indicadores') }}" class="btn btn-ghost"><i class="bi bi-x-circle"></i> Limpiar filtros</a></div>
  </div>
</form>

<div class="section-title">Avance documentario</div>
<div class="kpi-grid">
  <div class="kpi-card"><div class="kpi-label"><i class="bi bi-folder2"></i> Expedientes</div><div class="kpi-value">{{ $kpis['total'] }}</div><div class="kpi-sub">{{ $kpis['faltantes'] }} documentos exigidos faltantes</div></div>
  <div class="kpi-card info"><div class="kpi-label"><i class="bi bi-check2-square"></i> Completos</div><div class="kpi-value">{{ $pct($kpis['completos'], $kpis['total']) }}%</div><div class="kpi-sub">{{ $kpis['completos'] }} con los documentos de COFIDE</div></div>
  <div class="kpi-card verde"><div class="kpi-label"><i class="bi bi-patch-check"></i> Conformes</div><div class="kpi-value">{{ $pct($kpis['conformes'], $kpis['total']) }}%</div><div class="kpi-sub">{{ $kpis['conformes'] }} con revisión central</div></div>
  <div class="kpi-card rojo"><div class="kpi-label"><i class="bi bi-alarm"></i> Plazo vencido</div><div class="kpi-value">{{ $kpis['vencidos'] }}</div><div class="kpi-sub">Sin completar a los 30 días</div></div>
  <div class="kpi-card"><div class="kpi-label"><i class="bi bi-slash-circle"></i> Incompleto definitivo</div><div class="kpi-value">{{ $kpis['definitivos'] }}</div><div class="kpi-sub">La tienda no cuenta con el documento</div></div>
</div>

<div class="section-title">Monto honrado</div>
<div class="kpi-grid">
  <div class="kpi-card"><div class="kpi-label"><i class="bi bi-cash-stack"></i> Total</div><div class="kpi-value">{{ Estilo::solesCorto($kpis['monto_total']) }}</div><div class="kpi-sub">{{ Estilo::soles($kpis['monto_total']) }}</div></div>
  <div class="kpi-card verde"><div class="kpi-label"><i class="bi bi-patch-check"></i> Conforme</div><div class="kpi-value">{{ Estilo::solesCorto($kpis['monto_conforme']) }}</div><div class="kpi-sub">{{ $pct($kpis['monto_conforme'], $kpis['monto_total']) }}% del monto</div></div>
  <div class="kpi-card info"><div class="kpi-label"><i class="bi bi-hourglass-split"></i> En revisión</div><div class="kpi-value">{{ Estilo::solesCorto($kpis['monto_revision']) }}</div><div class="kpi-sub">Completos sin conformidad central</div></div>
  <div class="kpi-card mostaza"><div class="kpi-label"><i class="bi bi-exclamation-triangle"></i> Pendiente de regularizar</div><div class="kpi-value">{{ Estilo::solesCorto($kpis['monto_pendiente']) }}</div><div class="kpi-sub">Pendientes, incompletos y observados</div></div>
  <div class="kpi-card rojo"><div class="kpi-label"><i class="bi bi-slash-circle"></i> Incompleto definitivo</div><div class="kpi-value">{{ Estilo::solesCorto($kpis['monto_definitivo']) }}</div><div class="kpi-sub">No regularizable</div></div>
</div>

<div class="grid2" style="margin-bottom:20px">
  <div class="card">
    <div class="card-title"><i class="bi bi-graph-up"></i> Avance de revisiones · últimas 12 semanas</div>
    <div style="position:relative;height:240px"><canvas id="chart-avance" role="img" aria-label="Conformidades por semana"></canvas></div>
  </div>
  <div class="card">
    <div class="card-title"><i class="bi bi-file-earmark-x"></i> Documentos que más faltan</div>
    <p class="card-sub">Expedientes donde el documento falta, está observado o no está disponible. Primero los exigidos por COFIDE.</p>
    @php $max = $documentos->max('faltan') ?: 1; @endphp
    <div class="metric-list">
      @foreach($documentos->take(10) as $d)
        <div>
          <div class="metric-item-head">
            <span>{{ $d['tipo']->nombre_corto }} @if($d['exigido_en'])<span class="badge badge-rojo" style="margin-left:4px">COFIDE</span>@endif</span>
            <span>{{ $d['faltan'] }} de {{ $d['aplica'] }} · {{ $pct($d['faltan'], $d['aplica']) }}%</span>
          </div>
          <div class="bar-track"><div class="bar-fill {{ $d['exigido_en'] ? 'danger' : 'warn' }}" style="width:{{ round($d['faltan'] / $max * 100) }}%"></div></div>
        </div>
      @endforeach
    </div>
  </div>
</div>

@foreach(['Por zona' => [$porZona, 'bi-geo-alt'], 'Por lote' => [$porLote, 'bi-collection']] as $titulo => [$filas, $ico])
  <div class="card card-flush">
    <div class="card-head"><div class="card-title"><i class="bi {{ $ico }}"></i> {{ $titulo }}</div></div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>{{ $titulo === 'Por zona' ? 'Zona' : 'Lote' }}</th><th class="num">Expedientes</th><th style="min-width:160px">Completos</th><th style="min-width:160px">Conformes</th><th class="num">Faltantes</th><th class="num">Monto honrado</th></tr></thead>
        <tbody>
          @foreach($filas->sortBy('nombre') as $f)
            <tr>
              <td class="cell-main">{{ $f['nombre'] }}</td>
              <td class="num">{{ $f['total'] }}</td>
              <td><div class="bar-row"><div class="bar-track"><div class="bar-fill {{ $barra($f['pct_completo']) }}" style="width:{{ $f['pct_completo'] }}%"></div></div><span class="fs-12 fw-600 num" style="min-width:52px;text-align:right">{{ $f['pct_completo'] }}%</span></div></td>
              <td><div class="bar-row"><div class="bar-track"><div class="bar-fill ok" style="width:{{ $f['pct_conforme'] }}%"></div></div><span class="fs-12 fw-600 num" style="min-width:52px;text-align:right">{{ $f['pct_conforme'] }}%</span></div></td>
              <td class="num">{{ $f['faltantes'] }}</td>
              <td class="num">{{ Estilo::soles($f['monto']) }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
@endforeach

<div class="card card-flush">
  <div class="card-head"><div class="card-title"><i class="bi bi-shop"></i> Tiendas con más documentos faltantes</div></div>
  <div class="table-wrap">
    <table data-rct-table data-page-size="10" data-search-placeholder="Buscar tienda o zona">
      <thead><tr><th>#</th><th>Tienda · zona</th><th class="num">Faltantes</th><th class="num">Expedientes</th><th style="min-width:160px">Completos</th><th class="num">Monto honrado</th></tr></thead>
      <tbody>
        @foreach($porTienda->sortByDesc('faltantes')->values() as $i => $t)
          <tr>
            <td class="num">{{ $i + 1 }}</td>
            <td class="cell-main">{{ $t['nombre'] }}</td>
            <td class="num fw-600 {{ $t['faltantes'] ? 'text-rojo' : 'text-verde' }}">{{ $t['faltantes'] }}</td>
            <td class="num">{{ $t['total'] }}</td>
            <td><div class="bar-row"><div class="bar-track"><div class="bar-fill {{ $barra($t['pct_completo']) }}" style="width:{{ $t['pct_completo'] }}%"></div></div><span class="fs-12 fw-600 num" style="min-width:52px;text-align:right">{{ $t['pct_completo'] }}%</span></div></td>
            <td class="num">{{ Estilo::soles($t['monto']) }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
  <div style="height:12px"></div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('vendor/chartjs/chart.umd.min.js') }}"></script>
<script>
(function () {
  var d = @json($avance);
  Chart.defaults.font.family = getComputedStyle(document.body).fontFamily;
  Chart.defaults.font.size = 11;
  Chart.defaults.color = '#5f6b7a';
  new Chart(document.getElementById('chart-avance'), {
    type: 'bar',
    data: {
      labels: d.map(function (s) { return 'Sem. ' + s.etiqueta; }),
      datasets: [
        { label: 'Conformidad por zona', data: d.map(function (s) { return s.zona; }), backgroundColor: '#1f4e8c', borderRadius: 3, maxBarThickness: 22 },
        { label: 'Conformidad central', data: d.map(function (s) { return s.central; }), backgroundColor: '#1f7a4d', borderRadius: 3, maxBarThickness: 22 }
      ]
    },
    options: { responsive: true, maintainAspectRatio: false, animation: false,
      plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, boxHeight: 10 } } },
      scales: { x: { grid: { display: false } }, y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: '#eef0f2' } } } }
  });
})();
</script>
@endpush
