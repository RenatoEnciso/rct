@extends('layouts.app')
@section('title', 'Expedientes de honramiento')

@use('App\Support\Estilo')
@use('App\Services\Honramiento\FormatoExcel')

@push('styles')
<style>
  .tabla-gob td, .tabla-gob th { padding: 8px 9px !important; font-size: 12.5px !important; }
  .tabla-gob .doc-ico { font-size: 14px; display: inline-block; width: 18px; text-align: center; }
  .tabla-gob th.doc-col { text-align: center; min-width: 30px; }
  .tabla-gob th.doc-col.exigido { color: var(--rojo); }
  .estado-chips { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 16px; }
  .estado-chip { display: flex; align-items: center; gap: 8px; padding: 8px 12px; border: 1px solid var(--borde); border-radius: var(--radius-lg); background: var(--superficie); color: var(--tinta); font-size: 13px; }
  .estado-chip:hover { border-color: var(--borde-fuerte); color: var(--tinta); }
  .estado-chip.active { border-color: var(--rojo); box-shadow: 0 0 0 2px var(--rojo-soft); }
  .estado-chip strong { font-size: 16px; font-variant-numeric: tabular-nums; }
</style>
@endpush

@section('content')
@include('gobierno._modulo')
@php $total = $conteo->sum(); @endphp
<div class="page-head">
  <div>
    <h2>Expedientes de honramiento</h2>
    <p>Programas de Gobierno · revisión del checklist documentario exigido por COFIDE
      @if(auth()->user()->esGrupoFud()) · {{ auth()->user()->tienda->nombre ?? '' }}
      @elseif(auth()->user()->nivel() === 1) · Zona {{ auth()->user()->tienda->zona->nombre ?? '' }} @endif</p>
  </div>
  <div class="page-head-actions">
    <a href="{{ route('gobierno.exportar', request()->except('page')) }}" class="btn btn-secondary" title="Exporta los expedientes con los filtros aplicados"><i class="bi bi-file-earmark-excel"></i> Exportar Excel</a>
    @if(auth()->user()->esAprobador())
      <a href="{{ route('gobierno.indicadores') }}" class="btn btn-secondary"><i class="bi bi-bar-chart-line"></i> Indicadores</a>
    @endif
    <a href="{{ $esCentral ? route('impulso.lotes') : route('gobierno.lotes') }}" class="btn btn-secondary"><i class="bi bi-collection"></i> Lotes y plazos</a>
    @if($esCentral)
      <a href="{{ route('gobierno.importar') }}" class="btn btn-primary"><i class="bi bi-file-earmark-arrow-up"></i> Importar Excel</a>
    @endif
  </div>
</div>

<div class="estado-chips">
  <a href="{{ route('gobierno.index', request()->except('estado', 'page')) }}" class="estado-chip {{ request('estado') ? '' : 'active' }}"><strong>{{ $total }}</strong> Todos</a>
  @foreach(\App\Models\ExpedienteGobierno::ESTADOS as $est)
    @if($conteo[$est] ?? 0)
      <a href="{{ route('gobierno.index', array_merge(request()->except('page'), ['estado' => $est])) }}" class="estado-chip {{ request('estado') === $est ? 'active' : '' }}">
        <strong>{{ $conteo[$est] }}</strong> <span class="badge {{ Estilo::badgeHonramiento($est) }}">{{ $est }}</span>
      </a>
    @endif
  @endforeach
</div>

<div class="card card-flush">
  <form method="GET" style="padding:16px 20px;border-bottom:1px solid var(--borde)">
    <input type="hidden" name="estado" value="{{ request('estado') }}">
    <div class="grid4" style="align-items:end">
      <div class="field span-2">
        <label for="q">DNI, cliente, crédito, operación o ID</label>
        <input id="q" type="search" name="q" value="{{ request('q') }}" placeholder="Buscar">
      </div>
      <div class="field">
        <label for="gestion_id">Gestión</label>
        <select id="gestion_id" name="gestion_id"><option value="">Todas</option>
          @foreach($gestiones as $g)<option value="{{ $g->id }}" @selected(request('gestion_id') == $g->id)>{{ $g->nombre }}</option>@endforeach
        </select>
      </div>
      <div class="field">
        <label for="lote_id">Lote</label>
        <select id="lote_id" name="lote_id"><option value="">Todos</option>
          @foreach($lotes as $l)<option value="{{ $l->id }}" @selected(request('lote_id') == $l->id)>{{ $l->codigo }}{{ $l->programa ? ' · '.$l->programa : '' }}</option>@endforeach
        </select>
      </div>
      <div class="field">
        <label for="zona_id">Zona</label>
        <select id="zona_id" name="zona_id" onchange="this.form.tienda_id.value=''"><option value="">Todas</option>
          @foreach($zonas as $z)<option value="{{ $z->id }}" @selected(request('zona_id') == $z->id)>{{ $z->nombre }}</option>@endforeach
        </select>
      </div>
      <div class="field">
        <label for="tienda_id">Tienda</label>
        <select id="tienda_id" name="tienda_id"><option value="">Todas</option>
          @foreach($tiendas as $t)<option value="{{ $t->id }}" @selected(request('tienda_id') == $t->id)>{{ $t->nombre }}</option>@endforeach
        </select>
      </div>
      <div class="field">
        <label for="plazo">Plazo de regularización</label>
        <select id="plazo" name="plazo"><option value="">Todos</option>
          <option value="vencido" @selected(request('plazo') === 'vencido')>Vencido</option>
          <option value="por_vencer" @selected(request('plazo') === 'por_vencer')>Por vencer (7 días)</option>
          <option value="vigente" @selected(request('plazo') === 'vigente')>Vigente</option>
        </select>
      </div>
      <div class="field">
        <label for="tipo_persona">Tipo de persona</label>
        <select id="tipo_persona" name="tipo_persona"><option value="">Todas</option>
          <option value="natural" @selected(request('tipo_persona') === 'natural')>Natural (6 doc. COFIDE)</option>
          <option value="juridica" @selected(request('tipo_persona') === 'juridica')>Jurídica (7 doc. COFIDE)</option>
        </select>
      </div>
      <div class="field">
        <label for="honramiento">Honramiento</label>
        <select id="honramiento" name="honramiento"><option value="">Todos</option>
          @foreach(['Honrado' => 'Honrado por COFIDE', 'Solicitado' => 'En solicitud', 'No honrado' => 'No honrado'] as $v => $lbl)
            <option value="{{ $v }}" @selected(request('honramiento') === $v)>{{ $lbl }}</option>
          @endforeach
        </select>
      </div>
      <div class="field">
        <label>Fecha de revisión</label>
        <div class="d-flex gap-2"><input type="date" name="desde" value="{{ request('desde') }}" aria-label="Desde"><input type="date" name="hasta" value="{{ request('hasta') }}" aria-label="Hasta"></div>
      </div>
      <div class="d-flex gap-2">
        <button class="btn btn-primary"><i class="bi bi-funnel"></i> Filtrar</button>
        <a href="{{ route('gobierno.index') }}" class="btn btn-ghost">Limpiar</a>
      </div>
    </div>
  </form>

  <div class="table-wrap">
    <table class="tabla-gob">
      <thead>
        <tr>
          <th>Gestión</th><th>Rev. central</th><th>Rev. zona</th><th>ID</th><th>DNI / RUC</th><th>Crédito SICMACT</th><th>Cuenta BT</th><th>Operación</th>
          <th>Zona / tienda</th><th>Cliente</th><th class="num">Monto honrado</th><th>Lote</th><th>Estado</th>
          @foreach(FormatoExcel::DOCUMENTOS as $n => $nombre)
            <th class="doc-col {{ in_array($n, [1, 3, 4, 6, 7, 8]) ? 'exigido' : '' }}" title="{{ $nombre }}{{ in_array($n, [1, 3, 4, 6, 7, 8]) ? ' · exigido por COFIDE' : '' }}">D{{ $n }}</th>
          @endforeach
          <th class="center" title="Documentos exigidos por COFIDE faltantes">Falt.</th><th style="min-width:220px">Observaciones</th><th></th>
        </tr>
      </thead>
      <tbody>
        @forelse($expedientes as $e)
          @php $cols = $checklist->porColumnaExcel($e); $plazo = $e->situacionPlazo(); @endphp
          <tr>
            <td class="nowrap">{{ $e->gestion->nombre ?? '—' }}</td>
            <td class="nowrap">{{ $e->revisado_central_en?->format('d/m/Y') ?? '—' }}</td>
            <td class="nowrap">{{ $e->revisado_zona_en?->format('d/m/Y') ?? '—' }}</td>
            <td class="nowrap"><a href="{{ route('gobierno.show', $e) }}" class="fw-600">{{ $e->id_externo }}</a></td>
            <td class="nowrap">{{ $e->dni }}<div class="cell-sub">{{ $e->tipo_persona === 'juridica' ? 'Jurídica' : 'Natural' }}</div></td>
            <td class="nowrap">{{ $e->credito_sicmact ?? '—' }}</td>
            <td class="nowrap">{{ $e->cuenta_bt ?? '—' }}</td>
            <td class="nowrap">{{ $e->operacion ?? '—' }}</td>
            <td class="nowrap">{{ $e->zona->nombre ?? '—' }}<div class="cell-sub">{{ $e->tienda->nombre ?? '' }}</div></td>
            <td><span class="cell-main">{{ $e->cliente }}</span></td>
            <td class="num nowrap">{{ number_format((float) $e->monto_honrado, 2) }}</td>
            <td class="nowrap">{{ $e->lote->codigo ?? '—' }}
              @if($plazo === 'vencido')<div><span class="badge badge-danger">Plazo vencido</span></div>
              @elseif($plazo === 'por_vencer')<div><span class="badge badge-pending">Vence en {{ $e->lote->diasRestantes() }} d</span></div>@endif
            </td>
            <td class="nowrap"><span class="badge {{ Estilo::badgeHonramiento($e->estado) }}">{{ $e->estado }}</span></td>
            @foreach($cols as $n => $c)
              @php [$ico, $color, $lbl] = Estilo::iconoDocumento($c['estado']); @endphp
              <td class="center"><i class="bi {{ $ico }} {{ $color }} doc-ico" title="{{ $c['nombre'] }}: {{ $lbl }}{{ $c['exigido'] ? ' · exigido por COFIDE' : '' }}"></i></td>
            @endforeach
            <td class="center fw-600 {{ $e->faltantes ? 'text-rojo' : 'text-verde' }}">{{ $e->faltantes }}</td>
            <td class="cell-sub" style="white-space:normal">{{ \Illuminate\Support\Str::limit(trim($e->observacionesAutomaticas().($e->observaciones ? ' | '.$e->observaciones : ''), ' |'), 120) ?: '—' }}</td>
            <td class="num"><a href="{{ route('gobierno.show', $e) }}" class="btn btn-secondary btn-sm">Abrir</a></td>
          </tr>
        @empty
          <tr><td colspan="27" class="empty-state"><i class="bi bi-folder2-open"></i><strong>Sin expedientes</strong><span>No hay expedientes con los filtros aplicados.</span></td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
  {{ $expedientes->links() }}
</div>

<p class="cell-sub">
  <i class="bi bi-check-lg text-verde"></i> cargado ·
  <i class="bi bi-check-circle-fill text-verde"></i> conforme ·
  <i class="bi bi-exclamation-triangle-fill text-mostaza"></i> observado ·
  <i class="bi bi-x-lg text-rojo"></i> falta ·
  <i class="bi bi-slash-circle text-rojo"></i> no disponible ·
  <span class="text-rojo fw-600">D en rojo</span>: exigido por COFIDE. Pase el cursor sobre cada ícono para ver el documento.
</p>
@endsection
