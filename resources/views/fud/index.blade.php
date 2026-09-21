@extends('layouts.app')
@section('title', 'Mis Propuestas')

@section('content')

<div class="page-head">
  <div class="page-head-text">
    <h2>📋 Mis Propuestas (FUDs enviados)</h2>
    <p>Historial de propuestas de exoneración enviadas al nivel de autonomía.</p>
  </div>
  <a href="{{ route('fud.create') }}" class="btn btn-primary">+ Nueva propuesta</a>
</div>

{{-- Filtros --}}
<div class="card-plain" style="display:flex;gap:12px;flex-wrap:wrap;margin-bottom:16px;padding:16px;border-radius:10px">
  <form method="GET" style="display:flex;gap:12px;flex-wrap:wrap;width:100%">
    <div class="field" style="flex:1;min-width:140px">
      <label>Estado</label>
      <select name="estado" onchange="this.form.submit()">
        <option value="">Todos</option>
        @foreach(['En revisión','Aprobado','Observado','Rechazado'] as $e)
          <option value="{{ $e }}" {{ request('estado') === $e ? 'selected' : '' }}>{{ $e }}</option>
        @endforeach
      </select>
    </div>
    <div class="field" style="flex:2;min-width:180px">
      <label>Buscar cliente</label>
      <input name="q" value="{{ request('q') }}" placeholder="Nombre o nro. operación"
             onchange="this.form.submit()">
    </div>
  </form>
</div>

<div class="card">
  <div class="card-title">Propuestas registradas</div>
  <table>
    <thead>
      <tr>
        <th>#</th><th>Fecha</th><th>Cliente</th><th>Operación</th>
        <th>Tipo</th><th>Saldo Capital</th><th>Aprobador</th><th>Estado</th><th></th>
      </tr>
    </thead>
    <tbody>
      @forelse($propuestas as $p)
        @php
          $badge = match($p->estado_fud) {
            'Aprobado'  => 'badge-ok',
            'Observado','Rechazado' => 'badge-danger',
            default     => 'badge-pending',
          };
        @endphp
        <tr>
          <td>{{ $propuestas->total() - $loop->index }}</td>
          <td>{{ $p->fecha_envio->format('d/m/Y') }}</td>
          <td><strong>{{ $p->cliente->nombre_cliente }}</strong></td>
          <td>{{ $p->cliente->operacion }}</td>
          <td style="font-size:12px">{{ $p->tipo_negociacion_desc ?? '—' }}</td>
          <td>{{ $p->cliente->saldo_formateado }}</td>
          <td style="font-size:12px;color:#666">{{ $p->aprobador->name ?? '—' }}</td>
          <td><span class="badge {{ $badge }}">{{ $p->estado_fud }}</span></td>
          <td>
            <div style="display:flex;gap:6px;flex-wrap:wrap">
              <a href="{{ route('fud.show', $p) }}"
                class="btn btn-ghost btn-sm">
                Ver Detalle
              </a>
              @if(!$p->estaAprobado())
                <a href="{{ route('fud.edit', $p) }}"
                  class="btn btn-secondary btn-sm">
                  <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/>
                    <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
                  </svg>
                  Editar
                </a>
              @endif
            </div>
          </td>
          
        </tr>
      @empty
        <tr>
          <td colspan="9" style="text-align:center;padding:24px;color:#999">
            No hay propuestas que coincidan.
          </td>
        </tr>
      @endforelse
    </tbody>
  </table>
  <div style="margin-top:16px">{{ $propuestas->withQueryString()->links() }}</div>
</div>

@endsection