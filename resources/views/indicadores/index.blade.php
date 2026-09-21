@extends('layouts.app')
@section('title', 'Indicadores')

@section('content')

<div class="page-head">
  <div class="page-head-text">
    <h2>📊 Indicadores de Campaña</h2>
    <p>Campaña "Sin Atrasos que Atrasan"
       · {{ $campana?->fecha_inicio->format('d M Y') }}
       – {{ $campana?->fecha_fin->format('d M Y') }}</p>
  </div>
  <span class="badge badge-ok">🟢 Campaña activa</span>
</div>

{{-- KPIs --}}
<div class="grid4" style="margin-bottom:20px">
  <div class="kpi-card">
    <div class="kpi-label">FUDs enviados</div>
    <div class="kpi-value">{{ $total }}</div>
    <div class="kpi-sub">Total acumulado</div>
  </div>
  <div class="kpi-card mostaza">
    <div class="kpi-label">En revisión</div>
    <div class="kpi-value">{{ $revision }}</div>
    <div class="kpi-sub">Esperando aprobación</div>
  </div>
  <div class="kpi-card verde">
    <div class="kpi-label">Aprobadas</div>
    <div class="kpi-value">{{ $aprobadas }}</div>
    <div class="kpi-sub">Conciliaciones OK</div>
  </div>
  <div class="kpi-card info">
    <div class="kpi-label">Tasa de aprobación</div>
    <div class="kpi-value">{{ $tasa }}%</div>
    <div class="kpi-sub">% del total enviado</div>
  </div>
</div>

<div class="grid2">
  {{-- Por tipo --}}
  <div class="card">
    <div class="card-title">Distribución por tipo de negociación</div>
    @forelse($porTipo as $item)
      @php $max = $porTipo->max('total'); @endphp
      <div style="display:flex;justify-content:space-between;align-items:center;padding:10px 0;border-bottom:1px solid #f0f0f0;font-size:13px">
        <span style="max-width:55%">{{ $item->tipo_negociacion_desc ?? 'Sin dato' }}</span>
        <div style="flex:1;margin:0 12px">
          <div class="progress-bar">
            <div class="progress-fill" style="width:{{ $max ? round($item->total/$max*100) : 0 }}%"></div>
          </div>
        </div>
        <strong style="color:var(--rojo)">{{ $item->total }}</strong>
      </div>
    @empty
      <p style="color:#999;font-size:13px;text-align:center;padding:20px">Sin datos aún.</p>
    @endforelse
  </div>

  {{-- Parámetros clave --}}
  <div class="card">
    <div class="card-title">📌 Parámetros clave de campaña</div>
    @foreach([
      'Vigencia campaña'                         => '01 Sep 2026 – 28 Feb 2027',
      'Límite conciliaciones por cliente'        => 'Máx. 4 en 12 meses',
      'Pago fraccionado (cancelación total)'     => 'Máx. 90 días desde 1ra amortización',
      'Concil. intereses máx. (Dudoso/Pérdida ≥91d)' => '70% intereses + 100% moratorio',
      'Cartera Judicial/Extrajudicial máx.'      => '70% SK + 100% intereses + moratorio',
      'Cartera FOCMAC máx.'                      => '90% SK + 100% intereses + moratorio',
      'Pago cuotas – máx. monto conciliar'       => '50% del valor cuota pactada',
      'Reportes de conciliaciones'               => 'Primeros 2 días hábiles del mes',
    ] as $label => $val)
      <div style="display:flex;justify-content:space-between;align-items:center;padding:10px 0;border-bottom:1px solid #f0f0f0;font-size:13px">
        <span>{{ $label }}</span>
        <strong>{{ $val }}</strong>
      </div>
    @endforeach
  </div>
</div>

@endsection