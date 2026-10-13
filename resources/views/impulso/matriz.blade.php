@extends('layouts.app')
@section('title', 'Base matriz Impulso MyPerú')

@use('App\Support\Estilo')

@section('content')
@include('gobierno._modulo')
@php
  $grupo = fn($estado) => collect(\App\Http\Controllers\Impulso\RevisionController::GRUPOS)->search(fn($e) => in_array($estado, $e)) ?: $estado;
  $colorGrupo = ['PENDIENTE' => 'badge-gray', 'INCOMPLETO' => 'badge-pending', 'INCOMPLETO DEFINITIVO' => 'badge-danger', 'COMPLETO' => 'badge-ok'];
@endphp
<div class="cc">
  <div class="page-head cc-head">
    <div>
      <div class="page-eyebrow">Impulso MyPerú · Etapa 4</div>
      <h2>Base matriz IMPULSO MYPERÚ</h2>
      <p>Fuente única de cada crédito honrado: lote y fechas, montos, documentación y recuperos pari passu.</p>
    </div>
    <div class="page-head-actions">
      <a href="{{ route('impulso.matriz.exportar', request()->query()) }}" class="btn btn-primary"><i class="bi bi-file-earmark-excel"></i> Exportar Excel</a>
    </div>
  </div>

  <div class="mx-kpis">
    @foreach([['Créditos honrados', $totales['creditos'], 'num', 'bi-bank', 'azul'], ['Monto honrado', $totales['honrado'], 'pe', 'bi-shield-check', 'verde'],
              ['Capital pagado después del honramiento', $totales['capital'], 'pe', 'bi-cash-coin', 'azul'],
              ['Reversión acumulada', $totales['reversion'], 'pe', 'bi-arrow-left-right', 'rojo'],
              ['Reversión devuelta a COFIDE', $totales['devuelta'], 'pe', 'bi-check2-all', 'verde'],
              ['Reversión pendiente de devolver', $totales['pendiente'], 'pe', 'bi-hourglass-split', 'ambar']] as $i => [$lbl, $v, $fmt, $ico, $color])
      <div class="cc-kpi cc-aparece" style="--i:{{ $i }}">
        <div class="cc-kpi-ico {{ $color }}"><i class="bi {{ $ico }}"></i></div>
        <div class="cc-kpi-label">{{ $lbl }}</div>
        <div class="cc-kpi-valor mx-valor">{{ $fmt === 'pe' ? Estilo::solesPe($v) : $v }}</div>
      </div>
    @endforeach
  </div>

  <form method="GET" class="card cc-card mx-filtros">
    <div class="field"><label for="lote_id">Lote</label>
      <select id="lote_id" name="lote_id" onchange="this.form.submit()"><option value="">Todos</option>
        @foreach($lotes as $l)<option value="{{ $l->id }}" @selected(request('lote_id') == $l->id)>{{ $l->nombre() }}</option>@endforeach</select></div>
    <div class="field"><label for="zona_id">Zona</label>
      <select id="zona_id" name="zona_id" onchange="this.form.submit()"><option value="">Todas</option>
        @foreach($zonas as $z)<option value="{{ $z->id }}" @selected(request('zona_id') == $z->id)>{{ $z->nombre }}</option>@endforeach</select></div>
    <div class="field"><label for="documentacion">Documentación</label>
      <select id="documentacion" name="documentacion" onchange="this.form.submit()"><option value="">Todas</option>
        @foreach(array_keys(\App\Http\Controllers\Impulso\RevisionController::GRUPOS) as $g)<option value="{{ $g }}" @selected(request('documentacion') === $g)>{{ ucfirst(mb_strtolower($g)) }}</option>@endforeach</select></div>
    <div class="field"><label for="q">Buscar</label><input id="q" name="q" value="{{ request('q') }}" placeholder="Cliente, DNI/RUC o crédito"></div>
    <label class="mx-check"><input type="checkbox" name="recupero" value="1" @checked(request('recupero')) onchange="this.form.submit()"> Solo con recuperos</label>
    <div class="d-flex gap-2"><button class="btn btn-secondary"><i class="bi bi-search"></i> Filtrar</button>
      <a href="{{ route('impulso.matriz') }}" class="btn btn-ghost">Limpiar</a></div>
  </form>

  <div class="card cc-card card-flush">
    <div class="table-wrap">
      <table class="mx-tabla">
        <thead>
          <tr class="mx-grupos"><th colspan="3">Crédito</th><th colspan="2">Lote y fechas</th><th colspan="3">Montos</th><th>Documentación</th><th colspan="4">Recuperos (pari passu)</th></tr>
          <tr><th>Cliente</th><th>N° crédito</th><th>Zona · tienda</th><th>Lote</th><th>Honramiento</th>
            <th class="num">Saldo insoluto</th><th class="num">Cobertura</th><th class="num">Monto honrado</th><th>Estado</th>
            <th class="num">Capital pagado</th><th class="num">Reversión</th><th class="num">Devuelta</th><th class="num">Pendiente</th></tr>
        </thead>
        <tbody>
          @forelse($filas as $e)
            @php $g = $grupo($e->estado); @endphp
            <tr>
              <td><a href="{{ route('gobierno.show', $e) }}" class="cell-main">{{ $e->cliente }}</a><div class="cell-sub">{{ $e->tipo_persona === 'juridica' ? 'RUC' : 'DNI' }} {{ $e->dni }} · ID {{ $e->id_externo }}</div></td>
              <td class="nowrap">{{ $e->credito_sicmact }}</td>
              <td>{{ $e->zona->nombre ?? '—' }}<div class="cell-sub">{{ $e->tienda->nombre ?? '' }}</div></td>
              <td class="nowrap">{{ $e->lote?->nombre() }}<div class="cell-sub">Corte {{ $e->lote?->fecha_corte?->format('d/m/Y') }}</div></td>
              <td class="nowrap">{{ $e->fecha_honramiento?->format('d/m/Y') ?? '—' }}</td>
              <td class="num">{{ Estilo::solesPe($e->saldo_insoluto) }}</td>
              <td class="num">{{ number_format($e->factor_cobertura * 100, 2, ',', '') }}%</td>
              <td class="num fw-600">{{ Estilo::solesPe($e->monto_honrado) }}</td>
              <td><span class="badge {{ $colorGrupo[$g] ?? 'badge-gray' }}">{{ ucfirst(mb_strtolower($g)) }}</span>
                @if($e->faltantes)<div class="cell-sub">{{ $e->faltantes }} faltante{{ $e->faltantes === 1 ? '' : 's' }}</div>@endif</td>
              <td class="num">{{ bccomp($e->pari['capital_pagado'], '0', 2) ? Estilo::solesPe($e->pari['capital_pagado']) : '—' }}</td>
              <td class="num fw-600">{{ bccomp($e->pari['reversion_acumulada'], '0', 2) ? Estilo::solesPe($e->pari['reversion_acumulada']) : '—' }}</td>
              <td class="num text-verde">{{ bccomp($e->pari['devuelta'], '0', 2) ? Estilo::solesPe($e->pari['devuelta']) : '—' }}</td>
              <td class="num {{ bccomp($e->pari['pendiente'], '0', 2) ? 'text-ambar fw-600' : '' }}">{{ bccomp($e->pari['pendiente'], '0', 2) ? Estilo::solesPe($e->pari['pendiente']) : '—' }}</td>
            </tr>
          @empty
            <tr><td colspan="13"><div class="empty-state"><i class="bi bi-table"></i><strong>Sin créditos honrados</strong><span>Los créditos aparecen al importar el reporte de honramiento de COFIDE.</span></div></td></tr>
          @endforelse
        </tbody>
        @if($totales['creditos'])
          <tfoot><tr class="mx-total"><td colspan="5">Total ({{ $totales['creditos'] }} créditos con los filtros aplicados)</td>
            <td class="num">{{ Estilo::solesPe($totales['saldo']) }}</td><td></td><td class="num">{{ Estilo::solesPe($totales['honrado']) }}</td><td></td>
            <td class="num">{{ Estilo::solesPe($totales['capital']) }}</td><td class="num">{{ Estilo::solesPe($totales['reversion']) }}</td>
            <td class="num">{{ Estilo::solesPe($totales['devuelta']) }}</td><td class="num">{{ Estilo::solesPe($totales['pendiente']) }}</td></tr></tfoot>
        @endif
      </table>
    </div>
    <div class="rct-pager">{{ $filas->links() }}</div>
  </div>
</div>
@endsection
