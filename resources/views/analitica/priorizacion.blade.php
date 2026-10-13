@extends('layouts.app')
@section('title', 'Priorización de cartera')

@use('App\Support\Estilo')
@use('App\Models\PriorizacionCartera')

@section('content')
@php
  $zonaNombre = $zonaId ? optional($zonas->firstWhere('id', $zonaId))->nombre : null;
  $filtro = fn($id) => route('priorizacion.index', array_filter(['zona_id' => $id]));
@endphp
<div class="cc">
  <div class="page-head cc-head">
    <div>
      <div class="page-eyebrow">Analítica · Modelo de priorización</div>
      <h2>Priorización de cartera</h2>
      <p>Cartera pendiente de gestión en la campaña{{ $zonaNombre ? ' · Zona '.$zonaNombre : ' · Todas las zonas' }}, por segmento de prioridad.</p>
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

  <div class="cc-kpis pr-kpis">
    <div class="cc-kpi cc-aparece" style="--i:0">
      <div class="cc-kpi-ico rojo"><i class="bi bi-bullseye"></i></div>
      <div class="cc-kpi-label">Créditos pendientes de prioridad alta</div>
      <div class="cc-kpi-valor" data-contar="{{ $resumen['alta'] }}" data-formato="num">{{ $resumen['alta'] }}</div>
      <div class="cc-kpi-sub">De {{ number_format($resumen['pendientes'], 0, '.', ' ') }} créditos pendientes</div>
    </div>
    <div class="cc-kpi cc-aparece" style="--i:1">
      <div class="cc-kpi-ico azul"><i class="bi bi-cash-stack"></i></div>
      <div class="cc-kpi-label">Recupero esperado de la cartera prioritaria</div>
      <div class="cc-kpi-valor" data-contar="{{ round($resumen['esperado']) }}" data-formato="soles">{{ Estilo::solesEntero($resumen['esperado']) }}</div>
      <div class="cc-kpi-sub">Prioridad alta y media pendientes</div>
    </div>
    <div class="cc-kpi cc-aparece" style="--i:2">
      <div class="cc-kpi-ico verde"><i class="bi bi-shield-check"></i></div>
      <div class="cc-kpi-label">Provisión liberable</div>
      <div class="cc-kpi-valor" data-contar="{{ round($resumen['provision']) }}" data-formato="soles">{{ Estilo::solesEntero($resumen['provision']) }}</div>
      <div class="cc-kpi-sub">Si se normaliza la cartera prioritaria</div>
    </div>
    <div class="cc-kpi cc-aparece" style="--i:3">
      <div class="cc-kpi-ico ambar"><i class="bi bi-check2-circle"></i></div>
      <div class="cc-kpi-label">Cartera prioritaria ya gestionada</div>
      <div class="cc-kpi-valor" data-contar="{{ $resumen['gestionada'] }}" data-formato="pct">{{ number_format($resumen['gestionada'], 1) }}%</div>
      <div class="cc-kpi-sub">{{ $resumen['gestionados'] }} de {{ $resumen['prioritaria'] }} créditos con propuesta en campaña</div>
    </div>
  </div>

  <div class="card cc-card cc-aparece" style="--i:4;margin-bottom:18px">
    <div class="card-head">
      <div>
        <div class="card-title"><i class="bi bi-grid-3x3"></i> Mapa de calor: {{ $porZona ? 'zona' : 'tienda' }} × segmento</div>
        <p class="card-sub" style="margin:0">Créditos pendientes y recupero esperado. Mayor intensidad = mayor recupero esperado.</p>
      </div>
      <div class="pr-escala" aria-hidden="true"><span>Menor</span><i></i><span>Mayor</span></div>
    </div>
    <div class="table-wrap">
      <table class="pr-calor">
        <thead>
          <tr>
            <th>{{ $porZona ? 'Zona' : 'Tienda' }}</th>
            <th class="center">Prioridad alta</th><th class="center">Prioridad media</th><th class="center">Prioridad baja</th>
            <th class="num">Recupero esperado</th><th style="min-width:200px">Cartera prioritaria gestionada</th>
          </tr>
        </thead>
        <tbody>
          @foreach($filas as $f)
            <tr>
              <td class="cell-main">{{ $f['nombre'] }}</td>
              @foreach(['Alta', 'Media', 'Baja'] as $s)
                @php
                  $c = $f['celdas'][$s];
                  $a = $c['n'] ? 0.07 + 0.83 * pow($c['esperado'] / $maxCelda, 0.6) : 0;
                @endphp
                <td class="pr-celda-td">
                  <div class="pr-celda {{ $a > 0.5 ? 'oscura' : '' }}" style="--a:{{ round($a, 3) }}"
                       title="{{ $f['nombre'] }} · prioridad {{ strtolower($s) }}: {{ $c['n'] }} créditos, {{ Estilo::solesEntero($c['esperado']) }} de recupero esperado">
                    <strong>{{ $c['n'] }}</strong>
                    <span>{{ $c['n'] ? Estilo::solesCorto($c['esperado']) : '—' }}</span>
                  </div>
                </td>
              @endforeach
              <td class="num fw-600">{{ Estilo::solesEntero($f['esperado']) }}</td>
              <td>
                <div class="bar-row">
                  <div class="cc-barra cc-barra-sm" style="flex:1"><div class="cc-barra-fill azul" style="--w:{{ $f['gestionada'] }}%"></div></div>
                  <span class="fs-12 fw-600 num" style="min-width:48px;text-align:right">{{ number_format($f['gestionada'], 1) }}%</span>
                </div>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>

  <div class="card cc-card card-flush cc-aparece" style="--i:5">
    <div class="card-head">
      <div class="card-title"><i class="bi bi-list-ol"></i> Créditos de prioridad alta pendientes</div>
      <span class="cell-sub">Los 12 de mayor puntaje</span>
    </div>
    <div class="table-wrap">
      <table>
        <thead><tr><th class="center">Puntaje</th><th>Cliente</th><th>Tienda</th><th class="num">Saldo capital</th><th class="num">Prob. de pago</th><th>Motivos</th><th>Acción sugerida</th><th class="num">Recupero esperado</th></tr></thead>
        <tbody>
          @foreach($casos as $p)
            <tr>
              <td class="center"><span class="pr-score alta">{{ $p->score }}</span></td>
              <td><div class="cell-main">{{ $p->cliente->nombre_cliente }}</div><div class="cell-sub">{{ $p->cliente->credito_sicmact }} · {{ $p->cliente->dias_atraso }} días</div></td>
              <td>{{ $p->cliente->tienda->nombre ?? '—' }}</td>
              <td class="num">{{ Estilo::solesEntero($p->cliente->saldo_capital) }}</td>
              <td class="num">{{ number_format($p->probabilidad_pago, 1) }}%</td>
              <td><div class="pr-chips compactos">@foreach(array_slice($p->motivos, 0, 2) as $m)<span class="pr-chip">{{ $m }}</span>@endforeach</div></td>
              <td class="nowrap"><i class="bi {{ PriorizacionCartera::iconoAccion($p->accion_sugerida) }} text-gris"></i> {{ $p->accion_sugerida }}</td>
              <td class="num fw-600">{{ Estilo::solesEntero($p->recupero_esperado) }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
    <div style="height:8px"></div>
  </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('assets/js/rct-analitica.js') }}?v={{ filemtime(public_path('assets/js/rct-analitica.js')) }}"></script>
<script>RCTAnalitica.contadores(); requestAnimationFrame(function () { document.body.classList.add('cc-listo'); });</script>
@endpush
