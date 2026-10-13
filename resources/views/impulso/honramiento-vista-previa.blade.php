@extends('layouts.app')
@section('title', 'Reporte de honramiento')

@use('App\Support\Estilo')

@section('content')
@include('gobierno._modulo')
@php
  $filas = collect($a['filas']);
  $validas = $filas->filter(fn($r) => !$r['errores']);
  $honradas = $validas->where('estado', 'Honrado');
  $monto = \App\Services\Impulso\Calculos::sumar($honradas->pluck('monto'));
@endphp
<div class="cc">
  <div class="page-head cc-head">
    <div>
      <div class="page-eyebrow"><a href="{{ route('impulso.lotes.show', $lote) }}">{{ $lote->codigo }}</a> / Reporte de honramiento</div>
      <h2>Revisión del reporte de COFIDE</h2>
      <p>{{ $archivo }} · Nada se guarda hasta confirmar.</p>
    </div>
    <div class="page-head-actions">
      <a href="{{ route('impulso.lotes.show', $lote) }}" class="btn btn-secondary">Cancelar</a>
      <form method="POST" action="{{ route('impulso.honramiento.confirmar', $lote) }}">@csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <button class="btn btn-primary" @disabled($validas->isEmpty())><i class="bi bi-check2-circle"></i> Confirmar honramiento</button>
      </form>
    </div>
  </div>

  <div class="cc-kpis pr-kpis">
    <div class="cc-kpi"><div class="cc-kpi-ico verde"><i class="bi bi-bank"></i></div><div class="cc-kpi-label">Créditos honrados</div>
      <div class="cc-kpi-valor">{{ $honradas->count() }}</div><div class="cc-kpi-sub">De {{ $lote->registros_rct }} solicitados</div></div>
    <div class="cc-kpi"><div class="cc-kpi-ico azul"><i class="bi bi-cash-stack"></i></div><div class="cc-kpi-label">Monto a honrar</div>
      <div class="cc-kpi-valor">{{ Estilo::solesPe($monto) }}</div><div class="cc-kpi-sub">Según COFIDE</div></div>
    <div class="cc-kpi"><div class="cc-kpi-ico ambar"><i class="bi bi-exclamation-triangle"></i></div><div class="cc-kpi-label">Observados o no incluidos</div>
      <div class="cc-kpi-valor">{{ $validas->where('estado', 'No honrado')->count() + count($a['faltantes']) }}</div><div class="cc-kpi-sub">Quedarán como no honrados</div></div>
    <div class="cc-kpi"><div class="cc-kpi-ico rojo"><i class="bi bi-x-octagon"></i></div><div class="cc-kpi-label">Filas con error</div>
      <div class="cc-kpi-valor">{{ $filas->filter(fn($r) => $r['errores'])->count() }}</div><div class="cc-kpi-sub">No se importarán</div></div>
  </div>

  <div class="card cc-card card-flush">
    <div class="table-wrap">
      <table data-rct-table data-page-size="20" data-search-placeholder="Buscar crédito o cliente">
        <thead><tr><th>Fila</th><th>Crédito</th><th>Cliente</th><th class="num">Saldo insoluto</th><th class="num">Interés</th><th class="num">Factor</th>
          <th class="num">Monto a honrar</th><th>Fecha</th><th>Resultado</th></tr></thead>
        <tbody>
          @foreach($filas as $r)
            <tr>
              <td class="num">{{ $r['fila'] }}</td><td class="nowrap">{{ $r['credito'] }}</td><td class="cell-main">{{ $r['cliente'] }}</td>
              <td class="num">{{ $r['saldo'] !== null ? Estilo::solesPe($r['saldo']) : '—' }}</td>
              <td class="num">{{ Estilo::solesPe($r['interes']) }}</td>
              <td class="num">{{ $r['factor'] !== null ? number_format($r['factor'], 4, ',', '') : '—' }}</td>
              <td class="num fw-600">{{ $r['monto'] !== null ? Estilo::solesPe($r['monto']) : '—' }}</td>
              <td>{{ $r['fecha'] ? \Illuminate\Support\Carbon::parse($r['fecha'])->format('d/m/Y') : '—' }}</td>
              <td>
                @if($r['errores'])<span class="badge badge-danger">Error</span><div class="cell-sub text-rojo">{{ implode(' ', $r['errores']) }}</div>
                @elseif($r['estado'] === 'No honrado')<span class="badge badge-pending">Observado</span><div class="cell-sub">{{ $r['observacion'] }}</div>
                @else<span class="badge badge-ok">Honrado</span>@foreach($r['avisos'] as $av)<div class="cell-sub text-ambar">{{ $av }}</div>@endforeach
                @endif
              </td>
            </tr>
          @endforeach
          @foreach($a['faltantes'] as $f)
            <tr><td class="num">—</td><td>{{ $f['credito'] }}</td><td class="cell-main">{{ $f['cliente'] }}</td><td colspan="5" class="text-gris">No figura en el reporte de COFIDE</td>
              <td><span class="badge badge-pending">No incluido</span></td></tr>
          @endforeach
        </tbody>
      </table>
    </div>
    <div style="height:12px"></div>
  </div>
</div>
@endsection
