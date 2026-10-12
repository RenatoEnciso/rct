@extends('layouts.app')
@section('title', 'Trazabilidad de la operación')

@use('App\Support\Estilo')

@section('content')
@php
  $codigo = 'FUD-'.str_pad($vigente->id, 5, '0', STR_PAD_LEFT);
  $num = fn($v) => rtrim(rtrim((string) $v, '0'), '.');
  $volver = auth()->user()->esAprobador() ? route('aprobaciones.index') : route('fud.index');
@endphp

<div class="page-head">
  <div>
    <div class="page-eyebrow"><a href="{{ $volver }}">{{ auth()->user()->esAprobador() ? 'Mis aprobaciones' : 'Mis propuestas' }}</a> / Trazabilidad</div>
    <h2>{{ $cliente->nombre_cliente }}</h2>
    <p>{{ $codigo }} · Operación {{ $cliente->operacion }} · {{ $vigente->tipo_negociacion_desc }}</p>
  </div>
  <div class="page-head-actions">
    <a href="{{ route('aprobaciones.pdf', $vigente) }}" class="btn btn-primary"><i class="bi bi-file-earmark-pdf"></i> Descargar PDF</a>
  </div>
</div>

<div class="notice notice-neutral">
  <i class="bi bi-geo"></i>
  <div style="flex:1"><strong>Estado actual:</strong> {{ $resumen['etapa'] }}</div>
  <span class="badge {{ Estilo::badge($resumen['estado_fud']) }}">Propuesta: {{ $resumen['estado_fud'] }}</span>
  @if($resumen['estado_sustento'])
    <span class="badge {{ Estilo::badge($resumen['estado_sustento']) }}">Sustento: {{ $resumen['estado_sustento'] === 'Aprobado' ? 'Conforme' : $resumen['estado_sustento'] }}</span>
  @endif
</div>

<div class="grid2" style="margin-bottom:20px">
  <div class="card">
    <div class="card-title"><i class="bi bi-person-vcard"></i> Cliente y crédito</div>
    <div class="detail-grid">
      <div><div class="detail-label">Documento</div><div class="detail-value">{{ $cliente->tipo_documento }} {{ $cliente->nro_documento }}</div></div>
      <div><div class="detail-label">Cuenta</div><div class="detail-value">{{ $cliente->cuenta }}</div></div>
      <div><div class="detail-label">Saldo capital</div><div class="detail-value">{{ $cliente->saldo_formateado }}</div></div>
      <div><div class="detail-label">Días de atraso</div><div class="detail-value">{{ $cliente->dias_atraso }}</div></div>
      <div><div class="detail-label">Calificación</div><div class="detail-value">{{ $cliente->calificacion_rcc }}</div></div>
      <div><div class="detail-label">Tienda</div><div class="detail-value">{{ $cliente->tienda->nombre ?? '—' }} · {{ $cliente->zona->nombre ?? '' }}</div></div>
    </div>
  </div>
  <div class="card">
    <div class="card-title"><i class="bi bi-sliders"></i> Condiciones vigentes</div>
    <div class="detail-grid">
      <div><div class="detail-label">Ejecutivo</div><div class="detail-value">{{ $vigente->enviador->name }}</div></div>
      <div><div class="detail-label">Aprobador</div><div class="detail-value">{{ $vigente->aprobadoPor->name ?? $vigente->aprobador->name ?? '—' }}</div></div>
      <div><div class="detail-label">Nivel de autonomía</div><div class="detail-value">Nivel {{ $vigente->nivel_autonomia_req }}</div></div>
      @if($detalle && $vigente->tipo_negociacion === 'cuotas')
        <div><div class="detail-label">Cuotas</div><div class="detail-value">{{ $detalle->cuota_desde }} a {{ $detalle->cuota_hasta }}</div></div>
        <div><div class="detail-label">Descuento intereses / moratorio</div><div class="detail-value">{{ $num($detalle->desc_interes) }}% / {{ $num($detalle->desc_moratorio) }}%</div></div>
      @elseif($detalle && $vigente->tipo_negociacion === 'cancelacion')
        <div><div class="detail-label">Monto a pagar</div><div class="detail-value">{{ Estilo::soles($detalle->monto_a_pagar) }}</div></div>
        <div><div class="detail-label">Descuento intereses / capital</div><div class="detail-value">{{ $num($detalle->desc_interes) }}% / {{ $num($detalle->desc_capital) }}%</div></div>
      @elseif($detalle)
        <div><div class="detail-label">Beneficio sobre SK</div><div class="detail-value">{{ $num($detalle->beneficio_pct_sk) }}%</div></div>
        <div><div class="detail-label">Etapa procesal</div><div class="detail-value">{{ $detalle->etapa_procesal ?? '—' }}</div></div>
      @endif
    </div>
  </div>
</div>

<div class="grid2" style="grid-template-columns:1fr 1.15fr;margin-bottom:20px">
  <div class="card">
    <div class="card-title"><i class="bi bi-clock-history"></i> Línea de tiempo</div>
    <div class="timeline">
      @foreach($eventos as $e)
        <div class="timeline-item {{ $e['estado'] === 'info' ? '' : $e['estado'] }}">
          <div class="timeline-title">{{ $e['titulo'] }}</div>
          <div class="timeline-meta">{{ \Carbon\Carbon::parse($e['fecha'])->format('d/m/Y H:i') }} · {{ $e['actor'] }}</div>
          @if($e['detalle'])<div class="timeline-body text-gris">{{ $e['detalle'] }}</div>@endif
        </div>
      @endforeach
    </div>
  </div>

  <div>
    <div class="card card-flush">
      <div class="card-head"><div class="card-title"><i class="bi bi-layers"></i> Versiones de la propuesta</div></div>
      <div class="table-wrap">
        <table>
          <thead><tr><th>Versión</th><th>Enviada</th><th>Decisión</th><th>Comentario del aprobador</th></tr></thead>
          <tbody>
            @foreach($versiones as $i => $v)
              @php $dec = $v->versiones()->exists() ? 'Observado' : ($v->fecha_decision ? $v->estado_fud : null); @endphp
              <tr>
                <td class="cell-main nowrap">{{ $i === 0 ? 'Envío inicial' : 'Corrección '.$i }}</td>
                <td class="nowrap">{{ $v->created_at->format('d/m/Y H:i') }}<div class="cell-sub">{{ $v->enviador->name }}</div></td>
                <td class="nowrap">
                  @if($dec)
                    <span class="badge {{ Estilo::badge($dec) }}">{{ $dec }}</span>
                    <div class="cell-sub">{{ ($v->decidido_en ?? $v->fecha_decision)->format('d/m/Y H:i') }} · {{ $v->aprobadoPor->name ?? '—' }}</div>
                  @else
                    <span class="badge badge-info">En revisión</span>
                  @endif
                </td>
                <td class="cell-sub" style="white-space:normal;min-width:180px">{{ $v->comentario_aprobador ?? '—' }}</td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>

    <div class="card card-flush">
      <div class="card-head"><div class="card-title"><i class="bi bi-paperclip"></i> Archivos adjuntos a la propuesta</div></div>
      <div class="table-wrap">
        <table>
          <thead><tr><th>Archivo</th><th>Tipo</th><th>Versión</th><th>Cargado</th></tr></thead>
          <tbody>
            @forelse($adjuntos as $a)
              <tr>
                <td><span class="file-chip"><i class="bi bi-file-earmark"></i><span>{{ $a['nombre'] }}</span></span></td>
                <td>{{ $a['tipo'] }}</td>
                <td>{{ $a['version'] }}</td>
                <td class="nowrap">{{ $a['fecha']?->format('d/m/Y H:i') }}<div class="cell-sub">{{ $a['por'] }}</div></td>
              </tr>
            @empty
              <tr><td colspan="4" class="empty-state" style="padding:18px"><span>Sin archivos adjuntos.</span></td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<div class="card card-flush">
  <div class="card-head">
    <div class="card-title"><i class="bi bi-shield-check"></i> Documentos de sustento · validación y revisión</div>
    @if($sustento?->revisador)
      <span class="cell-sub">Revisado por {{ $sustento->revisador->name }} ({{ $sustento->revisador->rol->nombre ?? '' }}) el {{ $sustento->fecha_revision?->format('d/m/Y H:i') }}</span>
    @endif
  </div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Documento</th><th>Archivo</th><th>Cargado</th><th>Validación automática</th><th>Resultado de la revisión</th></tr></thead>
      <tbody>
        @forelse($documentos as $d)
          <tr style="{{ $d['vigente'] ? '' : 'opacity:.6' }}">
            <td class="cell-main">{{ $d['tipo'] }}</td>
            <td><div>{{ $d['nombre'] }}</div><div class="cell-sub">{{ $d['tamano'] }}</div></td>
            <td class="nowrap">{{ $d['cargado_en']?->format('d/m/Y H:i') ?? '—' }}<div class="cell-sub">{{ $sustento->cargador->name ?? '' }}</div></td>
            <td style="white-space:normal;min-width:200px">
              <span class="badge {{ Estilo::badge($d['auto_estado']) }}">{{ $d['automatica'] }}</span>
              @foreach($d['hallazgos'] as $h)<div class="cell-sub" style="margin-top:3px">{{ $h }}</div>@endforeach
            </td>
            <td style="white-space:normal;min-width:200px">
              <div class="fs-13">{{ $d['resultado'] }}</div>
              @if($d['comentario'])<div class="cell-sub">«{{ $d['comentario'] }}»</div>@endif
            </td>
          </tr>
        @empty
          <tr><td colspan="5" class="empty-state">
            <i class="bi bi-file-earmark"></i><strong>Sin documentos de sustento</strong>
            <span>{{ $vigente->estaAprobado() ? 'El ejecutivo aún no ha cargado los sustentos.' : 'Los sustentos se habilitan cuando la propuesta es aprobada.' }}</span>
          </td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

<div class="actions actions-start">
  <a href="{{ $volver }}" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Volver</a>
  <a href="{{ route('aprobaciones.pdf', $vigente) }}" class="btn btn-primary"><i class="bi bi-file-earmark-pdf"></i> Descargar PDF</a>
</div>
@endsection
