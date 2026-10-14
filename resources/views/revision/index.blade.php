@extends('layouts.app')
@section('title', 'Revisión de sustentos')

@use('App\Support\Estilo')

@section('content')
<div class="page-head">
  <div>
    <h2>Revisión de sustentos</h2>
    <p>Validación de la documentación de las conciliaciones aprobadas.</p>
  </div>
  <div class="page-head-actions">
    <a href="{{ route('revision.dashboard') }}" class="btn btn-secondary"><i class="bi bi-file-earmark-bar-graph"></i> Reporte de conciliaciones</a>
  </div>
</div>

<div class="kpi-grid">
  @foreach(['En revisión' => 'info', 'Observado' => 'mostaza', 'Pendiente' => '', 'Aprobado' => 'verde', 'Rechazado' => 'rojo'] as $estado => $color)
    <a href="{{ route('revision.index', ['estado' => $estado]) }}" class="kpi-card {{ $color }}" style="color:inherit">
      <div class="kpi-label"><i class="bi {{ Estilo::icono($estado) }}"></i> {{ $estado === 'Aprobado' ? 'Conformes' : $estado }}</div>
      <div class="kpi-value">{{ $conteos[$estado] ?? 0 }}</div>
      <div class="kpi-sub">{{ request('estado') === $estado ? 'Filtro aplicado' : 'Ver sustentos' }}</div>
    </a>
  @endforeach
</div>

<div class="card card-flush">
  <div class="card-head">
    <div class="card-title"><i class="bi bi-clipboard-check"></i> Sustentos cargados</div>
  </div>

  <form method="GET" action="{{ route('revision.index') }}" class="grid4" style="padding:0 20px 16px;align-items:end">
    <div class="field">
      <label for="f-q">Cliente, cuenta u operación</label>
      <input id="f-q" type="search" name="q" value="{{ request('q') }}" placeholder="Buscar">
    </div>
    <div class="field">
      <label for="f-estado">Estado</label>
      <select id="f-estado" name="estado">
        <option value="">Todos</option>
        @foreach(['En revisión','Observado','Pendiente','Aprobado','Rechazado'] as $e)
          <option value="{{ $e }}" @selected(request('estado') === $e)>{{ $e }}</option>
        @endforeach
      </select>
    </div>
    <div class="field span-2">
      <label>Fecha de carga</label>
      <div class="rango-fechas">
        <input type="date" name="desde" value="{{ request('desde') }}" aria-label="Desde">
        <input type="date" name="hasta" value="{{ request('hasta') }}" aria-label="Hasta">
      </div>
    </div>
    <div class="d-flex gap-2">
      <button type="submit" class="btn btn-primary"><i class="bi bi-funnel"></i> Filtrar</button>
      <a href="{{ route('revision.index') }}" class="btn btn-ghost">Limpiar</a>
    </div>
  </form>

  <div class="table-wrap">
    <table>
      <thead>
        <tr><th>Cliente</th><th>Cuenta / Operación</th><th>Ejecutivo</th><th>Carga</th><th>Validación automática</th><th>Estado</th><th></th></tr>
      </thead>
      <tbody>
        @forelse($sustentos as $s)
          @php
            $docs = $s->documentos;
            $conf = $docs->where('estado_validacion', 'conforme')->count();
            $obs  = $docs->whereIn('estado_validacion', ['observado', 'rechazado'])->count();
          @endphp
          <tr>
            <td><div class="cell-main">{{ $s->propuesta->cliente->nombre_cliente }}</div><div class="cell-sub">{{ $s->propuesta->tipo_negociacion_desc }}</div></td>
            <td class="nowrap"><div>{{ $s->propuesta->cliente->cuenta }}</div><div class="cell-sub">{{ $s->propuesta->cliente->operacion }}</div></td>
            <td>{{ $s->cargador->name ?? '—' }}</td>
            <td class="nowrap">{{ $s->fecha_carga?->format('d/m/Y') ?? '—' }}</td>
            <td>
              @if($docs->count())
                <span class="badge badge-ok">{{ $conf }} conforme(s)</span>
                @if($obs)<span class="badge badge-pending">{{ $obs }} con observaciones</span>@endif
              @else
                <span class="cell-sub">Sin documentos</span>
              @endif
            </td>
            <td><span class="badge {{ Estilo::badge($s->estado) }}">{{ $s->estado === 'Aprobado' ? 'Conforme' : $s->estado }}</span></td>
            <td class="num">
              <a href="{{ route('revision.show', $s->id) }}" class="btn {{ $s->puedeRevisarse() ? 'btn-primary' : 'btn-secondary' }} btn-sm">
                {{ $s->puedeRevisarse() ? 'Revisar' : 'Ver detalle' }}
              </a>
            </td>
          </tr>
        @empty
          <tr><td colspan="7" class="empty-state">
            <i class="bi bi-inbox"></i><strong>Sin resultados</strong><span>No hay sustentos con los filtros aplicados.</span>
          </td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
  {{ $sustentos->links() }}
</div>
@endsection
