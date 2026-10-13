@extends('layouts.app')
@section('title', 'Lotes y plazos')

@section('content')
@include('gobierno._modulo')
<div class="page-head">
  <div>
    <div class="page-eyebrow"><a href="{{ route('gobierno.index') }}">Expedientes de honramiento</a> / Lotes</div>
    <h2>Lotes y plazos de regularización</h2>
    <p>Los expedientes pueden regularizarse hasta 30 días calendario después de la conformidad de la solicitud formal de honramiento.</p>
  </div>
</div>

<div class="card card-flush">
  <div class="table-wrap">
    <table>
      <thead>
        <tr><th>Lote</th><th>Programa</th><th>Solicitud formal</th><th>Conformidad</th><th>Límite (30 días)</th><th>Plazo</th>
          <th class="num">Expedientes</th><th class="num">Sin completar</th>@if($puedeEditar)<th></th>@endif</tr>
      </thead>
      <tbody>
        @forelse($lotes as $l)
          @php $dias = $l->diasRestantes(); @endphp
          <tr>
            <td class="cell-main nowrap"><a href="{{ route('gobierno.index', ['lote_id' => $l->id]) }}">{{ $l->codigo }}</a></td>
            @if($puedeEditar)
              <td colspan="3">
                <form method="POST" action="{{ route('gobierno.lotes.actualizar', $l) }}" class="d-flex gap-2 align-items-center flex-wrap" id="lote-{{ $l->id }}">
                  @csrf @method('PUT')
                  <input class="input-rct" name="programa" value="{{ $l->programa }}" placeholder="Programa" style="max-width:170px;min-height:32px">
                  <input class="input-rct" type="date" name="fecha_solicitud" value="{{ $l->fecha_solicitud?->format('Y-m-d') }}" title="Solicitud formal" style="max-width:150px;min-height:32px">
                  <input class="input-rct" type="date" name="fecha_conformidad" value="{{ $l->fecha_conformidad?->format('Y-m-d') }}" title="Conformidad" style="max-width:150px;min-height:32px">
                </form>
              </td>
            @else
              <td>{{ $l->programa ?? '—' }}</td>
              <td>{{ $l->fecha_solicitud?->format('d/m/Y') ?? '—' }}</td>
              <td>{{ $l->fecha_conformidad?->format('d/m/Y') ?? '—' }}</td>
            @endif
            <td class="nowrap">{{ $l->fechaLimite()?->format('d/m/Y') ?? '—' }}</td>
            <td class="nowrap">
              @if($dias === null)<span class="badge badge-gray">Sin conformidad</span>
              @elseif($dias < 0)<span class="badge badge-danger">Vencido hace {{ abs($dias) }} d</span>
              @elseif($dias <= 7)<span class="badge badge-pending">Vence en {{ $dias }} d</span>
              @else<span class="badge badge-ok">{{ $dias }} días restantes</span>@endif
            </td>
            <td class="num">{{ $l->expedientes_count }}</td>
            <td class="num">@if($l->pendientes_count)<a href="{{ route('gobierno.index', ['lote_id' => $l->id]) }}" class="fw-600 text-rojo">{{ $l->pendientes_count }}</a>@else 0 @endif</td>
            @if($puedeEditar)<td class="num"><button form="lote-{{ $l->id }}" class="btn btn-secondary btn-sm">Guardar</button></td>@endif
          </tr>
        @empty
          <tr><td colspan="9" class="empty-state"><span>No hay lotes registrados. Se crean al importar el Excel.</span></td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>
@endsection
