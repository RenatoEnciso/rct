@extends('layouts.app')
@section('title', 'Pagos sincronizados')

@use('App\Support\Estilo')

@section('content')
@include('gobierno._modulo')
@php
  $badge = ['Sincronizado' => 'badge-gray', 'Agregado' => 'badge-info', 'Incluido en devolución' => 'badge-pending', 'Devuelto' => 'badge-ok', 'Sin reversión' => 'badge-gray'];
  $nombreMes = fn($m) => ucfirst(\Illuminate\Support\Carbon::parse($m.'-01')->translatedFormat('F Y'));
@endphp
<div class="cc">
  <div class="page-head cc-head">
    <div>
      <div class="page-eyebrow">Impulso MyPerú · Etapa 5</div>
      <h2>Pagos sincronizados desde el core</h2>
      <p>Llegan solos cada 30 minutos: reversión = capital × factor de cobertura si el crédito estaba honrado a la fecha del pago.</p>
    </div>
    <div class="page-head-actions">
      @if(Route::has('impulso.pagos.simular') && \App\Http\Controllers\Impulso\PagosController::esAdministrador(auth()->user()))
        <button type="button" class="btn btn-primary" id="btn-simular" data-url="{{ route('impulso.pagos.simular') }}"><i class="bi bi-lightning-charge"></i> Simular pago del core</button>
      @endif
    </div>
  </div>

  <div class="pg-solo-lectura cc-aparece" style="--i:0">
    <i class="bi bi-lock"></i>
    <span><strong>Solo lectura.</strong> RCT no registra pagos a mano: los lee del core en cada sincronización
      @if($ultima) (última sincronización: {{ $ultima->finalizado_en->format('d/m/Y H:i') }})@endif.</span>
  </div>

  <div class="cc-kpis pr-kpis" style="grid-template-columns:repeat(5,minmax(0,1fr))">
    <div class="cc-kpi cc-aparece" style="--i:1"><div class="cc-kpi-ico azul"><i class="bi bi-receipt"></i></div>
      <div class="cc-kpi-label">Pagos {{ $mes ? 'de '.mb_strtolower($nombreMes($mes)) : 'encontrados' }}</div><div class="cc-kpi-valor">{{ $kpis['pagos'] }}</div>
      <div class="cc-kpi-sub">Monto total {{ Estilo::solesPe($kpis['monto']) }}</div></div>
    <div class="cc-kpi cc-aparece" style="--i:2"><div class="cc-kpi-ico verde"><i class="bi bi-cash-coin"></i></div>
      <div class="cc-kpi-label">Capital pagado</div><div class="cc-kpi-valor mx-valor">{{ Estilo::solesPe($kpis['capital']) }}</div><div class="cc-kpi-sub">Base de la reversión</div></div>
    <div class="cc-kpi cc-aparece" style="--i:3"><div class="cc-kpi-ico rojo"><i class="bi bi-arrow-left-right"></i></div>
      <div class="cc-kpi-label">Reversión a COFIDE</div><div class="cc-kpi-valor mx-valor">{{ Estilo::solesPe($kpis['reversion']) }}</div><div class="cc-kpi-sub">Agregada (pari passu)</div></div>
    <div class="cc-kpi cc-aparece" style="--i:4"><div class="cc-kpi-ico ambar"><i class="bi bi-dash-circle"></i></div>
      <div class="cc-kpi-label">Pagos sin reversión</div><div class="cc-kpi-valor">{{ $kpis['sin'] }}</div><div class="cc-kpi-sub">Crédito no honrado a la fecha</div></div>
    <div class="cc-kpi cc-aparece" style="--i:5"><div class="cc-kpi-ico verde"><i class="bi bi-arrow-repeat"></i></div>
      <div class="cc-kpi-label">Última sincronización</div><div class="cc-kpi-valor mx-valor">{{ $ultima?->finalizado_en->format('H:i') ?? '—' }}</div>
      <div class="cc-kpi-sub">{{ $ultima ? $ultima->finalizado_en->format('d/m/Y').' · automática cada 30 min' : 'Sin sincronizaciones' }}</div></div>
  </div>

  <form method="GET" class="card cc-card mx-filtros">
    <div class="field"><label for="mes">Mes del pago</label>
      <select id="mes" name="mes" onchange="this.form.submit()"><option value="todos" @selected(!$mes)>Todos</option>
        @foreach($meses as $m)<option value="{{ $m }}" @selected($mes === $m)>{{ $nombreMes($m) }}</option>@endforeach</select></div>
    <div class="field"><label for="estado">Estado</label>
      <select id="estado" name="estado" onchange="this.form.submit()"><option value="">Todos</option>
        @foreach(\App\Models\PagoPrograma::ESTADOS as $e)<option value="{{ $e }}" @selected(request('estado') === $e)>{{ $e }}</option>@endforeach</select></div>
    <div class="field"><label for="lote_id">Lote</label>
      <select id="lote_id" name="lote_id" onchange="this.form.submit()"><option value="">Todos</option>
        @foreach($lotes as $l)<option value="{{ $l->id }}" @selected(request('lote_id') == $l->id)>{{ $l->nombre() }}</option>@endforeach</select></div>
    <div class="field"><label for="zona_id">Zona</label>
      <select id="zona_id" name="zona_id" onchange="this.form.submit()"><option value="">Todas</option>
        @foreach($zonas as $z)<option value="{{ $z->id }}" @selected(request('zona_id') == $z->id)>{{ $z->nombre }}</option>@endforeach</select></div>
    <div class="field"><label for="canal">Canal</label>
      <select id="canal" name="canal" onchange="this.form.submit()"><option value="">Todos</option>
        @foreach($canales as $c)<option value="{{ $c }}" @selected(request('canal') === $c)>{{ $c }}</option>@endforeach</select></div>
    <div class="field"><label for="q">Buscar</label><input id="q" name="q" value="{{ request('q') }}" placeholder="Cliente, documento o crédito"></div>
    <div class="d-flex gap-2"><button class="btn btn-secondary"><i class="bi bi-search"></i> Filtrar</button><a href="{{ route('impulso.pagos') }}" class="btn btn-ghost">Limpiar</a></div>
  </form>

  <div class="card cc-card card-flush">
    <div class="table-wrap">
      <table>
        <thead><tr><th>Fecha del pago</th><th>Cliente</th><th>Lote · honramiento</th><th>Tienda · canal</th><th class="num">Monto</th><th class="num">Capital</th>
          <th class="num">Interés + mora</th><th class="num">Cobertura</th><th class="num">Reversión</th><th>Estado</th><th>Sincronizado</th></tr></thead>
        <tbody>
          @forelse($pagos as $p)
            <tr>
              <td class="nowrap">{{ $p->fecha_pago->format('d/m/Y') }}<div class="cell-sub">{{ $p->fecha_pago->format('H:i') }} · {{ $p->id_core }}</div></td>
              <td>@if($p->expediente)<a href="{{ route('gobierno.show', $p->expediente) }}" class="cell-main">{{ $p->expediente->cliente }}</a>@else<span class="cell-main">—</span>@endif
                <div class="cell-sub">{{ $p->credito_sicmact }}</div></td>
              <td class="nowrap">{{ $p->expediente?->lote?->nombre() ?? '—' }}
                <div class="cell-sub">{{ $p->expediente?->fecha_honramiento ? 'Honrado el '.$p->expediente->fecha_honramiento->format('d/m/Y') : 'No honrado' }}</div></td>
              <td>{{ $p->tienda->nombre ?? '—' }}<div class="cell-sub">{{ $p->canal }}</div></td>
              <td class="num">{{ Estilo::solesPe($p->monto_total) }}</td>
              <td class="num fw-600">{{ Estilo::solesPe($p->capital) }}</td>
              <td class="num">{{ Estilo::solesPe((float) $p->interes + (float) $p->mora) }}</td>
              <td class="num">{{ $p->factor_cobertura ? number_format($p->factor_cobertura * 100, 2, ',', '').'%' : '—' }}</td>
              <td class="num fw-600 {{ $p->honrado ? 'text-rojo' : 'text-gris' }}">{{ Estilo::solesPe($p->reversion) }}</td>
              <td><span class="badge {{ $badge[$p->estado] ?? 'badge-gray' }}">{{ $p->estado }}</span>@if($p->motivo)<div class="cell-sub">{{ $p->motivo }}</div>@endif</td>
              <td class="nowrap fs-12 text-gris">{{ $p->sincronizado_en->format('d/m/Y H:i') }}</td>
            </tr>
          @empty
            <tr><td colspan="11"><div class="empty-state"><i class="bi bi-cash-coin"></i><strong>Sin pagos</strong><span>No hay pagos del programa con los filtros aplicados.</span></div></td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
    <div class="rct-pager">{{ $pagos->links() }}</div>
  </div>
</div>
@endsection
