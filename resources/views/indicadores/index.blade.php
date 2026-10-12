@extends('layouts.app')
@section('title', 'Indicadores')

@section('content')
<div class="page-head">
  <div>
    <h2>Indicadores de campaña</h2>
    @if($campana)
      <p>Campaña «{{ $campana->nombre }}» · {{ $campana->fecha_inicio->format('d/m/Y') }} al {{ $campana->fecha_fin->format('d/m/Y') }}</p>
    @else
      <p>No hay una campaña activa configurada.</p>
    @endif
  </div>
  @if($campana)
    @php $vigente = now()->between($campana->fecha_inicio, $campana->fecha_fin->copy()->endOfDay()); @endphp
    <div class="page-head-actions">
      <span class="badge {{ $vigente ? 'badge-ok' : 'badge-gray' }} dot">
        {{ $vigente ? 'Vigente · '.(int) now()->diffInDays($campana->fecha_fin).' días restantes' : 'Fuera de vigencia' }}
      </span>
    </div>
  @endif
</div>

<div class="kpi-grid">
  <div class="kpi-card">
    <div class="kpi-label"><i class="bi bi-folder2"></i> Expedientes FUD</div>
    <div class="kpi-value">{{ $total }}</div>
    <div class="kpi-sub">Vigentes (última versión)</div>
  </div>
  <div class="kpi-card info">
    <div class="kpi-label"><i class="bi bi-hourglass-split"></i> En revisión</div>
    <div class="kpi-value">{{ $revision }}</div>
    <div class="kpi-sub">Esperando aprobación</div>
  </div>
  <div class="kpi-card verde">
    <div class="kpi-label"><i class="bi bi-check2-circle"></i> Aprobadas</div>
    <div class="kpi-value">{{ $aprobadas }}</div>
    <div class="kpi-sub">Tasa de aprobación {{ $tasa }}%</div>
  </div>
  <div class="kpi-card mostaza">
    <div class="kpi-label"><i class="bi bi-bank"></i> Programas de gobierno</div>
    <div class="kpi-value">{{ $gobCompletos }}<span class="fs-13 text-gris"> / {{ $gobTotal }}</span></div>
    <div class="kpi-sub">Expedientes completos</div>
  </div>
</div>

<div class="grid2" style="margin-bottom:20px">
  <div class="card">
    <div class="card-title"><i class="bi bi-calendar3"></i> Evolución mensual</div>
    <p class="card-sub">Propuestas enviadas y aprobadas por mes.</p>
    @php $maxEv = $evolucion->max('enviados') ?: 1; @endphp
    <div class="metric-list">
      @forelse($evolucion as $m)
        @php $fecha = \Carbon\Carbon::createFromFormat('Y-m', $m->mes)->locale('es'); @endphp
        <div>
          <div class="metric-item-head"><span>{{ ucfirst($fecha->translatedFormat('F Y')) }}</span><span>{{ $m->aprobados }} aprobadas de {{ $m->enviados }}</span></div>
          <div class="bar-track" style="height:8px">
            <div class="bar-fill muted" style="position:absolute;inset:0 auto 0 0;width:{{ round($m->enviados / $maxEv * 100) }}%"></div>
            <div class="bar-fill" style="position:absolute;inset:0 auto 0 0;width:{{ round($m->aprobados / $maxEv * 100) }}%"></div>
          </div>
        </div>
      @empty
        <div class="empty-state"><span>Aún no hay propuestas enviadas.</span></div>
      @endforelse
    </div>
    <div class="d-flex gap-3 fs-12 text-gris" style="margin-top:14px">
      <span class="d-inline-flex align-items-center gap-1"><span style="width:10px;height:10px;border-radius:2px;background:var(--rojo)"></span> Aprobadas</span>
      <span class="d-inline-flex align-items-center gap-1"><span style="width:10px;height:10px;border-radius:2px;background:var(--borde-fuerte)"></span> Enviadas</span>
    </div>
  </div>

  <div class="card">
    <div class="card-title"><i class="bi bi-pie-chart"></i> Distribución por tipo de negociación</div>
    @php $max = $porTipo->max('total') ?: 1; @endphp
    <div class="metric-list">
      @forelse($porTipo as $item)
        <div>
          <div class="metric-item-head"><span>{{ $item->etiqueta }}</span><span>{{ $item->total }}</span></div>
          <div class="bar-track"><div class="bar-fill info" style="width:{{ round($item->total / $max * 100) }}%"></div></div>
        </div>
      @empty
        <div class="empty-state"><span>Sin datos aún.</span></div>
      @endforelse
    </div>
  </div>
</div>

<div class="grid2">
  <div class="card card-flush">
    <div class="card-head"><div class="card-title"><i class="bi bi-layers"></i> Resultados por situación de cartera</div></div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Cartera</th><th class="num">FUD</th><th class="num">Aprobados</th><th class="num">% aprobación</th></tr></thead>
        <tbody>
          @forelse($porEstadoCartera as $c)
            <tr>
              <td class="cell-main">{{ $c->estado_cartera ? ucfirst(strtolower($c->estado_cartera)) : 'Sin dato' }}</td>
              <td class="num">{{ $c->total }}</td>
              <td class="num">{{ $c->aprobados }}</td>
              <td class="num">{{ $c->total ? round($c->aprobados / $c->total * 100) : 0 }}%</td>
            </tr>
          @empty
            <tr><td colspan="4" class="empty-state"><span>Sin datos aún.</span></td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  <div class="card card-flush">
    <div class="card-head"><div class="card-title"><i class="bi bi-sliders"></i> Parámetros de la campaña</div></div>
    <div class="table-wrap">
      <table>
        <tbody>
          @foreach([
            'Vigencia'                                   => $campana ? $campana->fecha_inicio->format('d/m/Y').' al '.$campana->fecha_fin->format('d/m/Y') : '—',
            'Límite de conciliaciones por cliente'       => $campana ? 'Máximo '.$campana->max_conciliaciones_12m.' en 12 meses' : '—',
            'Pago fraccionado (cancelación total)'       => $campana ? 'Máximo '.$campana->max_dias_pago_fraccionado.' días desde la primera amortización' : '—',
            'Pago de cuotas · monto conciliado'          => 'Hasta 50% del valor de la cuota pactada',
            'Intereses · Dudoso/Pérdida, 91 días o más' => '70% de intereses y 100% de moratorio',
            'Cartera judicial y extrajudicial'           => '70% de SK y 100% de intereses y moratorio',
            'Cartera FOCMAC'                             => '90% de SK y 100% de intereses y moratorio',
            'Reporte de conciliaciones'                  => 'Primeros 2 días hábiles del mes',
          ] as $label => $val)
            <tr><td class="text-gris">{{ $label }}</td><td class="fw-600" style="text-align:right">{{ $val }}</td></tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection
