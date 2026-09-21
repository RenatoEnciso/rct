@extends('layouts.app')
@section('title', 'Seguimiento')

@section('content')

<div class="page-head">
  <div class="page-head-text">
    <h2>🔍 Seguimiento de operaciones</h2>
    <p>Historial de propuestas, aprobaciones y cargas de la campaña.</p>
  </div>
</div>

<div class="card">
  <div class="card-title">Registro de actividades</div>
  @if($registros->isEmpty())
    <div class="notice">
      No hay registros de seguimiento aún. Inicia el flujo desde
      <a href="{{ route('fud.create') }}" style="color:var(--rojo);font-weight:700">
        Envío de FUD →
      </a>
    </div>
  @else
    <table>
      <thead>
        <tr>
          <th>Fecha</th><th>Tipo de operación</th><th>Cliente</th>
          <th>Operación</th><th>Estado</th><th>Gestionó</th>
        </tr>
      </thead>
      <tbody>
        @foreach($registros as $r)
          @php
            $badge = match($r->estado) {
              'Aprobado','Sustentos completos' => 'badge-ok',
              'Rechazado','Observado'          => 'badge-danger',
              default                          => 'badge-pending',
            };
          @endphp
          <tr>
            <td>{{ $r->fecha_accion->format('d/m/Y') }}</td>
            <td>{{ $r->tipo_operacion }}</td>
            <td><strong>{{ $r->cliente ?? '—' }}</strong></td>
            <td>{{ $r->operacion ?? '—' }}</td>
            <td><span class="badge {{ $badge }}">{{ $r->estado }}</span></td>
            <td style="font-size:12px;color:#666">{{ $r->user->name ?? '—' }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
    <div style="margin-top:16px">{{ $registros->links() }}</div>
  @endif
</div>

@endsection