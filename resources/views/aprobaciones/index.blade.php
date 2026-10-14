@extends('layouts.app')
@section('title', 'Mis aprobaciones')

@use('App\Support\Estilo')

@section('content')
<div class="page-head">
  <div>
    <h2>Mis aprobaciones</h2>
    <p>Propuestas que usted decidió, con su estado actual y la trazabilidad completa de cada operación.</p>
  </div>
</div>

<div class="kpi-grid">
  <a href="{{ route('aprobaciones.index', ['decision' => 'Aprobado']) }}" class="kpi-card verde">
    <div class="kpi-label"><i class="bi bi-check2-circle"></i> Aprobadas</div>
    <div class="kpi-value">{{ $kpis['aprobados'] }}</div><div class="kpi-sub">Ver aprobadas</div>
  </a>
  <a href="{{ route('aprobaciones.index', ['decision' => 'Observado']) }}" class="kpi-card mostaza">
    <div class="kpi-label"><i class="bi bi-arrow-return-left"></i> Observadas</div>
    <div class="kpi-value">{{ $kpis['observados'] }}</div><div class="kpi-sub">Devueltas para corrección</div>
  </a>
  <a href="{{ route('aprobaciones.index', ['decision' => 'Rechazado']) }}" class="kpi-card rojo">
    <div class="kpi-label"><i class="bi bi-x-circle"></i> Rechazadas</div>
    <div class="kpi-value">{{ $kpis['rechazados'] }}</div><div class="kpi-sub">Decisión definitiva</div>
  </a>
  <div class="kpi-card info">
    <div class="kpi-label"><i class="bi bi-calendar-check"></i> Decisiones del mes</div>
    <div class="kpi-value">{{ $kpis['mes'] }}</div><div class="kpi-sub">{{ ucfirst(now()->locale('es')->translatedFormat('F Y')) }}</div>
  </div>
</div>

<div class="card card-flush">
  <form method="GET" class="grid4 filtros-patio">
    <div class="field">
      <label for="q">Cliente, DNI u operación</label>
      <input id="q" type="search" name="q" value="{{ request('q') }}" placeholder="Buscar">
    </div>
    <div class="field">
      <label for="decision">Mi decisión</label>
      <select id="decision" name="decision">
        <option value="">Todas</option>
        @foreach(['Aprobado' => 'Aprobada', 'Observado' => 'Observada', 'Rechazado' => 'Rechazada'] as $v => $l)
          <option value="{{ $v }}" @selected(request('decision') === $v)>{{ $l }}</option>
        @endforeach
      </select>
    </div>
    <div class="field span-2">
      <label>Fecha de decisión</label>
      <div class="rango-fechas">
        <input type="date" name="desde" value="{{ request('desde') }}" aria-label="Desde">
        <input type="date" name="hasta" value="{{ request('hasta') }}" aria-label="Hasta">
      </div>
    </div>
    <div class="d-flex gap-2">
      <button class="btn btn-primary"><i class="bi bi-funnel"></i> Filtrar</button>
      <a href="{{ route('aprobaciones.index') }}" class="btn btn-ghost">Limpiar</a>
    </div>
  </form>

  <div class="table-wrap">
    <table>
      <thead>
        <tr><th>Decisión</th><th>Cliente</th><th>Tipo</th><th class="num">Saldo capital</th><th>Ejecutivo</th><th>Mi decisión</th><th>Estado actual</th><th></th></tr>
      </thead>
      <tbody>
        @forelse($propuestas as $p)
          @php
            $miDecision = $p->versiones->count() ? 'Observado' : $p->estado_fud;
            $vig = $p->ultimaVersion();
            $sus = $vig->sustentos()->latest('id')->first();
          @endphp
          <tr>
            <td class="nowrap">{{ ($p->decidido_en ?? $p->fecha_decision)->format('d/m/Y') }}
              <div class="cell-sub">{{ $p->decidido_en?->format('H:i') }}</div></td>
            <td><div class="cell-main">{{ $p->cliente->nombre_cliente }}</div><div class="cell-sub">Op. {{ $p->cliente->operacion }} · {{ $p->cliente->tienda->nombre ?? '' }}</div></td>
            <td>{{ $p->tipo_negociacion_desc }}</td>
            <td class="num">{{ $p->cliente->saldo_formateado }}</td>
            <td>{{ $p->enviador->name }}</td>
            <td><span class="badge {{ Estilo::badge($miDecision) }}">{{ $miDecision === 'Aprobado' ? 'Aprobada' : ($miDecision === 'Observado' ? 'Observada' : 'Rechazada') }}</span></td>
            <td>
              @if($vig->estado_fud === 'Aprobado' && $sus)
                <span class="badge {{ Estilo::badge($sus->estado) }}">Sustento: {{ $sus->estado === 'Aprobado' ? 'conforme' : mb_strtolower($sus->estado) }}</span>
              @else
                <span class="badge {{ Estilo::badge($vig->estado_fud) }}">{{ $vig->id !== $p->id ? 'Corrección: ' : '' }}{{ $vig->estado_fud }}</span>
              @endif
            </td>
            <td class="num nowrap">
              <a href="{{ route('aprobaciones.show', $p) }}" class="btn btn-secondary btn-sm">Trazabilidad</a>
              <a href="{{ route('aprobaciones.pdf', $p) }}" class="btn btn-ghost btn-sm btn-icon" title="Descargar PDF"><i class="bi bi-file-earmark-pdf"></i></a>
            </td>
          </tr>
        @empty
          <tr><td colspan="8" class="empty-state">
            <i class="bi bi-inbox"></i><strong>Sin decisiones</strong><span>No hay propuestas decididas con los filtros aplicados.</span>
          </td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
  {{ $propuestas->links() }}
</div>
@endsection
