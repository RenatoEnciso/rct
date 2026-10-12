@extends('layouts.app')
@section('title', 'Detalle de propuesta')

@use('App\Support\Estilo')

@section('content')
@php
  $cli = $fud->cliente;
  $det = $fud->getDetalle();
  $sustento = $fud->sustentos->first();
  $volver = auth()->user()->esGrupoFud() ? route('fud.index') : route('bandeja.index');
@endphp

<div class="page-head">
  <div>
    <div class="page-eyebrow"><a href="{{ $volver }}">{{ auth()->user()->esGrupoFud() ? 'Mis propuestas' : 'Bandeja' }}</a> / Propuesta FUD-{{ str_pad($fud->id, 5, '0', STR_PAD_LEFT) }}</div>
    <h2>{{ $cli->nombre_cliente }}</h2>
    <p>Operación {{ $cli->operacion }} · {{ $fud->tipo_negociacion_desc }}</p>
  </div>
  <div class="page-head-actions">
    @if($fud->veces_observado > 0)
      <span class="badge badge-gray">Corrección {{ $fud->veces_observado }}</span>
    @endif
    <span class="badge badge-lg {{ $fud->badgeClass() }}"><i class="bi {{ Estilo::icono($fud->estadoVisible()) }}"></i> {{ $fud->estadoVisible() }}</span>
  </div>
</div>

@if($fud->estaObservado() && $fud->enviado_por === auth()->id())
  <div class="notice-warning">
    <i class="bi bi-exclamation-triangle"></i>
    <div style="flex:1">
      <strong>Propuesta observada.</strong> {{ $fud->comentario_aprobador }}
    </div>
    <a href="{{ route('fud.edit', $fud) }}" class="btn btn-warning btn-sm"><i class="bi bi-pencil-square"></i> Corregir y reenviar</a>
  </div>
@endif

<div class="grid2" style="margin-bottom:20px">
  <div class="card">
    <div class="card-title"><i class="bi bi-person-vcard"></i> Cliente y crédito</div>
    <div class="detail-grid">
      <div><div class="detail-label">Cuenta</div><div class="detail-value">{{ $cli->cuenta }}</div></div>
      <div><div class="detail-label">Operación</div><div class="detail-value">{{ $cli->operacion }}</div></div>
      <div><div class="detail-label">Saldo capital</div><div class="detail-value">{{ $cli->saldo_formateado }}</div></div>
      <div><div class="detail-label">Días de atraso</div><div class="detail-value">{{ $cli->dias_atraso }} días</div></div>
      <div><div class="detail-label">Calificación RCC</div><div class="detail-value">{{ $cli->calificacion_rcc ?? '—' }}</div></div>
      <div><div class="detail-label">Situación de cartera</div><div class="detail-value">{{ $cli->estado_cartera }}</div></div>
      <div><div class="detail-label">Tipo de crédito</div><div class="detail-value">{{ $cli->tipo_credito }}</div></div>
      <div><div class="detail-label">Conciliaciones (12 meses)</div><div class="detail-value">{{ $cli->veces_conciliado }}</div></div>
    </div>
  </div>

  <div class="card">
    <div class="card-title"><i class="bi bi-file-earmark-text"></i> Propuesta</div>
    <div class="detail-grid">
      <div><div class="detail-label">Enviado por</div><div class="detail-value">{{ $fud->enviador->name }}</div></div>
      <div><div class="detail-label">Fecha de envío</div><div class="detail-value">{{ $fud->fecha_envio->format('d/m/Y') }}</div></div>
      <div><div class="detail-label">Aprobador asignado</div><div class="detail-value">{{ $fud->aprobador->name ?? '—' }}</div></div>
      <div><div class="detail-label">Nivel de autonomía</div><div class="detail-value">Nivel {{ $fud->nivel_autonomia_req }}</div></div>
      <div><div class="detail-label">Tipo de operación</div><div class="detail-value">{{ $fud->tipo_operacion }}</div></div>
      @if($sustento)
        <div><div class="detail-label">Sustentos</div><div class="detail-value"><span class="badge {{ Estilo::badge($sustento->estado) }}">{{ $sustento->estado === 'Aprobado' ? 'Conforme' : $sustento->estado }}</span></div></div>
      @endif
    </div>
  </div>
</div>

@if($det)
  <div class="card">
    <div class="card-title"><i class="bi bi-sliders"></i> Condiciones propuestas</div>
    <div class="detail-grid">
      @if($fud->tipo_negociacion === 'cuotas')
        <div><div class="detail-label">Cuotas</div><div class="detail-value">{{ $det->cuota_desde }} a {{ $det->cuota_hasta }}</div></div>
        <div><div class="detail-label">Descuento de intereses</div><div class="detail-value">{{ rtrim(rtrim((string) $det->desc_interes, '0'), '.') }}%</div></div>
        <div><div class="detail-label">Descuento de moratorio</div><div class="detail-value">{{ rtrim(rtrim((string) $det->desc_moratorio, '0'), '.') }}%</div></div>
        <div><div class="detail-label">Pone el crédito al día</div><div class="detail-value">{{ $det->pone_al_dia ? 'Sí' : 'No' }}</div></div>
        <div><div class="detail-label">Cierra el mes vigente</div><div class="detail-value">{{ $det->cierra_mes_vigente ? 'Sí' : 'No' }}</div></div>
      @elseif($fud->tipo_negociacion === 'cancelacion')
        <div><div class="detail-label">Monto a pagar</div><div class="detail-value lg">{{ Estilo::soles($det->monto_a_pagar) }}</div></div>
        <div><div class="detail-label">Descuento de intereses</div><div class="detail-value">{{ rtrim(rtrim((string) $det->desc_interes, '0'), '.') }}%</div></div>
        <div><div class="detail-label">Descuento de capital</div><div class="detail-value">{{ rtrim(rtrim((string) $det->desc_capital, '0'), '.') }}%</div></div>
        <div><div class="detail-label">Fecha límite de pago</div><div class="detail-value">{{ $det->fecha_limite?->format('d/m/Y') ?? '—' }}</div></div>
        <div><div class="detail-label">Saldo capital mayor a 3 UIT</div><div class="detail-value">{{ $det->mayor_tres_uit ? 'Sí' : 'No' }}</div></div>
        <div><div class="detail-label">Búsqueda registral</div><div class="detail-value">{{ $det->sunarp_positivo ? 'Positiva' : 'Negativa' }}</div></div>
      @else
        <div><div class="detail-label">Beneficio propuesto sobre SK</div><div class="detail-value lg">{{ rtrim(rtrim((string) $det->beneficio_pct_sk, '0'), '.') }}%</div></div>
        <div><div class="detail-label">Etapa procesal</div><div class="detail-value">{{ $det->etapa_procesal ?? '—' }}</div></div>
        <div><div class="detail-label">Monto del gravamen / embargo</div><div class="detail-value">{{ $det->monto_gravamen ? Estilo::soles($det->monto_gravamen) : '—' }}</div></div>
      @endif
    </div>
  </div>
@endif

<div class="card">
  <div class="card-title"><i class="bi bi-chat-left-text"></i> Sustento de la propuesta</div>
  <div class="quote">{{ $fud->motivo }}</div>
  @if($fud->comentario_aprobador && !$fud->estaObservado())
    <div class="quote" style="margin-top:12px;border-left-color:{{ $fud->estaAprobado() ? 'var(--verde)' : 'var(--peligro)' }}">
      <div class="quote-label">Decisión de {{ $fud->aprobadoPor->name ?? 'el aprobador' }} · {{ $fud->fecha_decision?->format('d/m/Y') }}</div>
      {{ $fud->comentario_aprobador }}
    </div>
  @endif
</div>

<div class="grid2" style="margin-bottom:20px">
  <div class="card">
    <div class="card-title"><i class="bi bi-paperclip"></i> Archivos adjuntos <span class="badge badge-gray">{{ $fud->adjuntos->count() }}</span></div>
    @forelse($fud->adjuntos as $adj)
      <div class="d-flex justify-content-between align-items-center gap-2" style="padding:8px 0;border-bottom:1px solid #eef0f2">
        <div style="min-width:0">
          <div class="fs-13 fw-600 text-truncate">{{ $adj->nombre_archivo }}</div>
          <div class="cell-sub">{{ $adj->tipo_adjunto }} · {{ $adj->subidor->name }} · {{ \Carbon\Carbon::parse($adj->created_at)->format('d/m/Y') }} · {{ $adj->size_formateado }}</div>
        </div>
        <a href="{{ route('fud.download', $adj) }}" class="btn btn-secondary btn-sm btn-icon" title="Descargar"><i class="bi bi-download"></i></a>
      </div>
    @empty
      <div class="empty-state" style="padding:20px"><i class="bi bi-paperclip"></i><span>Sin archivos adjuntos.</span></div>
    @endforelse
  </div>

  <div class="card">
    <div class="card-title"><i class="bi bi-clock-history"></i> Historial de la propuesta</div>
    <div class="timeline">
      @foreach($hilo as $version)
        @php
          $actual = $version->id === $fud->id;
          $cls = $actual ? 'current' : match($version->estadoVisible()) { 'Aprobado' => 'ok', 'Observado', 'Corregido' => 'warn', 'Rechazado' => 'danger', default => '' };
        @endphp
        <div class="timeline-item {{ $cls }}">
          <div class="timeline-title">
            {{ $loop->first ? 'Envío inicial' : 'Corrección '.($loop->iteration - 1) }}
            <span class="badge {{ $version->badgeClass() }}" style="margin-left:6px">{{ $version->estadoVisible() }}</span>
            @if($actual)<span class="cell-sub">· versión vigente</span>@endif
          </div>
          <div class="timeline-meta">{{ $version->fecha_envio->format('d/m/Y') }} · {{ $version->enviador->name ?? '' }}</div>
          @if($version->comentario_aprobador)
            <div class="timeline-body text-gris">{{ $version->comentario_aprobador }}</div>
          @endif
          @if(!$actual)
            <a href="{{ route('fud.show', $version) }}" class="fs-12">Ver esta versión</a>
          @endif
        </div>
      @endforeach
    </div>
  </div>
</div>

<div class="actions actions-start">
  <a href="{{ $volver }}" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Volver</a>
  @if($fud->enviado_por === auth()->id() && $fud->estaObservado())
    <a href="{{ route('fud.edit', $fud) }}" class="btn btn-primary"><i class="bi bi-pencil-square"></i> Corregir propuesta</a>
  @endif
</div>
@endsection
