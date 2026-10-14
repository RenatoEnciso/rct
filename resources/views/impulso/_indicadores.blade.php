{{-- Indicadores del módulo Impulso MyPerú (revisión central) --}}
@use('App\Support\Estilo')
@php $r = $impulso; $maxPend = max(1, $r['pendientes_zona']->max('total') ?? 1); @endphp
<div class="section-title mt-1">Impulso MyPerú · {{ ucfirst($r['mes']->translatedFormat('F Y')) }}</div>
<div class="mx-kpis">
  <div class="cc-kpi"><div class="cc-kpi-ico azul"><i class="bi bi-collection"></i></div>
    <div class="cc-kpi-label">Lotes del mes</div><div class="cc-kpi-valor">{{ $r['lotes_mes'] }}</div>
    <div class="cc-kpi-sub">{{ $r['creditos_mes'] }} créditos solicitados{{ $r['lotes_estados'] ? ' · '.collect($r['lotes_estados'])->map(fn($n, $e) => $n.' '.$e)->implode(' · ') : '' }}</div></div>
  <div class="cc-kpi"><div class="cc-kpi-ico verde"><i class="bi bi-bank"></i></div>
    <div class="cc-kpi-label">Monto honrado acumulado</div><div class="cc-kpi-valor mx-valor">{{ Estilo::solesPe($r['honrado_total']) }}</div>
    <div class="cc-kpi-sub">{{ $r['creditos_honrados'] }} créditos · {{ Estilo::solesPe($r['honrado_mes']) }} este mes</div></div>
  <div class="cc-kpi"><div class="cc-kpi-ico ambar"><i class="bi bi-folder2-open"></i></div>
    <div class="cc-kpi-label">Honrados pendientes de documentación</div><div class="cc-kpi-valor">{{ $r['pendientes_zona']->sum('pendientes') }}</div>
    <div class="cc-kpi-sub">Sin los documentos del Anexo 13</div></div>
  <div class="cc-kpi"><div class="cc-kpi-ico rojo"><i class="bi bi-arrow-left-right"></i></div>
    <div class="cc-kpi-label">Reversiones del mes</div><div class="cc-kpi-valor mx-valor">{{ Estilo::solesPe($r['reversion_mes']) }}</div>
    <div class="cc-kpi-sub">{{ $r['pagos_mes'] }} pagos sincronizados · {{ $r['sin_reversion_mes'] }} sin reversión</div></div>
  <div class="cc-kpi"><div class="cc-kpi-ico {{ $r['por_vencer'] && $r['por_vencer']->diasParaVencer() <= 5 ? 'rojo' : 'verde' }}"><i class="bi bi-alarm"></i></div>
    <div class="cc-kpi-label">Devolución por vencer</div>
    @if($r['por_vencer'])
      <div class="cc-kpi-valor mx-valor">{{ Estilo::solesPe($r['por_vencer']->monto_calculado) }}</div>
      <div class="cc-kpi-sub"><a href="{{ route('impulso.devoluciones.show', $r['por_vencer']) }}">{{ $r['por_vencer']->nombrePeriodo() }}</a> ·
        {{ $r['por_vencer']->diasParaVencer() >= 0 ? 'vence en '.$r['por_vencer']->diasParaVencer().' días ('.$r['por_vencer']->fecha_limite->format('d/m').')' : 'vencida' }}</div>
    @else
      <div class="cc-kpi-valor mx-valor">—</div><div class="cc-kpi-sub">Sin devoluciones pendientes</div>
    @endif</div>
  <div class="cc-kpi"><div class="cc-kpi-ico azul"><i class="bi bi-cash-coin"></i></div>
    <div class="cc-kpi-label">Capital pagado en el mes</div><div class="cc-kpi-valor mx-valor">{{ Estilo::solesPe($r['capital_mes']) }}</div>
    <div class="cc-kpi-sub">Base de la reversión a COFIDE</div></div>
</div>

<div class="grid2 mb-4">
  <div class="card cc-card">
    <div class="card-head">
      <div><div class="card-title"><i class="bi bi-calendar3"></i> Pagos por día del mes</div>
        <p class="card-sub m-0">Capital pagado por los créditos del programa según el día del mes en que pagan (todos los meses).</p></div>
    </div>
    <div class="cc-chart cc-chart-sm"><canvas id="chart-dias" role="img" aria-label="Capital pagado por día del mes"></canvas></div>
  </div>
  <div class="card cc-card">
    <div class="card-title"><i class="bi bi-geo-alt"></i> Pendientes de documentación por zona</div>
    <p class="card-sub">Créditos honrados sin todos los documentos obligatorios del Anexo 13.</p>
    <div class="cc-metas">
      @forelse($r['pendientes_zona'] as $z)
        <div>
          <div class="cc-meta-head"><span class="fw-600">{{ $z['zona'] }}</span><span>{{ $z['pendientes'] }} de {{ $z['total'] }}</span></div>
          <div class="cc-barra"><div class="cc-barra-fill warn" style="--w:{{ round($z['pendientes'] / max(1, $z['total']) * 100) }}%"></div></div>
        </div>
      @empty
        <div class="empty-state"><span>Todos los honrados tienen su documentación completa.</span></div>
      @endforelse
    </div>
  </div>
</div>
<div class="section-title">Revisión documentaria</div>
<script>requestAnimationFrame(function () { document.body.classList.add('cc-listo'); });</script>
