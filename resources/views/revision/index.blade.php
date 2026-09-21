@extends('layouts.app')
@section('title', 'Revisión de Sustentos')

@section('content')

<div class="page-head">
  <div class="page-head-text">
    <h2>🔍 Revisión de Sustentos</h2>
    <p>Documentación cargada por analistas pendiente de revisión.</p>
  </div>
</div>

{{-- Badges de conteo por estado --}}
<div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:16px">
  @foreach([
    'Pendiente'   => ['#6b7280','#f3f4f6'],
    'En revisión' => ['#1d4ed8','#eff6ff'],
    'Aprobado'    => ['#065f46','#ecfdf5'],
    'Observado'   => ['#92400e','#fffbeb'],
    'Rechazado'   => ['#7f1d1d','#fff5f5'],
  ] as $est => [$color,$bg])
    <div style="background:{{ $bg }};border:1px solid {{ $color }}33;border-radius:20px;
                padding:5px 14px;font-size:12px;font-weight:600;color:{{ $color }}">
      {{ $est }}: {{ $conteos[$est] ?? 0 }}
    </div>
  @endforeach
</div>

{{-- Filtros --}}
<div class="card" style="margin-bottom:16px">
  <form method="GET" action="{{ route('revision.index') }}"
        style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end">
    <div style="display:flex;flex-direction:column;gap:4px">
      <label style="font-size:11px;color:#6b7280;font-weight:600">Estado</label>
      <select name="estado" style="font-size:12px;padding:6px 10px;border:1px solid #d1d5db;border-radius:6px">
        <option value="">Todos</option>
        @foreach(['Pendiente','En revisión','Aprobado','Observado','Rechazado'] as $e)
          <option value="{{ $e }}" {{ request('estado') === $e ? 'selected' : '' }}>{{ $e }}</option>
        @endforeach
      </select>
    </div>
    <div style="display:flex;flex-direction:column;gap:4px">
      <label style="font-size:11px;color:#6b7280;font-weight:600">Cuenta</label>
      <input type="text" name="cuenta" value="{{ request('cuenta') }}"
             placeholder="Nro. cuenta..."
             style="font-size:12px;padding:6px 10px;border:1px solid #d1d5db;border-radius:6px">
    </div>
    <div style="display:flex;flex-direction:column;gap:4px">
      <label style="font-size:11px;color:#6b7280;font-weight:600">Desde</label>
      <input type="date" name="desde" value="{{ request('desde') }}"
             style="font-size:12px;padding:6px 10px;border:1px solid #d1d5db;border-radius:6px">
    </div>
    <div style="display:flex;flex-direction:column;gap:4px">
      <label style="font-size:11px;color:#6b7280;font-weight:600">Hasta</label>
      <input type="date" name="hasta" value="{{ request('hasta') }}"
             style="font-size:12px;padding:6px 10px;border:1px solid #d1d5db;border-radius:6px">
    </div>
    <button type="submit" class="btn btn-primary" style="font-size:12px;padding:7px 14px">
      🔎 Filtrar
    </button>
    <a href="{{ route('revision.index') }}" class="btn btn-ghost" style="font-size:12px;padding:7px 14px">
      Limpiar
    </a>
  </form>
</div>

{{-- Tabla --}}
<div class="card">
  <div class="card-title">Sustentos cargados</div>
  <table>
    <thead>
      <tr>
        <th>Cliente</th>
        <th>Cuenta</th>
        <th>Operación</th>
        <th>Analista</th>
        <th>Fecha carga</th>
        <th>Docs</th>
        <th>Estado</th>
        <th>Acción</th>
      </tr>
    </thead>
    <tbody>
      @forelse($sustentos as $s)
        @php
          $badgeMap = [
            'Pendiente'   => 'badge-pending',
            'En revisión' => 'badge-info',
            'Aprobado'    => 'badge-ok',
            'Observado'   => 'badge-warn',
            'Rechazado'   => 'badge-error',
          ];
          $badge    = $badgeMap[$s->estado] ?? 'badge-pending';
          $docOk    = $s->documentos->where('estado_validacion','ok')->count();
          $docWarn  = $s->documentos->where('estado_validacion','warn')->count();
          $docErr   = $s->documentos->where('estado_validacion','error')->count();
          $docTotal = $s->documentos->count();
        @endphp
        <tr>
          <td><strong>{{ $s->propuesta->cliente->nombre_cliente }}</strong></td>
          <td>{{ $s->propuesta->cliente->cuenta }}</td>
          <td>{{ $s->propuesta->cliente->operacion }}</td>
          <td style="font-size:12px">{{ $s->cargador->name ?? '—' }}</td>
          <td style="font-size:12px">{{ $s->fecha_carga?->format('d/m/Y') ?? '—' }}</td>
          <td style="font-size:11px">
            @if($docTotal)
              <span style="color:#065f46">✔ {{ $docOk }}</span>
              @if($docWarn) <span style="color:#92400e"> ⚠ {{ $docWarn }}</span> @endif
              @if($docErr)  <span style="color:#991b1b"> ✖ {{ $docErr }}</span>  @endif
              <span style="color:#9ca3af">/ {{ $docTotal }}</span>
            @else
              <span style="color:#9ca3af">Sin docs</span>
            @endif
          </td>
          <td><span class="badge {{ $badge }}">{{ $s->estado }}</span></td>
          <td>
            <a href="{{ route('revision.show', $s->id) }}"
               class="btn btn-primary" style="font-size:12px;padding:7px 12px">
              Ver detalle
            </a>
          </td>
        </tr>
      @empty
        <tr>
          <td colspan="8" style="text-align:center;padding:24px;color:#999">
            No hay sustentos con los filtros aplicados.
          </td>
        </tr>
      @endforelse
    </tbody>
  </table>

  @if($sustentos->hasPages())
    <div style="margin-top:16px">
      {{ $sustentos->withQueryString()->links() }}
    </div>
  @endif
</div>

@endsection