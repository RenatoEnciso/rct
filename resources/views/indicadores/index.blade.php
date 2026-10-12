@extends('layouts.app')
@section('title', 'Indicadores')

@section('content')

<div class="page-head">
  <div class="page-head-text">
    <h2>📊 Indicadores de Campaña</h2>
    @if($campana)
      <p>Campaña "{{ $campana->nombre }}"
         · {{ $campana->fecha_inicio->format('d/m/Y') }} – {{ $campana->fecha_fin->format('d/m/Y') }}</p>
    @else
      <p>No hay una campaña activa configurada.</p>
    @endif
  </div>
  @if($campana)
    @php $vigente = now()->between($campana->fecha_inicio, $campana->fecha_fin->copy()->endOfDay()); @endphp
    <span class="badge {{ $vigente ? 'badge-ok' : 'badge-gray' }}">
      {{ $vigente ? 'Campaña vigente · quedan '.(int) now()->diffInDays($campana->fecha_fin).' días' : 'Campaña fuera de vigencia' }}
    </span>
  @endif
</div>

{{-- KPIs --}}
<div class="grid4" style="margin-bottom:20px">
  <div class="kpi-card">
    <div class="kpi-label">Expedientes FUD</div>
    <div class="kpi-value">{{ $total }}</div>
    <div class="kpi-sub">Vigentes (última versión)</div>
  </div>
  <div class="kpi-card mostaza">
    <div class="kpi-label">En revisión</div>
    <div class="kpi-value">{{ $revision }}</div>
    <div class="kpi-sub">Esperando aprobación</div>
  </div>
  <div class="kpi-card verde">
    <div class="kpi-label">Aprobadas</div>
    <div class="kpi-value">{{ $aprobadas }}</div>
    <div class="kpi-sub">Tasa de aprobación {{ $tasa }}%</div>
  </div>
  <div class="kpi-card info">
    <div class="kpi-label">Programas de gobierno</div>
    <div class="kpi-value">{{ $gobCompletos }}/{{ $gobTotal }}</div>
    <div class="kpi-sub">Expedientes completos</div>
  </div>
</div>

<div class="grid2" style="margin-bottom:20px">
  {{-- Evolución mensual --}}
  <div class="card">
    <div class="card-title">Evolución mensual · enviados vs aprobados</div>
    @php $maxEv = $evolucion->max('enviados') ?: 1; @endphp
    @forelse($evolucion as $m)
      @php $fecha = \Carbon\Carbon::createFromFormat('Y-m', $m->mes)->locale('es'); @endphp
      <div style="padding:8px 0;border-bottom:1px solid #f0f0f0;font-size:13px">
        <div style="display:flex;justify-content:space-between;margin-bottom:5px">
          <strong>{{ ucfirst($fecha->translatedFormat('F Y')) }}</strong>
          <span style="color:#666">{{ $m->aprobados }} aprobados de {{ $m->enviados }}</span>
        </div>
        <div class="progress-bar" style="height:8px;position:relative">
          <div class="progress-fill" style="width:{{ round($m->enviados / $maxEv * 100) }}%;opacity:.25"></div>
          <div class="progress-fill" style="position:absolute;top:0;left:0;height:100%;width:{{ round($m->aprobados / $maxEv * 100) }}%"></div>
        </div>
      </div>
    @empty
      <p style="color:#999;font-size:13px;text-align:center;padding:20px">Aún no hay FUD enviados.</p>
    @endforelse
  </div>

  {{-- Por tipo --}}
  <div class="card">
    <div class="card-title">Distribución por tipo de negociación</div>
    @php $max = $porTipo->max('total') ?: 1; @endphp
    @forelse($porTipo as $item)
      <div style="display:flex;justify-content:space-between;align-items:center;padding:10px 0;border-bottom:1px solid #f0f0f0;font-size:13px">
        <span style="width:45%">{{ $item->etiqueta }}</span>
        <div style="flex:1;margin:0 12px">
          <div class="progress-bar">
            <div class="progress-fill" style="width:{{ round($item->total / $max * 100) }}%"></div>
          </div>
        </div>
        <strong style="color:var(--rojo)">{{ $item->total }}</strong>
      </div>
    @empty
      <p style="color:#999;font-size:13px;text-align:center;padding:20px">Sin datos aún.</p>
    @endforelse
  </div>
</div>

<div class="grid2">
  {{-- Por estado de cartera --}}
  <div class="card">
    <div class="card-title">Resultados por situación de cartera</div>
    <div style="overflow-x:auto">
      <table>
        <thead><tr><th>Cartera</th><th style="text-align:center">FUD</th><th style="text-align:center">Aprobados</th><th style="text-align:center">% aprob.</th></tr></thead>
        <tbody>
          @forelse($porEstadoCartera as $c)
            <tr>
              <td><strong>{{ $c->estado_cartera ?? 'Sin dato' }}</strong></td>
              <td style="text-align:center">{{ $c->total }}</td>
              <td style="text-align:center">{{ $c->aprobados }}</td>
              <td style="text-align:center">{{ $c->total ? round($c->aprobados / $c->total * 100) : 0 }}%</td>
            </tr>
          @empty
            <tr><td colspan="4" style="text-align:center;color:#999;padding:20px">Sin datos aún.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  {{-- Parámetros clave --}}
  <div class="card">
    <div class="card-title">📌 Parámetros clave de campaña</div>
    @foreach([
      'Vigencia campaña'                         => $campana ? $campana->fecha_inicio->format('d/m/Y').' – '.$campana->fecha_fin->format('d/m/Y') : '—',
      'Límite conciliaciones por cliente'        => $campana ? 'Máx. '.$campana->max_conciliaciones_12m.' en 12 meses' : '—',
      'Pago fraccionado (cancelación total)'     => $campana ? 'Máx. '.$campana->max_dias_pago_fraccionado.' días desde 1ra amortización' : '—',
      'Concil. intereses máx. (Dudoso/Pérdida ≥91d)' => '70% intereses + 100% moratorio',
      'Cartera Judicial/Extrajudicial máx.'      => '70% SK + 100% intereses + moratorio',
      'Cartera FOCMAC máx.'                      => '90% SK + 100% intereses + moratorio',
      'Pago cuotas – máx. monto conciliar'       => '50% del valor cuota pactada',
      'Reportes de conciliaciones'               => 'Primeros 2 días hábiles del mes',
    ] as $label => $val)
      <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;padding:10px 0;border-bottom:1px solid #f0f0f0;font-size:13px">
        <span>{{ $label }}</span>
        <strong style="text-align:right">{{ $val }}</strong>
      </div>
    @endforeach
  </div>
</div>

@endsection
