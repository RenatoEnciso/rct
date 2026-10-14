@extends('layouts.app')
@section('title', 'Notificaciones')

@section('content')
@php
  $categorias = ['' => 'Todas', 'decision' => 'Decisiones', 'solicitud' => 'Solicitudes', 'sustento' => 'Sustentos', 'gobierno' => 'Gobierno', 'recordatorio' => 'Recordatorios'];
  $noLeidas = $todas->where('leida', false)->count();
@endphp

<div class="page-head">
  <div>
    <h2>Notificaciones</h2>
    <p>Actividad de los últimos {{ config('rct.notificaciones.ventana_dias') }} días y recordatorios de tareas pendientes.</p>
  </div>
  <div class="page-head-actions">
    @if($noLeidas)
      <form method="POST" action="{{ route('notificaciones.leer-todas') }}">
        @csrf
        <button class="btn btn-secondary"><i class="bi bi-check2-all"></i> Marcar todo como leído</button>
      </form>
    @endif
  </div>
</div>

<div class="card card-flush">
  <div class="card-head" style="padding-bottom:12px;border-bottom:1px solid var(--borde)">
    <div class="notif-tabs" style="padding:0;border:0">
      @foreach($categorias as $cat => $lbl)
        @if(!$cat || ($conteo[$cat] ?? 0))
          <a href="{{ route('notificaciones.index', array_filter(['categoria' => $cat, 'estado' => request('estado')])) }}"
             class="{{ request('categoria', '') === $cat ? 'active' : '' }}"
             style="padding:5px 11px;border-radius:999px;font-size:13px;{{ request('categoria', '') === $cat ? 'background:var(--rojo-soft);color:var(--rojo);font-weight:600' : 'color:var(--gris)' }}">
            {{ $lbl }} <span class="cell-sub">{{ $cat ? ($conteo[$cat] ?? 0) : $todas->count() }}</span>
          </a>
        @endif
      @endforeach
    </div>
    <a href="{{ route('notificaciones.index', array_filter(['categoria' => request('categoria'), 'estado' => request('estado') === 'no-leidas' ? null : 'no-leidas'])) }}"
       class="btn btn-ghost btn-sm"><i class="bi {{ request('estado') === 'no-leidas' ? 'bi-check-square' : 'bi-square' }}"></i> Solo no leídas ({{ $noLeidas }})</a>
  </div>

  @forelse($notificaciones as $n)
    <a href="{{ $n['url'] }}" class="notif-item {{ $n['leida'] ? '' : 'unread' }}" data-clave="{{ $n['clave'] }}" style="padding:14px 20px">
      <span class="notif-ico {{ $n['color'] }}"><i class="bi {{ $n['icono'] }}"></i></span>
      <span class="notif-body flex-grow-1">
        <span class="notif-title">{{ $n['titulo'] }}</span>
        <span class="notif-msg">{{ $n['mensaje'] }}</span>
      </span>
      <span class="notif-time nowrap" style="margin:0 18px 0 0">
        {{ $n['categoria'] === 'recordatorio' ? 'Recordatorio' : $n['fecha']->format('d/m/Y H:i') }}
      </span>
    </a>
  @empty
    <div class="empty-state"><i class="bi bi-bell-slash"></i><strong>Sin notificaciones</strong><span>No hay notificaciones con este filtro.</span></div>
  @endforelse
</div>
@endsection
