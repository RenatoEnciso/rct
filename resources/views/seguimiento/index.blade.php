@extends('layouts.app')
@section('title', 'Seguimiento')

@use('App\Support\Estilo')

@section('content')
<div class="page-head">
  <div>
    <h2>Seguimiento de operaciones</h2>
    <p>Bitácora de propuestas, decisiones y cargas de sustentos{{ auth()->user()->esAprobador() ? '' : ' realizadas por usted' }}.</p>
  </div>
</div>

<div class="card card-flush">
  <form method="GET" class="grid4" style="padding:16px 20px;align-items:end;border-bottom:1px solid var(--borde)">
    <div class="field">
      <label for="q">Cliente u operación</label>
      <input id="q" type="search" name="q" value="{{ request('q') }}" placeholder="Buscar">
    </div>
    <div class="field">
      <label for="tipo">Acción</label>
      <select id="tipo" name="tipo">
        <option value="">Todas</option>
        @foreach($tipos as $t)<option value="{{ $t }}" @selected(request('tipo') === $t)>{{ $t }}</option>@endforeach
      </select>
    </div>
    <div class="field">
      <label>Fecha</label>
      <div class="d-flex gap-2">
        <input type="date" name="desde" value="{{ request('desde') }}" aria-label="Desde">
        <input type="date" name="hasta" value="{{ request('hasta') }}" aria-label="Hasta">
      </div>
    </div>
    <div class="d-flex gap-2">
      <button class="btn btn-primary"><i class="bi bi-funnel"></i> Filtrar</button>
      <a href="{{ route('seguimiento.index') }}" class="btn btn-ghost">Limpiar</a>
    </div>
  </form>

  <div class="table-wrap">
    <table>
      <thead>
        <tr><th>Fecha</th><th>Acción</th><th>Cliente</th><th>Operación</th><th>Resultado</th><th>Responsable</th></tr>
      </thead>
      <tbody>
        @forelse($registros as $r)
          <tr>
            <td class="nowrap">{{ $r->fecha_accion->format('d/m/Y') }}
              @if($r->created_at)<div class="cell-sub">{{ \Carbon\Carbon::parse($r->created_at)->format('H:i') }}</div>@endif</td>
            <td>{{ $r->tipo_operacion }}
              @if($r->descripcion)<div class="cell-sub" style="max-width:320px">{{ \Illuminate\Support\Str::limit($r->descripcion, 110) }}</div>@endif</td>
            <td class="cell-main">{{ $r->cliente ?? '—' }}</td>
            <td class="nowrap">{{ $r->operacion ?? '—' }}</td>
            <td><span class="badge {{ Estilo::badge(\Illuminate\Support\Str::before($r->estado, ' (')) }}">{{ $r->estado }}</span></td>
            <td>{{ $r->user->name ?? '—' }}<div class="cell-sub">{{ $r->user->rol->nombre ?? '' }}</div></td>
          </tr>
        @empty
          <tr><td colspan="6" class="empty-state">
            <i class="bi bi-clock-history"></i><strong>Sin registros</strong><span>No hay actividades con los filtros aplicados.</span>
          </td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
  {{ $registros->links() }}
</div>
@endsection
