@extends('layouts.app')
@section('title', 'Reporte de campaña')

@use('App\Support\Estilo')

@section('content')
@php
  $etiquetasNeg = [
    'cancelacion' => 'Cancelación total', 'cuotas' => 'Pago de cuotas', 'extrajudicial' => 'Extrajudicial / judicial',
    'castigada' => 'Cartera castigada', 'focmac' => 'FOCMAC',
  ];
  $claseBarra = fn($p) => $p >= 70 ? 'ok' : ($p >= 50 ? 'warn' : 'danger');
@endphp

<div class="page-head">
  <div>
    <h2>Reporte de campaña</h2>
    <p>Resumen ejecutivo de la gestión · {{ $campana ? 'Campaña «'.$campana->nombre.'»' : 'Sin campaña activa' }} · actualizado al {{ now()->format('d/m/Y H:i') }}</p>
  </div>
  <div class="page-head-actions">
    <button type="button" class="btn btn-secondary" onclick="window.print()"><i class="bi bi-printer"></i> Imprimir</button>
  </div>
</div>

<form method="GET" class="card" style="padding:16px 20px">
  <div class="grid4" style="align-items:end">
    <div class="field">
      <label for="zona_id">Zona</label>
      <select id="zona_id" name="zona_id" onchange="this.form.tienda_id.value='';this.form.submit()">
        <option value="">Todas las zonas</option>
        @foreach($zonas as $z)<option value="{{ $z->id }}" @selected($zonaId == $z->id)>{{ $z->nombre }}</option>@endforeach
      </select>
    </div>
    <div class="field">
      <label for="tienda_id">Tienda</label>
      <select id="tienda_id" name="tienda_id" onchange="this.form.submit()">
        <option value="">Todas las tiendas</option>
        @foreach($tiendas as $t)<option value="{{ $t->id }}" @selected($tiendaId == $t->id)>{{ $t->nombre }}</option>@endforeach
      </select>
    </div>
    <div class="field">
      <label for="estado">Estado del FUD</label>
      <select id="estado" name="estado" onchange="this.form.submit()">
        <option value="">Todos</option>
        @foreach(['En revisión','Aprobado','Observado','Rechazado'] as $e)<option value="{{ $e }}" @selected($estado === $e)>{{ $e }}</option>@endforeach
      </select>
    </div>
    <div><a href="{{ route('reportes.dashboard') }}" class="btn btn-ghost"><i class="bi bi-x-circle"></i> Limpiar filtros</a></div>
  </div>
</form>

<div class="section-title">Propuestas FUD</div>
<div class="kpi-grid">
  <div class="kpi-card rojo">
    <div class="kpi-label"><i class="bi bi-bullseye"></i> Tasa de aprobación</div>
    <div class="kpi-value">{{ $cumplimientoPct }}%</div>
    <div class="kpi-sub">{{ $aprobados }} de {{ $totalFuds }} expedientes</div>
  </div>
  <div class="kpi-card verde">
    <div class="kpi-label"><i class="bi bi-check2-circle"></i> Aprobados</div>
    <div class="kpi-value">{{ number_format($aprobados) }}</div>
    <div class="kpi-sub">Conciliaciones autorizadas</div>
  </div>
  <div class="kpi-card info">
    <div class="kpi-label"><i class="bi bi-hourglass-split"></i> En revisión</div>
    <div class="kpi-value">{{ number_format($enRevision) }}</div>
    <div class="kpi-sub">Pendientes de decisión</div>
  </div>
  <div class="kpi-card mostaza">
    <div class="kpi-label"><i class="bi bi-arrow-return-left"></i> Observados</div>
    <div class="kpi-value">{{ number_format($observados) }}</div>
    <div class="kpi-sub">{{ $rechazados }} rechazados</div>
  </div>
  <div class="kpi-card">
    <div class="kpi-label"><i class="bi bi-exclamation-triangle"></i> Zonas críticas</div>
    <div class="kpi-value">{{ $zonasCriticas }}</div>
    <div class="kpi-sub">Con aprobación menor al 50%</div>
  </div>
</div>

<div class="section-title">Sustentos y montos</div>
<div class="kpi-grid">
  <div class="kpi-card verde">
    <div class="kpi-label"><i class="bi bi-shield-check"></i> Sustentos conformes</div>
    <div class="kpi-value">{{ $sustentosConformes }}</div>
    <div class="kpi-sub">{{ $cumplimientoSustentos }}% de {{ $totalSustentos }} sustentos</div>
  </div>
  <div class="kpi-card info">
    <div class="kpi-label"><i class="bi bi-clipboard-check"></i> En revisión</div>
    <div class="kpi-value">{{ $sustentosEnRevision }}</div>
    <div class="kpi-sub">Cargados, esperando revisor</div>
  </div>
  <div class="kpi-card mostaza">
    <div class="kpi-label"><i class="bi bi-paperclip"></i> Por cargar o corregir</div>
    <div class="kpi-value">{{ $sustentosPendientes }}</div>
    <div class="kpi-sub">Pendientes y observados</div>
  </div>
  <div class="kpi-card verde">
    <div class="kpi-label"><i class="bi bi-cash-stack"></i> Capital normalizado</div>
    <div class="kpi-value">{{ Estilo::solesCorto($montoNormalizado) }}</div>
    <div class="kpi-sub">Aprobado con sustento conforme</div>
  </div>
  <div class="kpi-card rojo">
    <div class="kpi-label"><i class="bi bi-exclamation-octagon"></i> Capital en riesgo</div>
    <div class="kpi-value">{{ Estilo::solesCorto($montoEnRiesgo) }}</div>
    <div class="kpi-sub">Aprobado sin sustento validado</div>
  </div>
</div>

<div class="grid2" style="margin-bottom:20px">
  <div class="card card-flush">
    <div class="card-head"><div class="card-title"><i class="bi bi-geo-alt"></i> Aprobación por zona</div></div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Zona</th><th class="num">FUD</th><th class="num">Aprobados</th><th style="min-width:150px">Tasa</th>
          <th class="num" title="Tasa de aprobación de lo decidido este mes frente al mes anterior">Var. mes ant.</th></tr></thead>
        <tbody>
          @forelse($cumplimientoPorZona as $fila)
            @php $v = $fila['variacion']; @endphp
            <tr>
              <td class="cell-main">{{ $fila['zona'] }}</td>
              <td class="num">{{ $fila['total'] }}</td>
              <td class="num">{{ $fila['aprobados'] }}</td>
              <td><div class="bar-row"><div class="bar-track"><div class="bar-fill {{ $claseBarra($fila['pct']) }}" style="width:{{ min($fila['pct'], 100) }}%"></div></div>
                <span class="fs-12 fw-600 num" style="min-width:44px;text-align:right">{{ $fila['pct'] }}%</span></div></td>
              <td class="num"><span class="kpi-delta {{ $v > 0 ? 'up' : ($v < 0 ? 'down' : 'flat') }}">
                <i class="bi {{ $v > 0 ? 'bi-arrow-up-short' : ($v < 0 ? 'bi-arrow-down-short' : 'bi-dash') }}"></i>{{ abs($v) }} pts</span></td>
            </tr>
          @empty
            <tr><td colspan="5" class="empty-state"><span>Sin datos para los filtros aplicados.</span></td></tr>
          @endforelse
        </tbody>
        @if($cumplimientoPorZona->count())
          <tfoot><tr>
            <td class="cell-main" style="padding:10px 12px;border-top:1px solid var(--borde)">Total</td>
            <td class="num fw-600" style="padding:10px 12px;border-top:1px solid var(--borde)">{{ $totalFuds }}</td>
            <td class="num fw-600" style="padding:10px 12px;border-top:1px solid var(--borde)">{{ $aprobados }}</td>
            <td class="fw-600" style="padding:10px 12px;border-top:1px solid var(--borde)">{{ $cumplimientoPct }}%</td>
            <td style="border-top:1px solid var(--borde)"></td>
          </tr></tfoot>
        @endif
      </table>
    </div>
  </div>

  <div class="card">
    <div class="card-title"><i class="bi bi-exclamation-octagon"></i> Capital en riesgo por zona</div>
    <p class="card-sub">Saldo capital de FUD aprobados cuyo sustento aún no está validado.</p>
    @php $maxMonto = $montoPorZona->max('monto') ?: 1; @endphp
    <div class="metric-list">
      @forelse($montoPorZona as $item)
        <div>
          <div class="metric-item-head"><span>{{ $item['zona'] }}</span><span>{{ Estilo::soles($item['monto']) }}</span></div>
          <div class="bar-track"><div class="bar-fill danger" style="width:{{ max(round($item['monto'] / $maxMonto * 100), 2) }}%"></div></div>
        </div>
      @empty
        <div class="empty-state"><i class="bi bi-check2-circle"></i><span>No hay capital en riesgo para los filtros aplicados.</span></div>
      @endforelse
    </div>
    <div class="form-section d-flex justify-content-between align-items-baseline">
      <span class="fs-13 text-gris">Total en riesgo</span>
      <span class="fw-600" style="font-size:18px">{{ Estilo::soles($montoEnRiesgo) }}</span>
    </div>
  </div>
</div>

<div class="grid3" style="margin-bottom:20px">
  <div class="card">
    <div class="card-title"><i class="bi bi-shop"></i> Tiendas con menor aprobación</div>
    <div class="metric-list">
      @forelse($topTiendasBajo as $i => $t)
        <div>
          <div class="metric-item-head"><span><span class="text-gris num">{{ $i + 1 }}.</span> {{ $t['tienda'] }} <span class="cell-sub">· {{ $t['zona'] }}</span></span>
            <span>{{ $t['pct'] }}% · {{ $t['aprobados'] }}/{{ $t['total'] }}</span></div>
          <div class="bar-track"><div class="bar-fill {{ $claseBarra($t['pct']) }}" style="width:{{ max($t['pct'], 2) }}%"></div></div>
        </div>
      @empty
        <div class="empty-state"><span>Sin datos.</span></div>
      @endforelse
    </div>
  </div>

  <div class="card">
    <div class="card-title"><i class="bi bi-file-earmark-x"></i> Documentos faltantes</div>
    <p class="card-sub">Sustentos por cargar o corregir sin el documento conforme.</p>
    @php $maxDoc = max(array_column($documentosFaltantes, 'faltantes')) ?: 1; @endphp
    <div class="metric-list">
      @foreach($documentosFaltantes as $doc)
        <div>
          <div class="metric-item-head"><span>{{ $doc['documento'] === 'Informe-Propuesta SK>3UIT' ? 'Informe-Propuesta (SK > 3 UIT)' : $doc['documento'] }}</span><span>{{ $doc['faltantes'] }}</span></div>
          <div class="bar-track"><div class="bar-fill warn" style="width:{{ max(round($doc['faltantes'] / $maxDoc * 100), $doc['faltantes'] ? 2 : 0) }}%"></div></div>
        </div>
      @endforeach
    </div>
  </div>

  <div class="card">
    <div class="card-title"><i class="bi bi-pie-chart"></i> Por tipo de negociación</div>
    @php $totalTipos = $porTipoNeg->sum('total') ?: 1; @endphp
    <div class="metric-list">
      @forelse($porTipoNeg as $tipo)
        @php $pct = round($tipo->total / $totalTipos * 100); @endphp
        <div>
          <div class="metric-item-head"><span>{{ $etiquetasNeg[$tipo->tipo_negociacion] ?? ucfirst($tipo->tipo_negociacion) }}</span>
            <span>{{ $tipo->total }} · {{ $tipo->aprobados }} aprobados</span></div>
          <div class="bar-track"><div class="bar-fill info" style="width:{{ $pct }}%"></div></div>
        </div>
      @empty
        <div class="empty-state"><span>Sin datos.</span></div>
      @endforelse
    </div>
  </div>
</div>

<div class="card">
  <div class="card-head">
    <div class="card-title"><i class="bi bi-bank"></i> Programas de gobierno</div>
    <a href="{{ route('gobierno.index') }}" class="btn btn-secondary btn-sm">Ver expedientes</a>
  </div>
  <div class="grid2">
    <div class="grid2" style="gap:12px">
      <div class="card-plain" style="margin:0;padding:14px"><div class="detail-label">Expedientes</div><div class="detail-value lg">{{ $gobierno['total'] }}</div></div>
      <div class="card-plain" style="margin:0;padding:14px"><div class="detail-label">Completos</div><div class="detail-value lg text-verde">{{ $gobierno['completos'] }}</div></div>
      <div class="card-plain" style="margin:0;padding:14px"><div class="detail-label">Incompletos</div><div class="detail-value lg text-mostaza">{{ $gobierno['incompletos'] }}</div></div>
      <div class="card-plain" style="margin:0;padding:14px"><div class="detail-label">Avance documentario promedio</div><div class="detail-value lg">{{ $gobierno['avance'] }}%</div></div>
    </div>
    <div class="metric-list">
      @forelse($gobierno['porPrograma'] as $programa => $g)
        @php $pctG = $g['total'] ? round($g['completos'] / $g['total'] * 100) : 0; @endphp
        <div>
          <div class="metric-item-head"><span>{{ $programa }}</span><span>{{ $g['completos'] }} de {{ $g['total'] }} completos</span></div>
          <div class="bar-track"><div class="bar-fill {{ $claseBarra($pctG) }}" style="width:{{ max($pctG, 2) }}%"></div></div>
        </div>
      @empty
        <div class="empty-state"><span>Aún no hay expedientes registrados.</span></div>
      @endforelse
    </div>
  </div>
</div>

<div class="grid2">
  <div class="card">
    <div class="card-title"><i class="bi bi-diagram-2"></i> Expedientes por estado</div>
    <div class="metric-list">
      @foreach($porEstado as $est => $count)
        @php $pct = $totalFuds > 0 ? round($count / $totalFuds * 100) : 0; @endphp
        <div>
          <div class="metric-item-head"><span><span class="badge {{ Estilo::badge($est) }}">{{ $est }}</span></span><span>{{ number_format($count) }} · {{ $pct }}%</span></div>
          <div class="bar-track"><div class="bar-fill {{ ['Aprobado' => 'ok', 'En revisión' => 'info', 'Observado' => 'warn', 'Rechazado' => 'danger'][$est] }}" style="width:{{ $pct }}%"></div></div>
        </div>
      @endforeach
    </div>
  </div>

  <div class="card">
    <div class="card-head">
      <div class="card-title"><i class="bi bi-activity"></i> Decisiones recientes</div>
      <a href="{{ route('seguimiento.index') }}" class="btn btn-ghost btn-sm">Ver seguimiento <i class="bi bi-chevron-right"></i></a>
    </div>
    <div class="timeline">
      @forelse($actividadReciente as $fud)
        <div class="timeline-item {{ ['Aprobado' => 'ok', 'Observado' => 'warn', 'Rechazado' => 'danger'][$fud->estado_fud] ?? '' }}">
          <div class="timeline-title">{{ $fud->cliente->nombre_cliente }} <span class="badge {{ Estilo::badge($fud->estado_fud) }}" style="margin-left:4px">{{ $fud->estado_fud }}</span></div>
          <div class="timeline-meta">{{ $fud->tipo_negociacion_desc }} · {{ $fud->cliente->saldo_formateado }} · {{ $fud->aprobadoPor->name ?? $fud->enviador->name }} · {{ $fud->fecha_decision?->format('d/m/Y') }}</div>
        </div>
      @empty
        <div class="empty-state"><span>Sin decisiones registradas.</span></div>
      @endforelse
    </div>
  </div>
</div>
@endsection
