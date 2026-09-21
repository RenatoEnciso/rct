@extends('layouts.app')
@section('title', 'Dashboard de Reportes')

@push('styles')
<style>
/* ── KPI grande ─────────────────────────── */
.kpi-lg {
    background: #fff;
    border-radius: 12px;
    padding: 18px 22px;
    box-shadow: var(--shadow);
    border: 1px solid var(--borde);
    position: relative;
    overflow: hidden;
}
.kpi-lg::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 4px;
}
.kpi-lg.rojo::before   { background: var(--rojo); }
.kpi-lg.verde::before  { background: var(--verde); }
.kpi-lg.mostaza::before{ background: var(--mostaza); }
.kpi-lg.azul::before   { background: #1a56a0; }
.kpi-lg.gris::before   { background: #667085; }

.kpi-lg-numero {
    font-size: 36px;
    font-weight: 800;
    line-height: 1;
    margin: 6px 0 4px;
}
.kpi-lg.rojo    .kpi-lg-numero { color: var(--rojo); }
.kpi-lg.verde   .kpi-lg-numero { color: var(--verde); }
.kpi-lg.mostaza .kpi-lg-numero { color: #a07a00; }
.kpi-lg.azul    .kpi-lg-numero { color: #1a56a0; }
.kpi-lg.gris    .kpi-lg-numero { color: #667085; }

.kpi-lg-label {
    font-size: 10px;
    font-weight: 700;
    color: #888;
    text-transform: uppercase;
    letter-spacing: 0.6px;
}
.kpi-lg-sub {
    font-size: 11px;
    color: #aaa;
    margin-top: 3px;
}
.kpi-icon {
    position: absolute;
    right: 16px; top: 50%;
    transform: translateY(-50%);
    opacity: 0.07;
}

/* ── Tabla de cumplimiento ──────────────── */
.tbl-cump thead th {
    background: var(--rojo-dark);
    color: #fff;
    padding: 10px 14px;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    font-weight: 700;
}
.tbl-cump tbody td {
    padding: 10px 14px;
    border-bottom: 1px solid #f0f0f0;
    font-size: 13px;
    vertical-align: middle;
}
.tbl-cump tbody tr:hover { background: #fafafa; }

/* ── Barra de progreso inline ───────────── */
.bar-wrap {
    display: flex; align-items: center; gap: 10px;
}
.bar-track {
    flex: 1; height: 8px;
    background: #eef0f3;
    border-radius: 10px;
    overflow: hidden;
    min-width: 80px;
}
.bar-fill {
    height: 100%;
    border-radius: 10px;
    transition: width 0.6s ease;
}
.bar-fill.ok      { background: var(--verde); }
.bar-fill.warn    { background: var(--mostaza); }
.bar-fill.danger  { background: var(--rojo); }

/* ── Barras horizontales (gráfico zona) ─── */
.hbar-item {
    display: flex; align-items: center;
    gap: 12px; margin-bottom: 10px;
}
.hbar-label {
    font-size: 12px; font-weight: 600;
    min-width: 100px; color: #333;
    white-space: nowrap;
    overflow: hidden; text-overflow: ellipsis;
}
.hbar-track {
    flex: 1; height: 22px;
    background: #eef0f3;
    border-radius: 6px;
    overflow: hidden;
    position: relative;
}
.hbar-fill {
    height: 100%;
    background: linear-gradient(90deg, var(--rojo-dark), var(--rojo));
    border-radius: 6px;
    display: flex; align-items: center;
    justify-content: flex-end;
    padding-right: 8px;
    transition: width 0.8s ease;
    min-width: 40px;
}
.hbar-fill span {
    font-size: 11px; font-weight: 700; color: #fff;
    white-space: nowrap;
}
.hbar-monto {
    font-size: 12px; color: #666; min-width: 70px;
    text-align: right; font-weight: 600;
}

/* ── Variación ──────────────────────────── */
.var-up   { color: var(--verde);   font-weight:700; font-size:12px }
.var-down { color: #c0392b;        font-weight:700; font-size:12px }
.var-flat { color: #888;           font-weight:700; font-size:12px }

/* ── Dona (CSS puro) ────────────────────── */
.dona-wrap {
    display: flex;
    align-items: center;
    gap: 24px;
    flex-wrap: wrap;
}
.dona-svg { flex-shrink: 0; }
.dona-leyenda { display: flex; flex-direction: column; gap: 8px; }
.dona-item {
    display: flex; align-items: center; gap: 8px;
    font-size: 12px;
}
.dona-dot {
    width: 10px; height: 10px;
    border-radius: 50%; flex-shrink: 0;
}

/* ── Tabla tiendas ──────────────────────── */
.rank-num {
    width: 24px; height: 24px;
    border-radius: 50%;
    background: var(--rojo);
    color: #fff;
    font-size: 10px; font-weight: 800;
    display: inline-flex;
    align-items: center; justify-content: center;
}

/* ── Tabla docs faltantes ───────────────── */
.doc-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 9px 0;
    border-bottom: 1px solid #f2f3f5;
    font-size: 13px;
    gap: 12px;
}
.doc-row:last-child { border-bottom: none; }

/* ── Filtros ────────────────────────────── */
.filtro-bar {
    background: #fff;
    border: 1px solid var(--borde);
    border-radius: 10px;
    padding: 14px 18px;
    margin-bottom: 20px;
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
    align-items: flex-end;
}
.filtro-bar .field { margin: 0; min-width: 160px; flex: 1; }

/* ── Actividad reciente ─────────────────── */
.act-item {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 10px 0;
    border-bottom: 1px solid #f2f3f5;
}
.act-item:last-child { border-bottom: none; }
.act-dot {
    width: 32px; height: 32px;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0; font-size: 13px;
}
</style>
@endpush

@section('content')

{{-- ── CABECERA ─────────────────────────────────────────── --}}
<div class="page-head">
  <div class="page-head-text">
    <h2>Dashboard de Reportes</h2>
    <p>Campaña <strong>"Sin Atrasos que Atrasan"</strong> · Resumen ejecutivo de gestión</p>
  </div>
  <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
    <span class="badge badge-ok">🟢 Campaña Activa</span>
    <span class="badge badge-info">{{ auth()->user()->rol->nombre }}</span>
    <span style="font-size:12px;color:#888">
      Actualizado: {{ now()->format('d/m/Y H:i') }}
    </span>
  </div>
</div>

{{-- ── FILTROS ──────────────────────────────────────────── --}}
<form method="GET" class="filtro-bar">
  <div class="field">
    <label>Zona</label>
    <select name="zona_id" onchange="this.form.submit()">
      <option value="">Todas las zonas</option>
      @foreach($zonas as $z)
        <option value="{{ $z->id }}" {{ $zonaId == $z->id ? 'selected' : '' }}>
          {{ $z->nombre }}
        </option>
      @endforeach
    </select>
  </div>
  <div class="field">
    <label>Estado FUD</label>
    <select name="estado" onchange="this.form.submit()">
      <option value="">Todos</option>
      @foreach(['En revisión','Aprobado','Observado','Rechazado'] as $e)
        <option value="{{ $e }}" {{ $estado === $e ? 'selected' : '' }}>{{ $e }}</option>
      @endforeach
    </select>
  </div>
  <div style="display:flex;align-items:flex-end">
    <a href="{{ route('reportes.dashboard') }}" class="btn btn-ghost btn-sm">
      Limpiar filtros
    </a>
  </div>
</form>

{{-- ── KPIs FILA 1 ─────────────────────────────────────── --}}
<div style="display:grid;grid-template-columns:repeat(6,1fr);gap:14px;margin-bottom:20px">

  {{-- Cumplimiento % --}}
  <div class="kpi-lg rojo" style="grid-column:span 1">
    <div class="kpi-lg-label">Cumplimiento actual</div>
    <div class="kpi-lg-numero">{{ $cumplimientoPct }}%</div>
    <div class="kpi-lg-sub">FUDs aprobados / total</div>
    <div class="kpi-icon">
      <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="var(--rojo)" stroke-width="1">
        <path d="M22 12h-4l-3 9L9 3l-3 9H2"/>
      </svg>
    </div>
  </div>

  {{-- Aprobados --}}
  <div class="kpi-lg verde">
    <div class="kpi-lg-label">Aprobados</div>
    <div class="kpi-lg-numero">{{ number_format($aprobados) }}</div>
    <div class="kpi-lg-sub">FUDs completos</div>
    <div class="kpi-icon">
      <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="var(--verde)" stroke-width="1">
        <circle cx="12" cy="12" r="10"/>
        <polyline points="9 12 11 14 15 10"/>
      </svg>
    </div>
  </div>

  {{-- En revisión --}}
  <div class="kpi-lg mostaza">
    <div class="kpi-lg-label">En revisión</div>
    <div class="kpi-lg-numero">{{ number_format($enRevision) }}</div>
    <div class="kpi-lg-sub">Pendientes de aprobación</div>
    <div class="kpi-icon">
      <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="var(--mostaza)" stroke-width="1">
        <circle cx="12" cy="12" r="10"/>
        <polyline points="12 6 12 12 16 14"/>
      </svg>
    </div>
  </div>

  {{-- Observados --}}
  <div class="kpi-lg gris">
    <div class="kpi-lg-label">Observados</div>
    <div class="kpi-lg-numero">{{ number_format($observados) }}</div>
    <div class="kpi-lg-sub">Requieren corrección</div>
    <div class="kpi-icon">
      <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="#667085" stroke-width="1">
        <circle cx="12" cy="12" r="10"/>
        <line x1="12" y1="8" x2="12" y2="12"/>
        <line x1="12" y1="16" x2="12.01" y2="16"/>
      </svg>
    </div>
  </div>

  {{-- Zonas críticas --}}
  <div class="kpi-lg rojo">
    <div class="kpi-lg-label">Zonas críticas</div>
    <div class="kpi-lg-numero">{{ $zonasCriticas }}</div>
    <div class="kpi-lg-sub">Con cumplimiento &lt; 50%</div>
    <div class="kpi-icon">
      <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="var(--rojo)" stroke-width="1">
        <path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
        <line x1="12" y1="9" x2="12" y2="13"/>
        <line x1="12" y1="17" x2="12.01" y2="17"/>
      </svg>
    </div>
  </div>

  {{-- Monto honrado --}}
  <div class="kpi-lg azul">
    <div class="kpi-lg-label">Monto honrado</div>
    <div class="kpi-lg-numero" style="font-size:24px">
      S/ {{ number_format($montoHonrado / 1000000, 2) }}M
    </div>
    <div class="kpi-lg-sub">Capital conciliado</div>
    <div class="kpi-icon">
      <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="#1a56a0" stroke-width="1">
        <line x1="12" y1="1" x2="12" y2="23"/>
        <path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/>
      </svg>
    </div>
  </div>

</div>

{{-- ── KPIs SUSTENTOS ──────────────────────────────────── --}}
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:20px">
  <div class="kpi-card">
    <div class="kpi-label">Total sustentos</div>
    <div class="kpi-value">{{ $totalSustentos }}</div>
    <div class="kpi-sub">Post-aprobación</div>
  </div>
  <div class="kpi-card verde">
    <div class="kpi-label">Sustentos completos</div>
    <div class="kpi-value">{{ $sustentosCompletos }}</div>
    <div class="kpi-sub">Documentación OK</div>
  </div>
  <div class="kpi-card mostaza">
    <div class="kpi-label">Sustentos pendientes</div>
    <div class="kpi-value">{{ $sustentosPendientes }}</div>
    <div class="kpi-sub">Faltan documentos</div>
  </div>
  <div class="kpi-card info">
    <div class="kpi-label">Cumplim. sustentos</div>
    <div class="kpi-value">{{ $cumplimientoSustentos }}%</div>
    <div class="kpi-sub">% con docs completos</div>
  </div>
</div>

{{-- ── FILA PRINCIPAL: tabla zona + gráfico barras ──────── --}}
<div class="grid2" style="margin-bottom:20px">

  {{-- Tabla cumplimiento por zona --}}
  <div class="card" style="overflow:hidden">
    <div class="card-title">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>
        <polyline points="9 22 9 12 15 12 15 22"/>
      </svg>
      Cumplimiento por Zona
    </div>
    <div style="overflow-x:auto">
      <table class="tbl-cump" style="width:100%;border-collapse:collapse">
        <thead>
          <tr>
            <th style="text-align:left">Zona</th>
            <th style="text-align:center">Total FUDs</th>
            <th style="text-align:center">Aprobados</th>
            <th style="text-align:left;min-width:160px">Cumplimiento Actual</th>
            <th style="text-align:center">Variación</th>
          </tr>
        </thead>
        <tbody>
          @forelse($cumplimientoPorZona as $fila)
            @php
              $barClass = $fila['pct'] >= 70 ? 'ok' : ($fila['pct'] >= 50 ? 'warn' : 'danger');
              $varClass = $fila['variacion'] > 0 ? 'var-up' : ($fila['variacion'] < 0 ? 'var-down' : 'var-flat');
              $varIcon  = $fila['variacion'] > 0 ? '▲' : ($fila['variacion'] < 0 ? '▼' : '→');
            @endphp
            <tr>
              <td><strong>{{ $fila['zona'] }}</strong></td>
              <td style="text-align:center">{{ $fila['total'] }}</td>
              <td style="text-align:center">{{ $fila['aprobados'] }}</td>
              <td>
                <div class="bar-wrap">
                  <div class="bar-track">
                    <div class="bar-fill {{ $barClass }}"
                         style="width:{{ min($fila['pct'],100) }}%"></div>
                  </div>
                  <span style="font-size:12px;font-weight:700;min-width:44px;
                               color:{{ $barClass === 'ok' ? 'var(--verde)' : ($barClass === 'warn' ? '#a07a00' : 'var(--rojo)') }}">
                    {{ $fila['pct'] }}%
                  </span>
                </div>
              </td>
              <td style="text-align:center">
                <span class="{{ $varClass }}">
                  {{ $varIcon }} {{ abs($fila['variacion']) }}%
                </span>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="5" style="text-align:center;color:#999;padding:24px">
                Sin datos disponibles.
              </td>
            </tr>
          @endforelse

          {{-- Total --}}
          <tr style="background:#f8f9fa;font-weight:700">
            <td>Total</td>
            <td style="text-align:center">{{ $totalFuds }}</td>
            <td style="text-align:center">{{ $aprobados }}</td>
            <td>
              <div class="bar-wrap">
                <div class="bar-track">
                  <div class="bar-fill {{ $cumplimientoPct >= 70 ? 'ok' : ($cumplimientoPct >= 50 ? 'warn' : 'danger') }}"
                       style="width:{{ min($cumplimientoPct,100) }}%"></div>
                </div>
                <span style="font-size:12px;font-weight:800;min-width:44px;color:var(--rojo)">
                  {{ $cumplimientoPct }}%
                </span>
              </div>
            </td>
            <td></td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

  {{-- Gráfico barras horizontales: monto por zona --}}
  <div class="card">
    <div class="card-title">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <line x1="18" y1="20" x2="18" y2="10"/>
        <line x1="12" y1="20" x2="12" y2="4"/>
        <line x1="6"  y1="20" x2="6"  y2="14"/>
      </svg>
      Monto en riesgo por zona
    </div>
    @php $maxMonto = $montoPorZona->max('monto') ?: 1; @endphp
    @forelse($montoPorZona->take(8) as $item)
      @php $pctBar = round($item['monto'] / $maxMonto * 100); @endphp
      <div class="hbar-item">
        <div class="hbar-label" title="{{ $item['zona'] }}">
          {{ Str::limit($item['zona'], 12) }}
        </div>
        <div class="hbar-track">
          <div class="hbar-fill" style="width:{{ max($pctBar, 5) }}%">
            <span>S/ {{ number_format($item['monto'] / 1000, 1) }}K</span>
          </div>
        </div>
        <div class="hbar-monto">
          S/ {{ number_format($item['monto'] / 1000000, 2) }}M
        </div>
      </div>
    @empty
      <p style="color:#999;font-size:13px;text-align:center;padding:20px">Sin datos.</p>
    @endforelse

    {{-- Monto en riesgo total --}}
    <div style="margin-top:16px;padding-top:14px;border-top:2px solid var(--mostaza)">
      <div style="display:flex;justify-content:space-between;align-items:center">
        <div>
          <div style="font-size:10px;color:#888;text-transform:uppercase;font-weight:700">
            Monto en riesgo total
          </div>
          <div style="font-size:20px;font-weight:800;color:var(--rojo)">
            S/ {{ number_format($montoEnRiesgo / 1000000, 2) }}M
          </div>
          <div style="font-size:11px;color:#aaa">
            Capital de FUDs aprobados sin sustentos
          </div>
        </div>
        <svg width="48" height="48" viewBox="0 0 24 24" fill="none"
             stroke="var(--rojo)" stroke-width="1.5" opacity="0.3">
          <path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
          <line x1="12" y1="9" x2="12" y2="13"/>
          <line x1="12" y1="17" x2="12.01" y2="17"/>
        </svg>
      </div>
    </div>
  </div>

</div>

{{-- ── FILA: tiendas + docs faltantes + distribución ────── --}}
<div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;margin-bottom:20px">

  {{-- Top tiendas con menor cumplimiento --}}
  <div class="card" style="grid-column:span 1">
    <div class="card-title">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/>
        <circle cx="9" cy="7" r="4"/>
        <path d="M23 21v-2a4 4 0 00-3-3.87"/>
        <path d="M16 3.13a4 4 0 010 7.75"/>
      </svg>
      Top tiendas menor cumplimiento
    </div>
    @forelse($topTiendasBajo as $i => $t)
      <div style="display:flex;align-items:center;gap:10px;padding:7px 0;
                  border-bottom:1px solid #f2f3f5;font-size:12px">
        <span class="rank-num" style="{{ $i < 3 ? 'background:var(--rojo)' : 'background:#888' }}">
          {{ $i + 1 }}
        </span>
        <div style="flex:1;min-width:0">
          <div style="font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
            {{ $t['tienda'] }}
          </div>
          <div style="font-size:11px;color:#aaa">{{ $t['zona'] }}</div>
        </div>
        <div style="text-align:right;flex-shrink:0">
          <div style="font-weight:800;color:{{ $t['pct'] < 30 ? 'var(--rojo)' : ($t['pct'] < 60 ? '#a07a00' : 'var(--verde)') }}">
            {{ $t['pct'] }}%
          </div>
          <div style="font-size:10px;color:#aaa">{{ $t['aprobados'] }}/{{ $t['total'] }}</div>
        </div>
      </div>
    @empty
      <p style="color:#999;font-size:13px;text-align:center;padding:20px">Sin datos.</p>
    @endforelse
  </div>

  {{-- Documentos faltantes en sustentos --}}
  <div class="card">
    <div class="card-title">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
        <polyline points="14 2 14 8 20 8"/>
        <line x1="12" y1="18" x2="12" y2="12"/>
        <line x1="9" y1="15" x2="15" y2="15"/>
      </svg>
      Documentos faltantes en sustentos
    </div>
    @foreach($documentosFaltantes as $doc)
      @php
        $maxDoc   = max(array_column($documentosFaltantes, 'faltantes')) ?: 1;
        $pctDoc   = round($doc['faltantes'] / $maxDoc * 100);
        $colorDoc = $doc['faltantes'] > 50 ? 'var(--rojo)' : ($doc['faltantes'] > 20 ? '#a07a00' : 'var(--verde)');
      @endphp
      <div class="doc-row">
        <div style="flex:1;min-width:0">
          <div style="font-size:12px;font-weight:600">{{ $doc['documento'] }}</div>
          <div class="bar-track" style="margin-top:4px">
            <div class="bar-fill {{ $doc['faltantes'] > 50 ? 'danger' : ($doc['faltantes'] > 20 ? 'warn' : 'ok') }}"
                 style="width:{{ max($pctDoc,2) }}%"></div>
          </div>
        </div>
        <div style="font-size:18px;font-weight:800;color:{{ $colorDoc }};
                    margin-left:12px;flex-shrink:0">
          {{ $doc['faltantes'] }}
        </div>
      </div>
    @endforeach
  </div>

  {{-- Distribución por tipo de negociación --}}
  <div class="card">
    <div class="card-title">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <circle cx="12" cy="12" r="10"/>
        <line x1="12" y1="8" x2="12" y2="16"/>
        <line x1="8" y1="12" x2="16" y2="12"/>
      </svg>
      Por tipo de negociación
    </div>
    @php
      $coloresTipo = [
        'cancelacion'   => ['bg' => 'var(--rojo)',    'label' => 'Cancelación Total'],
        'cuotas'        => ['bg' => 'var(--mostaza)', 'label' => 'Pago de Cuotas'],
        'extrajudicial' => ['bg' => '#1a56a0',        'label' => 'Extrajudicial/Judicial'],
        'castigada'     => ['bg' => '#7b2d8b',        'label' => 'Cartera Castigada'],
        'focmac'        => ['bg' => 'var(--verde)',   'label' => 'FOCMAC'],
      ];
      $totalTipos = $porTipoNeg->sum('total') ?: 1;
    @endphp
    @forelse($porTipoNeg as $tipo)
      @php
        $cfg     = $coloresTipo[$tipo->tipo_negociacion] ?? ['bg' => '#888', 'label' => ucfirst($tipo->tipo_negociacion)];
        $pctTipo = round($tipo->total / $totalTipos * 100);
        $aprobPct = $tipo->total > 0 ? round($tipo->aprobados / $tipo->total * 100) : 0;
      @endphp
      <div style="margin-bottom:12px">
        <div style="display:flex;justify-content:space-between;
                    font-size:12px;font-weight:600;margin-bottom:4px">
          <span>{{ $cfg['label'] }}</span>
          <span style="color:#888">{{ $tipo->total }} ({{ $pctTipo }}%)</span>
        </div>
        <div class="bar-track" style="height:12px">
          <div style="height:100%;width:{{ $pctTipo }}%;
                      background:{{ $cfg['bg'] }};border-radius:10px;
                      transition:width 0.6s ease"></div>
        </div>
        <div style="font-size:10px;color:#aaa;margin-top:2px">
          {{ $tipo->aprobados }} aprobados ({{ $aprobPct }}%)
        </div>
      </div>
    @empty
      <p style="color:#999;font-size:13px;text-align:center;padding:20px">Sin datos.</p>
    @endforelse
  </div>

</div>

{{-- ── FILA: estados (dona visual) + actividad reciente ─── --}}
<div class="grid2" style="margin-bottom:20px">

  {{-- Distribución por estado --}}
  <div class="card">
    <div class="card-title">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <circle cx="12" cy="12" r="10"/>
        <polyline points="12 6 12 12 16 14"/>
      </svg>
      Distribución de FUDs por estado
    </div>
    @php
      $estadosConfig = [
        'Aprobado'    => ['color' => 'var(--verde)',   'icon' => '✅'],
        'En revisión' => ['color' => 'var(--mostaza)', 'icon' => '⏳'],
        'Observado'   => ['color' => '#667085',        'icon' => '👁'],
        'Rechazado'   => ['color' => 'var(--rojo)',    'icon' => '❌'],
      ];
    @endphp
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
      @foreach($porEstado as $estado => $count)
        @php
          $cfg  = $estadosConfig[$estado] ?? ['color' => '#888', 'icon' => '•'];
          $pct  = $totalFuds > 0 ? round($count / $totalFuds * 100) : 0;
        @endphp
        <div style="background:#f8f9fa;border-radius:10px;padding:14px;
                    border-left:4px solid {{ $cfg['color'] }}">
          <div style="font-size:20px;margin-bottom:4px">{{ $cfg['icon'] }}</div>
          <div style="font-size:24px;font-weight:800;color:{{ $cfg['color'] }}">
            {{ number_format($count) }}
          </div>
          <div style="font-size:11px;font-weight:600;color:#555">{{ $estado }}</div>
          <div style="font-size:10px;color:#aaa">{{ $pct }}% del total</div>
          <div class="bar-track" style="margin-top:6px;height:5px">
            <div style="height:100%;width:{{ $pct }}%;background:{{ $cfg['color'] }};
                        border-radius:10px"></div>
          </div>
        </div>
      @endforeach
    </div>

    {{-- Progreso total --}}
    <div style="margin-top:16px;padding:14px;background:#f8f9fa;
                border-radius:10px;border:1px solid var(--borde)">
      <div style="display:flex;justify-content:space-between;margin-bottom:6px">
        <span style="font-size:12px;font-weight:700">Progreso general campaña</span>
        <span style="font-size:14px;font-weight:800;color:var(--rojo)">{{ $cumplimientoPct }}%</span>
      </div>
      <div class="bar-track" style="height:12px">
        <div class="bar-fill {{ $cumplimientoPct >= 70 ? 'ok' : ($cumplimientoPct >= 50 ? 'warn' : 'danger') }}"
             style="width:{{ min($cumplimientoPct, 100) }}%"></div>
      </div>
      <div style="display:flex;justify-content:space-between;
                  font-size:11px;color:#aaa;margin-top:4px">
        <span>0%</span>
        <span>Meta: 100%</span>
      </div>
    </div>
  </div>

  {{-- Actividad reciente --}}
  <div class="card">
    <div class="card-title">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>
      </svg>
      Actividad reciente
    </div>
    @forelse($actividadReciente as $fud)
      @php
        $dotColor = match($fud->estado_fud) {
          'Aprobado'   => 'var(--verde)',
          'Observado'  => 'var(--mostaza)',
          'Rechazado'  => 'var(--rojo)',
          default      => '#888',
        };
        $dotIcon = match($fud->estado_fud) {
          'Aprobado'   => '✓',
          'Observado'  => '!',
          'Rechazado'  => '✕',
          default      => '·',
        };
      @endphp
      <div class="act-item">
        <div class="act-dot"
             style="background:{{ $dotColor }}18;color:{{ $dotColor }};
                    font-weight:800;font-size:14px">
          {{ $dotIcon }}
        </div>
        <div style="flex:1;min-width:0">
          <div style="font-size:12px;font-weight:600;
                      white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
            {{ $fud->cliente->nombre_cliente }}
          </div>
          <div style="font-size:11px;color:#888">
            {{ $fud->tipo_negociacion_desc ?? ucfirst($fud->tipo_negociacion) }}
            · {{ $fud->cliente->saldo_formateado }}
          </div>
          <div style="font-size:10px;color:#aaa;margin-top:1px">
            Por: {{ $fud->aprobadoPor->name ?? $fud->enviador->name }}
            · {{ $fud->updated_at->diffForHumans() }}
          </div>
        </div>
        <span class="badge {{ $fud->badgeClass() }}" style="font-size:10px;flex-shrink:0">
          {{ $fud->estado_fud }}
        </span>
      </div>
    @empty
      <div style="text-align:center;padding:32px;color:#999">
        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#ccc"
             stroke-width="1.5" style="display:block;margin:0 auto 10px">
          <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>
        </svg>
        No hay actividad reciente.
      </div>
    @endforelse

    @if($actividadReciente->count())
      <div style="text-align:center;margin-top:14px;padding-top:12px;
                  border-top:1px solid var(--borde)">
        <a href="{{ route('seguimiento.index') }}"
           style="font-size:12px;color:var(--rojo);font-weight:700">
          Ver historial completo →
        </a>
      </div>
    @endif
  </div>

</div>

@endsection