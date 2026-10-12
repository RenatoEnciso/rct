@extends('layouts.app')
@section('title', 'Reporte de conciliaciones')

@section('content')
<div class="page-head">
  <div>
    <h2>Reporte de conciliaciones</h2>
    <p>Situación de la documentación de sustento de las conciliaciones aprobadas.</p>
  </div>
  <div class="page-head-actions">
    <a href="{{ route('revision.index') }}" class="btn btn-secondary"><i class="bi bi-clipboard-check"></i> Ir a revisión</a>
    <button type="button" class="btn btn-secondary" onclick="window.print()"><i class="bi bi-printer"></i> Imprimir</button>
  </div>
</div>

<div class="kpi-grid">
  <div class="kpi-card"><div class="kpi-label"><i class="bi bi-files"></i> Total</div><div class="kpi-value">{{ $total }}</div><div class="kpi-sub">Sustentos registrados</div></div>
  <div class="kpi-card verde"><div class="kpi-label"><i class="bi bi-check2-circle"></i> Conformes</div><div class="kpi-value">{{ $aprobados }}</div><div class="kpi-sub">{{ $pct }}% del total</div></div>
  <div class="kpi-card info"><div class="kpi-label"><i class="bi bi-hourglass-split"></i> En revisión</div><div class="kpi-value">{{ $enRevision }}</div><div class="kpi-sub">Esperando revisor</div></div>
  <div class="kpi-card mostaza"><div class="kpi-label"><i class="bi bi-arrow-return-left"></i> Observados</div><div class="kpi-value">{{ $observados }}</div><div class="kpi-sub">Requieren corrección</div></div>
  <div class="kpi-card"><div class="kpi-label"><i class="bi bi-clock"></i> Pendientes</div><div class="kpi-value">{{ $pendientes }}</div><div class="kpi-sub">Sin documentos aún</div></div>
  <div class="kpi-card rojo"><div class="kpi-label"><i class="bi bi-x-circle"></i> Rechazados</div><div class="kpi-value">{{ $rechazados }}</div><div class="kpi-sub">Decisión definitiva</div></div>
</div>

<div class="grid2" style="grid-template-columns:1.6fr 1fr;margin-bottom:20px">
  <div class="card">
    <div class="card-title"><i class="bi bi-graph-up"></i> Evolución mensual · últimos 6 meses</div>
    <div style="position:relative;height:240px">
      <canvas id="chartTendencia" role="img" aria-label="Sustentos conformes y observados por mes"></canvas>
    </div>
  </div>
  <div class="card">
    <div class="card-title"><i class="bi bi-pie-chart"></i> Distribución por estado</div>
    <div style="position:relative;height:190px">
      <canvas id="chartDonut" role="img" aria-label="Distribución de sustentos por estado"></canvas>
    </div>
    <div class="d-flex flex-wrap gap-2" style="margin-top:12px">
      @foreach($distribucion['labels'] as $i => $lbl)
        <span class="d-inline-flex align-items-center gap-1 fs-12 text-gris">
          <span style="width:9px;height:9px;border-radius:2px;background:{{ $distribucion['colors'][$i] }};display:inline-block"></span>
          {{ $lbl === 'Aprobado' ? 'Conforme' : $lbl }}: <strong class="text-dark">{{ $distribucion['data'][$i] }}</strong>
        </span>
      @endforeach
    </div>
  </div>
</div>

<div class="grid2">
  <div class="card">
    <div class="card-title"><i class="bi bi-file-earmark-text"></i> Documentos pendientes de revisión por tipo</div>
    @php $maxDoc = $docsFaltantes->max('total') ?: 1; @endphp
    <div class="metric-list">
      @forelse($docsFaltantes as $df)
        <div>
          <div class="metric-item-head"><span>{{ $df->tipo_doc === 'Informe-Propuesta SK>3UIT' ? 'Informe-Propuesta (SK > 3 UIT)' : $df->tipo_doc }}</span><span>{{ $df->total }}</span></div>
          <div class="bar-track"><div class="bar-fill info" style="width:{{ round($df->total / $maxDoc * 100) }}%"></div></div>
        </div>
      @empty
        <div class="empty-state"><i class="bi bi-check2-all"></i><span>No hay documentos pendientes de revisión.</span></div>
      @endforelse
    </div>
  </div>

  <div class="card card-flush">
    <div class="card-head"><div class="card-title"><i class="bi bi-people"></i> Ejecutivos con mayor volumen de carga</div></div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Ejecutivo</th><th class="num">Sustentos</th><th class="num">Conformes</th><th class="num">% conformidad</th></tr></thead>
        <tbody>
          @forelse($topAnalistas as $a)
            @php $pctA = $a->total > 0 ? round($a->aprobados / $a->total * 100) : 0; @endphp
            <tr>
              <td class="cell-main">{{ $a->cargador->name ?? '—' }}</td>
              <td class="num">{{ $a->total }}</td>
              <td class="num">{{ $a->aprobados }}</td>
              <td class="num"><span class="badge {{ $pctA >= 60 ? 'badge-ok' : ($pctA >= 40 ? 'badge-pending' : 'badge-danger') }}">{{ $pctA }}%</span></td>
            </tr>
          @empty
            <tr><td colspan="4" class="empty-state"><span>Sin datos.</span></td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.js"></script>
<script>
(function () {
  Chart.defaults.font.family = getComputedStyle(document.body).fontFamily;
  Chart.defaults.font.size = 11;
  Chart.defaults.color = '#5f6b7a';

  var datos = @json($tendencia);
  var meses = Object.keys(datos);
  var serie = function (estado) {
    return meses.map(function (m) { var f = (datos[m] || []).find(function (r) { return r.estado === estado; }); return f ? f.total : 0; });
  };

  new Chart(document.getElementById('chartTendencia'), {
    type: 'bar',
    data: {
      labels: meses,
      datasets: [
        { label: 'Conformes',  data: serie('Aprobado'),  backgroundColor: '#1f7a4d', borderRadius: 3, maxBarThickness: 28 },
        { label: 'Observados', data: serie('Observado'), backgroundColor: '#d08a1e', borderRadius: 3, maxBarThickness: 28 }
      ]
    },
    options: {
      responsive: true, maintainAspectRatio: false,
      plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, boxHeight: 10 } } },
      scales: {
        x: { grid: { display: false } },
        y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: '#eef0f2' } }
      }
    }
  });

  var dist = @json($distribucion);
  new Chart(document.getElementById('chartDonut'), {
    type: 'doughnut',
    data: { labels: dist.labels.map(function (l) { return l === 'Aprobado' ? 'Conforme' : l; }),
            datasets: [{ data: dist.data, backgroundColor: dist.colors, borderWidth: 2, borderColor: '#fff' }] },
    options: { responsive: true, maintainAspectRatio: false, cutout: '68%', plugins: { legend: { display: false } } }
  });
})();
</script>
@endpush
