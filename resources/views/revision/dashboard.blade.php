@extends('layouts.app')
@section('title', 'Dashboard de Sustentos')

@section('content')

<div class="page-head">
  <div class="page-head-text">
    <h2>📊 Dashboard de Sustentos</h2>
    <p>Resumen general de documentación y cumplimiento.</p>
  </div>
</div>

{{-- KPIs --}}
<div style="display:grid;grid-template-columns:repeat(6,1fr);gap:10px;margin-bottom:20px">

  @php
    $kpis = [
      ['label'=>'Total',        'val'=>$total,      'sub'=>'registros activos',      'color'=>'#185fa5'],
      ['label'=>'Aprobados',    'val'=>$aprobados,  'sub'=>'documentación completa', 'color'=>'#1a7a4a'],
      ['label'=>'En revisión',  'val'=>$enRevision, 'sub'=>'esperando revisor',      'color'=>'#b07d00'],
      ['label'=>'Observados',   'val'=>$observados, 'sub'=>'requieren corrección',   'color'=>'#c0392b'],
      ['label'=>'Pendientes',   'val'=>$pendientes, 'sub'=>'sin documentos aún',     'color'=>'#6b7280'],
      ['label'=>'Cumplimiento', 'val'=>$pct.'%',    'sub'=>'aprobados / total',      'color'=>'#533ab7'],
    ];
  @endphp

  @foreach($kpis as $k)
    <div style="background:#fff;border:1px solid #e5e7eb;border-radius:10px;
                padding:14px;border-bottom:3px solid {{ $k['color'] }}">
      <div style="font-size:10px;color:#6b7280;font-weight:600;text-transform:uppercase;
                  letter-spacing:.5px;margin-bottom:6px">{{ $k['label'] }}</div>
      <div style="font-size:24px;font-weight:500;color:{{ $k['color'] }};line-height:1">
        {{ $k['val'] }}
      </div>
      <div style="font-size:10px;color:#9ca3af;margin-top:5px">{{ $k['sub'] }}</div>
    </div>
  @endforeach

</div>

{{-- Fila 1: Tendencia + Distribución --}}
<div style="display:grid;grid-template-columns:1.6fr 1fr;gap:14px;margin-bottom:14px">

  {{-- Tendencia mensual --}}
  <div class="card">
    <div class="card-title">Tendencia mensual — últimos 6 meses</div>
    <div style="position:relative;height:200px">
      <canvas id="chartTendencia" role="img"
              aria-label="Aprobados y observados por mes">Tendencia mensual de sustentos.</canvas>
    </div>
    <div style="display:flex;gap:20px;margin-top:10px;font-size:11px;color:#6b7280">
      <span style="display:flex;align-items:center;gap:5px">
        <span style="width:10px;height:10px;border-radius:2px;background:#1a7a4a;display:inline-block"></span>
        Aprobados
      </span>
      <span style="display:flex;align-items:center;gap:5px">
        <span style="width:10px;height:10px;border-radius:2px;background:#c0392b;display:inline-block"></span>
        Observados
      </span>
    </div>
  </div>

  {{-- Distribución donut --}}
  <div class="card">
    <div class="card-title">Distribución por estado</div>
    <div style="position:relative;height:180px">
      <canvas id="chartDonut" role="img"
              aria-label="Distribución de sustentos por estado">Distribución por estado.</canvas>
    </div>
    <div style="display:flex;flex-wrap:wrap;gap:8px;margin-top:10px">
      @foreach($distribucion['labels'] as $i => $lbl)
        <span style="display:flex;align-items:center;gap:4px;font-size:10px;color:#6b7280">
          <span style="width:8px;height:8px;border-radius:2px;
                       background:{{ $distribucion['colors'][$i] }};display:inline-block"></span>
          {{ $lbl }}: <strong>{{ $distribucion['data'][$i] }}</strong>
        </span>
      @endforeach
    </div>
  </div>

</div>

{{-- Fila 2: Docs faltantes + Top analistas --}}
<div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">

  {{-- Documentos pendientes por tipo --}}
  <div class="card">
    <div class="card-title">Documentos pendientes de revisión por tipo</div>
    @php $maxDoc = $docsFaltantes->max('total') ?: 1; @endphp
    @forelse($docsFaltantes as $df)
      <div style="margin-bottom:10px">
        <div style="display:flex;justify-content:space-between;font-size:12px;margin-bottom:4px">
          <span style="color:#374151">{{ $df->tipo_doc }}</span>
          <strong style="color:#c0392b">{{ $df->total }}</strong>
        </div>
        <div style="background:#f3f4f6;border-radius:4px;height:8px;overflow:hidden">
          <div style="height:100%;border-radius:4px;background:#c0392b;
                      width:{{ round($df->total / $maxDoc * 100) }}%"></div>
        </div>
      </div>
    @empty
      <div style="text-align:center;padding:24px;color:#9ca3af;font-size:12px">
        Sin documentos pendientes.
      </div>
    @endforelse
  </div>

  {{-- Top analistas --}}
  <div class="card">
    <div class="card-title">Top analistas por volumen de carga</div>
    <table style="width:100%;border-collapse:collapse;font-size:12px">
      <thead>
        <tr>
          <th style="color:#6b7280;font-weight:500;padding:5px 8px;text-align:left;
                     border-bottom:1px solid #e5e7eb">#</th>
          <th style="color:#6b7280;font-weight:500;padding:5px 8px;text-align:left;
                     border-bottom:1px solid #e5e7eb">Analista</th>
          <th style="color:#6b7280;font-weight:500;padding:5px 8px;text-align:center;
                     border-bottom:1px solid #e5e7eb">Total</th>
          <th style="color:#6b7280;font-weight:500;padding:5px 8px;text-align:center;
                     border-bottom:1px solid #e5e7eb">Aprobados</th>
          <th style="color:#6b7280;font-weight:500;padding:5px 8px;text-align:center;
                     border-bottom:1px solid #e5e7eb">% ok</th>
        </tr>
      </thead>
      <tbody>
        @forelse($topAnalistas as $i => $a)
          <tr>
            <td style="padding:7px 8px;border-bottom:1px solid #f3f4f6;color:#9ca3af">
              {{ $i + 1 }}
            </td>
            <td style="padding:7px 8px;border-bottom:1px solid #f3f4f6;font-weight:500">
              {{ $a->cargador->name ?? '—' }}
            </td>
            <td style="padding:7px 8px;border-bottom:1px solid #f3f4f6;text-align:center">
              {{ $a->total }}
            </td>
            <td style="padding:7px 8px;border-bottom:1px solid #f3f4f6;text-align:center;
                       color:#1a7a4a;font-weight:500">
              {{ $a->aprobados }}
            </td>
            <td style="padding:7px 8px;border-bottom:1px solid #f3f4f6;text-align:center">
              @php $pctA = $a->total > 0 ? round($a->aprobados / $a->total * 100) : 0; @endphp
              <span style="background:{{ $pctA >= 60 ? '#ecfdf5' : ($pctA >= 40 ? '#fffbeb' : '#fff5f5') }};
                           color:{{ $pctA >= 60 ? '#065f46' : ($pctA >= 40 ? '#92400e' : '#7f1d1d') }};
                           font-size:11px;padding:2px 8px;border-radius:20px;font-weight:500">
                {{ $pctA }}%
              </span>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="5" style="text-align:center;padding:20px;color:#9ca3af">
              Sin datos.
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

</div>

@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.js"></script>
<script>
(function() {
  var tendenciaRaw = @json($tendencia);
  var meses        = Object.keys(tendenciaRaw);

  var aprobadosPorMes  = meses.map(function(m) {
    var g = tendenciaRaw[m] || [];
    var f = g.find(function(r){ return r.estado === 'Aprobado'; });
    return f ? f.total : 0;
  });
  var observadosPorMes = meses.map(function(m) {
    var g = tendenciaRaw[m] || [];
    var f = g.find(function(r){ return r.estado === 'Observado'; });
    return f ? f.total : 0;
  });

  new Chart(document.getElementById('chartTendencia'), {
    type: 'line',
    data: {
      labels: meses,
      datasets: [
        {
          label: 'Aprobados',
          data: aprobadosPorMes,
          borderColor: '#1a7a4a', backgroundColor: 'rgba(26,122,74,0.08)',
          borderWidth: 2, tension: 0.3,
          pointRadius: 3, pointBackgroundColor: '#1a7a4a', fill: true
        },
        {
          label: 'Observados',
          data: observadosPorMes,
          borderColor: '#c0392b', backgroundColor: 'rgba(192,57,43,0.06)',
          borderWidth: 2, tension: 0.3,
          pointRadius: 3, pointBackgroundColor: '#c0392b', fill: true
        }
      ]
    },
    options: {
      responsive: true, maintainAspectRatio: false,
      plugins: { legend: { display: false } },
      scales: {
        x: { grid: { display: false }, ticks: { font: { size: 10 }, color: '#898781' } },
        y: { grid: { color: 'rgba(0,0,0,0.05)' }, ticks: { font: { size: 10 }, color: '#898781' }, beginAtZero: true }
      }
    }
  });

  var dist = @json($distribucion);
  new Chart(document.getElementById('chartDonut'), {
    type: 'doughnut',
    data: {
      labels: dist.labels,
      datasets: [{
        data: dist.data,
        backgroundColor: dist.colors,
        borderWidth: 2,
        borderColor: '#fff'
      }]
    },
    options: {
      responsive: true, maintainAspectRatio: false,
      cutout: '65%',
      plugins: { legend: { display: false } }
    }
  });
})();
</script>
@endpush