@extends('layouts.app')
@section('title', 'Panel principal')

@section('content')
@php
  $estadoBadge = fn($e) => match($e) {
    'Aprobado' => 'badge-ok', 'Observado' => 'badge-pending', 'Rechazado' => 'badge-danger', default => 'badge-info',
  };
@endphp

<div class="page-head">
  <div>
    @php $h = (int) now()->format('G'); @endphp
    <h2>{{ $h < 12 ? 'Buenos días' : ($h < 19 ? 'Buenas tardes' : 'Buenas noches') }}, {{ $user->name }}</h2>
    @if($campana)
      <p>Campaña «{{ $campana->nombre }}» · vigencia del {{ $campana->fecha_inicio->format('d/m/Y') }} al {{ $campana->fecha_fin->format('d/m/Y') }}</p>
    @else
      <p>No hay una campaña activa configurada.</p>
    @endif
  </div>
  <div class="page-head-actions">
    @if($campana && now()->between($campana->fecha_inicio, $campana->fecha_fin->copy()->endOfDay()))
      <span class="badge badge-ok dot">Campaña vigente · {{ (int) now()->diffInDays($campana->fecha_fin) }} días restantes</span>
    @endif
    @if($user->esGrupoFud())
      <a href="{{ route('fud.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Nueva propuesta</a>
    @else
      <a href="{{ route('bandeja.index') }}" class="btn btn-primary"><i class="bi bi-inbox"></i> Ir a la bandeja</a>
    @endif
  </div>
</div>

@if($user->esGrupoFud())
  <div class="kpi-grid">
    <div class="kpi-card">
      <div class="kpi-label"><i class="bi bi-folder2"></i> Mis expedientes</div>
      <div class="kpi-value">{{ $kpis['total'] }}</div>
      <div class="kpi-sub">Propuestas vigentes</div>
    </div>
    <div class="kpi-card info">
      <div class="kpi-label"><i class="bi bi-hourglass-split"></i> En revisión</div>
      <div class="kpi-value">{{ $kpis['revision'] }}</div>
      <div class="kpi-sub">Esperando decisión del aprobador</div>
    </div>
    <div class="kpi-card verde">
      <div class="kpi-label"><i class="bi bi-check2-circle"></i> Aprobadas</div>
      <div class="kpi-value">{{ $kpis['aprobadas'] }}</div>
      <div class="kpi-sub">{{ $kpis['total'] ? round($kpis['aprobadas'] / $kpis['total'] * 100) : 0 }}% de mis expedientes</div>
    </div>
    <div class="kpi-card rojo">
      <div class="kpi-label"><i class="bi bi-exclamation-circle"></i> Requieren atención</div>
      <div class="kpi-value">{{ $kpis['observadas'] + $kpis['sustentos'] }}</div>
      <div class="kpi-sub">{{ $kpis['observadas'] }} observadas o rechazadas · {{ $kpis['sustentos'] }} sustentos</div>
    </div>
  </div>

  @if($kpis['sustentos'])
    <div class="notice-warning">
      <i class="bi bi-paperclip"></i>
      <span>Tiene <strong>{{ $kpis['sustentos'] }}</strong> operación(es) aprobada(s) con sustentos por cargar o corregir.
        <a href="{{ route('sustentos.index') }}" class="fw-600">Ir a carga de sustentos</a></span>
    </div>
  @endif

  <div class="card">
    <div class="card-title"><i class="bi bi-signpost-split"></i> Flujo de la conciliación</div>
    <div class="steps">
      <div class="step done">Contacto con el cliente</div>
      <div class="step done">Verificación en base de campaña</div>
      <div class="step active">Envío de propuesta FUD</div>
      <div class="step">Aprobación por autonomía</div>
      <div class="step">Ejecución en tienda</div>
      <div class="step">Carga de sustentos</div>
    </div>
  </div>

  <div class="card card-flush">
    <div class="card-head">
      <div class="card-title"><i class="bi bi-clock-history"></i> Propuestas recientes</div>
      <a href="{{ route('fud.index') }}" class="btn btn-secondary btn-sm">Ver todas</a>
    </div>
    <div class="table-wrap">
      <table>
        <thead>
          <tr><th>Cliente</th><th>Tipo de negociación</th><th>Aprobador</th><th>Enviado</th><th>Estado</th><th></th></tr>
        </thead>
        <tbody>
          @forelse($propuestas as $p)
            <tr>
              <td><div class="cell-main">{{ $p->cliente->nombre_cliente }}</div><div class="cell-sub">Op. {{ $p->cliente->operacion }}</div></td>
              <td>{{ $p->tipo_negociacion_desc ?? '—' }}</td>
              <td>{{ $p->aprobador->name ?? '—' }}</td>
              <td class="nowrap">{{ $p->fecha_envio->format('d/m/Y') }}</td>
              <td><span class="badge {{ $estadoBadge($p->estado_fud) }}">{{ $p->estado_fud }}</span></td>
              <td class="num"><a href="{{ route('fud.show', $p) }}" class="btn btn-ghost btn-sm">Ver detalle <i class="bi bi-chevron-right"></i></a></td>
            </tr>
          @empty
            <tr data-empty><td colspan="6" class="empty-state">
              <i class="bi bi-folder2-open"></i><strong>Aún no ha enviado propuestas</strong>
              <span>Registre su primera propuesta desde <a href="{{ route('fud.create') }}">Nueva propuesta FUD</a>.</span>
            </td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

@else
  <div class="kpi-grid">
    <div class="kpi-card rojo">
      <div class="kpi-label"><i class="bi bi-inbox"></i> En mi bandeja</div>
      <div class="kpi-value">{{ $kpis['pendientes'] }}</div>
      <div class="kpi-sub">FUD pendientes de decisión</div>
    </div>
    <a href="{{ route('aprobaciones.index', ['decision' => 'Aprobado']) }}" class="kpi-card verde" style="color:inherit">
      <div class="kpi-label"><i class="bi bi-check2-circle"></i> Aprobados por mí</div>
      <div class="kpi-value">{{ $kpis['aprobadas'] }}</div>
      <div class="kpi-sub">{{ $kpis['mes'] }} decisiones en el mes · ver detalle <i class="bi bi-chevron-right"></i></div>
    </a>
    <a href="{{ route('aprobaciones.index', ['decision' => 'Observado']) }}" class="kpi-card mostaza" style="color:inherit">
      <div class="kpi-label"><i class="bi bi-arrow-return-left"></i> Observados por mí</div>
      <div class="kpi-value">{{ $kpis['observadas'] }}</div>
      <div class="kpi-sub">Esperando corrección · ver detalle <i class="bi bi-chevron-right"></i></div>
    </a>
    <div class="kpi-card info">
      <div class="kpi-label"><i class="bi bi-diagram-3"></i> Nivel de autonomía</div>
      <div class="kpi-value">{{ $kpis['nivel'] }}</div>
      <div class="kpi-sub">{{ $user->rol->nombre }}</div>
    </div>
  </div>

  <div class="grid2 mb-4">
    <div class="card">
      <div class="card-title"><i class="bi bi-person-badge"></i> Alcance de su autonomía</div>
      @php
        $autonomias = [
          1 => 'Pago de cuotas; cancelaciones totales de cartera Deficiente, Dudoso o Pérdida sin descuento de capital; conciliación de capital con SK menor a 3 UIT.',
          2 => 'Todo lo del nivel 1, además de cancelaciones con SK mayor a 3 UIT, excepciones a los topes de descuento y casos que exceden el límite de 4 conciliaciones en 12 meses.',
          3 => 'Casos escalados desde la Jefatura de Recuperaciones y excepciones fuera de su autonomía.',
        ];
      @endphp
      <p class="fs-13 text-gris m-0">{{ $autonomias[$user->nivel()] ?? '' }}</p>
    </div>
    <div class="card">
      <div class="card-title"><i class="bi bi-calendar-event"></i> Plazos de la campaña</div>
      <div class="metric-list fs-13">
        <div class="d-flex gap-2"><i class="bi bi-dot text-rojo"></i><span>Reporte de conciliaciones: primeros 2 días hábiles del mes.</span></div>
        <div class="d-flex gap-2"><i class="bi bi-dot text-rojo"></i><span>Sustentos: carga y validación diaria por tienda y zona.</span></div>
        <div class="d-flex gap-2"><i class="bi bi-dot text-rojo"></i><span>Pago fraccionado: máximo {{ $campana->max_dias_pago_fraccionado ?? 90 }} días desde la primera amortización.</span></div>
      </div>
    </div>
  </div>

  <div class="card card-flush">
    <div class="card-head">
      <div class="card-title"><i class="bi bi-inbox"></i> Pendientes de decisión</div>
      <a href="{{ route('bandeja.index') }}" class="btn btn-secondary btn-sm">Abrir bandeja</a>
    </div>
    <div class="table-wrap">
      <table data-rct-table data-page-size="8" data-search-placeholder="Buscar cliente, operación o ejecutivo">
        <thead>
          <tr><th>Enviado</th><th>Ejecutivo</th><th>Cliente</th><th class="num">Saldo capital</th><th>Calificación</th><th>Tipo</th></tr>
        </thead>
        <tbody>
          @forelse($pendientes as $p)
            <tr>
              <td class="nowrap">{{ $p->fecha_envio->format('d/m/Y') }}</td>
              <td>{{ $p->enviador->name }}</td>
              <td><div class="cell-main">{{ $p->cliente->nombre_cliente }}</div><div class="cell-sub">Op. {{ $p->cliente->operacion }}</div></td>
              <td class="num">{{ $p->cliente->saldo_formateado }}</td>
              <td><span class="badge badge-gray">{{ $p->cliente->calificacion_rcc }}</span></td>
              <td>{{ $p->tipo_negociacion_desc }}</td>
            </tr>
          @empty
            <tr data-empty><td colspan="6" class="empty-state">
              <i class="bi bi-check2-all"></i><strong>Bandeja al día</strong><span>No tiene propuestas pendientes de decisión.</span>
            </td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
@endif
@endsection
