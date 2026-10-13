@extends('layouts.app')
@section('title', 'Centro de control')

@use('App\Support\Estilo')

@section('content')
@php
  $pct = fn($a, $b) => $b ? round($a / $b * 100, 1) : 0;
  $gobPct = $pct($kpis['gob_completos'], $kpis['gob_total']);
  $filtros = fn(array $cambios) => route('centro-control', array_filter(array_merge(['periodo' => $periodo, 'zona_id' => $zonaFija ? null : $zonaId], $cambios)));
  $zonaNombre = $zonaId ? optional($zonas->firstWhere('id', $zonaId))->nombre : null;
@endphp

<div class="cc">
  <div class="page-head cc-head">
    <div>
      <div class="page-eyebrow">Analítica</div>
      <h2>Centro de control</h2>
      <p>{{ $campana ? 'Campaña «'.$campana->nombre.'»' : 'Recuperaciones' }}{{ $zonaNombre ? ' · Zona '.$zonaNombre : ' · Todas las zonas' }} · Datos al {{ $hoy->format('d/m/Y') }}</p>
    </div>
    <div class="page-head-actions">
      <div class="cc-segmento" role="group" aria-label="Periodo">
        <a href="{{ $filtros(['periodo' => 'anio']) }}" class="{{ $periodo === 'anio' ? 'active' : '' }}">Año {{ $hoy->year }}</a>
        <a href="{{ $filtros(['periodo' => 'campana']) }}" class="{{ $periodo === 'campana' ? 'active' : '' }}">Campaña actual</a>
      </div>
      @unless($zonaFija)
        <select class="input-rct cc-select" aria-label="Zona" onchange="location.href=this.value">
          <option value="{{ $filtros(['zona_id' => null]) }}">Todas las zonas</option>
          @foreach($zonas as $z)
            <option value="{{ $filtros(['zona_id' => $z->id]) }}" @selected($zonaId == $z->id)>Zona {{ $z->nombre }}</option>
          @endforeach
        </select>
      @endunless
      <button type="button" class="btn btn-secondary" data-modo-presentacion><i class="bi bi-arrows-fullscreen"></i> Modo presentación</button>
    </div>
  </div>

  {{-- Indicadores principales --}}
  <div class="cc-kpis">
    <div class="cc-kpi cc-aparece" style="--i:0">
      <div class="cc-kpi-ico azul"><i class="bi bi-cash-stack"></i></div>
      <div class="cc-kpi-label">Monto recuperado</div>
      <div class="cc-kpi-valor" data-contar="{{ round($kpis['recuperado']) }}" data-formato="soles">{{ Estilo::solesEntero($kpis['recuperado']) }}</div>
      <div class="cc-kpi-sub">
        @if($kpis['variacion_campana'] !== null)
          <span class="cc-delta {{ $kpis['variacion_campana'] >= 0 ? 'up' : 'down' }}"><i class="bi {{ $kpis['variacion_campana'] >= 0 ? 'bi-arrow-up-right' : 'bi-arrow-down-right' }}"></i> {{ number_format(abs($kpis['variacion_campana']), 1) }}%</span>
          promedio mensual en campaña
        @else
          {{ $kpis['beneficios'] }} beneficios pagados
        @endif
      </div>
    </div>
    <div class="cc-kpi cc-aparece" style="--i:1">
      <div class="cc-kpi-ico verde"><i class="bi bi-shield-check"></i></div>
      <div class="cc-kpi-label">Provisión liberada</div>
      <div class="cc-kpi-valor" data-contar="{{ round($kpis['provision']) }}" data-formato="soles">{{ Estilo::solesEntero($kpis['provision']) }}</div>
      <div class="cc-kpi-sub">Por créditos normalizados y cancelados</div>
    </div>
    <div class="cc-kpi cc-aparece" style="--i:2">
      <div class="cc-kpi-ico ambar"><i class="bi bi-percent"></i></div>
      <div class="cc-kpi-label">Monto condonado</div>
      <div class="cc-kpi-valor" data-contar="{{ round($kpis['condonado']) }}" data-formato="soles">{{ Estilo::solesEntero($kpis['condonado']) }}</div>
      <div class="cc-kpi-sub">{{ number_format($kpis['pct_condonado'], 1) }}% de la deuda negociada</div>
    </div>
    <div class="cc-kpi cc-aparece" style="--i:3">
      <div class="cc-kpi-ico rojo"><i class="bi bi-graph-up-arrow"></i></div>
      <div class="cc-kpi-label">Recuperado por cada S/ 1 condonado</div>
      <div class="cc-kpi-valor" data-contar="{{ $kpis['ratio'] }}" data-formato="ratio">S/ {{ number_format($kpis['ratio'], 2) }}</div>
      <div class="cc-kpi-sub">Eficiencia del beneficio otorgado</div>
    </div>
    <div class="cc-kpi cc-aparece" style="--i:4">
      <div class="cc-kpi-ico azul"><i class="bi bi-person-check"></i></div>
      <div class="cc-kpi-label">Clientes normalizados</div>
      <div class="cc-kpi-valor" data-contar="{{ $kpis['normalizados'] }}" data-formato="num">{{ number_format($kpis['normalizados'], 0, '.', ' ') }}</div>
      <div class="cc-kpi-sub">De {{ $kpis['beneficios'] }} beneficios pagados</div>
    </div>
    <div class="cc-kpi cc-aparece" style="--i:5">
      <div class="cc-kpi-ico verde"><i class="bi bi-bank"></i></div>
      <div class="cc-kpi-label">Expedientes de programa completos</div>
      <div class="cc-kpi-valor" data-contar="{{ $gobPct }}" data-formato="pct">{{ number_format($gobPct, 1) }}%</div>
      <div class="cc-kpi-sub">{{ $kpis['gob_completos'] }} de {{ $kpis['gob_total'] }} con documentos COFIDE</div>
    </div>
  </div>

  <div class="cc-fila cc-fila-2-1">
    {{-- Tendencia --}}
    <div class="card cc-card cc-aparece" style="--i:6">
      <div class="card-head">
        <div>
          <div class="card-title"><i class="bi bi-bar-chart-line"></i> Recuperado y condonado por mes</div>
          <p class="card-sub" style="margin:0">Frente a la meta mensual de las zonas{{ collect($tendencia)->last()['parcial'] ?? false ? ' · '.collect($tendencia)->last()['mes'].' al '.$hoy->format('d/m') : '' }}</p>
        </div>
        <button type="button" class="btn btn-ghost btn-sm" data-ver-tabla="tabla-tendencia"><i class="bi bi-table"></i> Ver tabla</button>
      </div>
      <div class="cc-chart"><canvas id="chart-tendencia" role="img" aria-label="Recuperado, condonado y meta por mes"></canvas></div>
      <div class="table-wrap" id="tabla-tendencia" hidden>
        <table>
          <thead><tr><th>Mes</th><th class="num">Recuperado</th><th class="num">Condonado</th><th class="num">Meta</th></tr></thead>
          <tbody>
            @foreach($tendencia as $t)
              <tr><td>{{ $t['mes'] }}</td><td class="num">{{ Estilo::solesEntero($t['recuperado']) }}</td><td class="num">{{ Estilo::solesEntero($t['condonado']) }}</td><td class="num">{{ Estilo::solesEntero($t['meta']) }}</td></tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>

    {{-- Avance vs meta --}}
    <div class="card cc-card cc-aparece" style="--i:7">
      <div class="card-title"><i class="bi bi-flag"></i> Avance de la campaña frente a la meta</div>
      <p class="card-sub">
        @if($zonaId) Zona {{ $zonaNombre }} · recuperado y meta de cada mes de campaña.
        @else Recuperado desde el {{ optional($campana?->fecha_inicio ? \Illuminate\Support\Carbon::parse($campana->fecha_inicio) : null)->format('d/m/Y') }} y meta acumulada a la fecha. @endif
      </p>
      <div class="cc-metas">
        @foreach($metas as $m)
          @php
            $clase = $m['avance'] >= 100 ? 'ok' : ($m['avance'] >= 80 ? 'warn' : 'danger');
            $escala = max(100, $m['avance']);   // si supera la meta, la barra se reescala y la marca de meta queda antes del final
          @endphp
          <div class="cc-meta">
            <div class="cc-meta-head">
              <span class="fw-600">{{ $m['zona'] }}</span>
              <span class="cc-meta-pct {{ $clase }}" data-contar="{{ $m['avance'] }}" data-formato="pct">{{ number_format($m['avance'], 1) }}%</span>
            </div>
            <div class="cc-barra" title="Recuperado {{ Estilo::solesEntero($m['recuperado']) }} de {{ Estilo::solesEntero($m['meta']) }}">
              <div class="cc-barra-fill {{ $clase }}" style="--w:{{ round($m['avance'] / $escala * 100, 1) }}%"></div>
              <div class="cc-barra-meta" style="left:{{ round(100 / $escala * 100, 1) }}%" title="Meta"></div>
            </div>
            <div class="cc-meta-sub">{{ Estilo::solesEntero($m['recuperado']) }} de {{ Estilo::solesEntero($m['meta']) }}</div>
          </div>
        @endforeach
      </div>
    </div>
  </div>

  {{-- Simulador de impacto --}}
  <div class="card cc-card cc-sim cc-aparece" style="--i:8">
    <div class="cc-sim-grid">
      <div class="cc-sim-panel">
        <div class="card-title"><i class="bi bi-sliders"></i> Simulador de impacto</div>
        <p class="cc-sim-pregunta">Si gestionamos el <strong id="sim-pct">30%</strong> de la cartera pendiente, en el orden del modelo de priorización…</p>
        <input type="range" id="sim-rango" class="cc-rango" min="5" max="100" step="5" value="30" aria-label="Porcentaje de cartera gestionada">
        <div class="cc-rango-escala"><span>5%</span><span>50%</span><span>100%</span></div>

        <div class="cc-sim-res">
          <div>
            <div class="cc-sim-label">Recupero esperado</div>
            <div class="cc-sim-valor" id="sim-recupero">—</div>
          </div>
          <div>
            <div class="cc-sim-label">Provisión que se liberaría</div>
            <div class="cc-sim-valor verde" id="sim-provision">—</div>
          </div>
        </div>
        <div class="cc-sim-ventaja">
          <i class="bi bi-lightning-charge"></i>
          <div><strong id="sim-ventaja">—</strong> frente a gestionar en el orden de la base de aptos<br><span id="sim-creditos" class="text-gris fs-12">—</span></div>
        </div>
      </div>
      <div class="cc-sim-chart">
        <div class="cc-chart cc-chart-lg"><canvas id="chart-simulador" role="img" aria-label="Recupero acumulado según el orden de gestión"></canvas></div>
      </div>
    </div>
  </div>

  <div class="cc-fila cc-fila-2-1">
    {{-- Analítica --}}
    <div class="card cc-card cc-aparece" style="--i:9">
      <div class="card-title"><i class="bi bi-cpu"></i> Analítica de cartera</div>
      <div class="cc-analitica">
        <div class="cc-tile">
          <div class="cc-tile-label">Cartera prioritaria pendiente</div>
          <div class="cc-tile-valor" data-contar="{{ $analitica['prioritaria'] }}" data-formato="num">{{ $analitica['prioritaria'] }}</div>
          <div class="cc-tile-sub">créditos · <strong>{{ $analitica['prioritaria_alta'] }}</strong> de prioridad alta</div>
          <div class="cc-tile-pie">{{ Estilo::solesEntero($analitica['recuperable']) }} de recupero esperado · <a href="{{ route('priorizacion.index', array_filter(['zona_id' => $zonaFija ? null : $zonaId])) }}">Ver priorización</a></div>
        </div>
        <div class="cc-tile">
          <div class="cc-tile-label">Recuperación sostenida a 90 días</div>
          @if($analitica['sostenida'] !== null)
            <div class="cc-tile-valor" data-contar="{{ $analitica['sostenida'] }}" data-formato="pct">{{ number_format($analitica['sostenida'], 1) }}%</div>
            <div class="cc-tile-sub">de clientes al día tras el beneficio</div>
            <div class="cc-tile-pie">{{ $analitica['seguimiento'] }} beneficios con 90 días · <a href="{{ route('recaida.index', array_filter(['zona_id' => $zonaFija ? null : $zonaId])) }}">Ver análisis de recaída</a></div>
          @else
            <div class="cc-tile-valor text-gris">En curso</div>
            <div class="cc-tile-sub">Los beneficios aún no cumplen 90 días</div>
          @endif
        </div>
        <div class="cc-tile">
          <div class="cc-tile-label">Alertas de control abiertas</div>
          <div class="cc-tile-valor" data-contar="{{ array_sum($analitica['alertas']) }}" data-formato="num">{{ array_sum($analitica['alertas']) }}</div>
          <div class="cc-sev">
            <span class="cc-sev-item alta"><i class="bi bi-exclamation-octagon"></i> {{ $analitica['alertas']['Alta'] }} alta</span>
            <span class="cc-sev-item media"><i class="bi bi-exclamation-triangle"></i> {{ $analitica['alertas']['Media'] }} media</span>
            <span class="cc-sev-item baja"><i class="bi bi-info-circle"></i> {{ $analitica['alertas']['Baja'] }} baja</span>
          </div>
        </div>
      </div>

      @if($analitica['acciones'])
        @php
          $totalAcc = array_sum($analitica['acciones']);
          $iconos = ['Llamar' => 'bi-telephone', 'Visitar' => 'bi-geo-alt', 'Ofrecer cancelación' => 'bi-cash-coin', 'Ofrecer pago de cuotas' => 'bi-calendar-check'];
        @endphp
        <div class="cc-acciones">
          <div class="cc-tile-label">Acciones sugeridas para la cartera prioritaria</div>
          <div class="cc-acciones-grid">
            @foreach($analitica['acciones'] as $accion => $n)
              <div class="cc-accion">
                <i class="bi {{ $iconos[$accion] ?? 'bi-arrow-right-circle' }}"></i>
                <div class="cc-accion-body">
                  <div class="cc-rank-head"><span>{{ $accion }}</span><span class="num fw-600">{{ $n }}</span></div>
                  <div class="cc-barra cc-barra-sm"><div class="cc-barra-fill azul" style="--w:{{ round($n / $totalAcc * 100) }}%"></div></div>
                </div>
              </div>
            @endforeach
          </div>
        </div>
      @endif
    </div>

    {{-- Top tiendas --}}
    <div class="card cc-card cc-aparece" style="--i:10">
      <div class="card-title"><i class="bi bi-trophy"></i> Tiendas con mayor recupero</div>
      @php $maxT = $tiendas->max('recuperado') ?: 1; @endphp
      <div class="cc-ranking">
        @forelse($tiendas as $i => $t)
          <div class="cc-rank">
            <span class="cc-rank-n">{{ $i + 1 }}</span>
            <div class="cc-rank-body">
              <div class="cc-rank-head"><span class="fw-600">{{ $t['tienda'] }}</span><span class="num fw-600">{{ Estilo::solesEntero($t['recuperado']) }}</span></div>
              <div class="cc-barra cc-barra-sm"><div class="cc-barra-fill azul" style="--w:{{ round($t['recuperado'] / $maxT * 100) }}%"></div></div>
              <div class="cc-meta-sub">Zona {{ $t['zona'] }} · {{ $t['beneficios'] }} beneficios</div>
            </div>
          </div>
        @empty
          <div class="empty-state"><span>Sin recuperos en el periodo.</span></div>
        @endforelse
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('vendor/chartjs/chart.umd.min.js') }}"></script>
<script src="{{ asset('assets/js/rct-analitica.js') }}?v={{ filemtime(public_path('assets/js/rct-analitica.js')) }}"></script>
<script>
RCTAnalitica.centroControl({
  tendencia: {{ Js::from($tendencia) }},
  simulador: {{ Js::from($simulador) }}
});
</script>
@endpush
