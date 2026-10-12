@extends('layouts.app')
@section('title', 'Expediente de programa de gobierno')

@use('App\Support\Estilo')
@use('App\Services\Validacion\ResultadoValidacion')

@section('content')
@php
  $cli = $expediente->cliente;
  $conformes = $docs->filter->esConforme()->count();
@endphp

<div class="page-head">
  <div>
    <div class="page-eyebrow"><a href="{{ route('cartera.exclusiones') }}">Programas de gobierno</a> / Expediente</div>
    <h2>{{ $cli->nombre_cliente }}</h2>
    <p>{{ $expediente->programa }} · {{ $expediente->motivo_habilitacion }}</p>
  </div>
  <div class="page-head-actions">
    <span class="badge badge-lg {{ Estilo::badge($expediente->estado) }}"><i class="bi {{ Estilo::icono($expediente->estado) }}"></i> {{ $expediente->estado }}</span>
  </div>
</div>

<div class="grid2" style="margin-bottom:20px">
  <div class="card">
    <div class="card-title"><i class="bi bi-person-vcard"></i> Crédito</div>
    <div class="detail-grid">
      <div><div class="detail-label">Cuenta</div><div class="detail-value">{{ $cli->cuenta }}</div></div>
      <div><div class="detail-label">Operación</div><div class="detail-value">{{ $cli->operacion }}</div></div>
      <div><div class="detail-label">Documento</div><div class="detail-value">{{ $cli->tipo_documento }} {{ $cli->nro_documento }}</div></div>
      <div><div class="detail-label">Saldo capital</div><div class="detail-value">{{ $cli->saldo_formateado }}</div></div>
      <div><div class="detail-label">Calificación</div><div class="detail-value">{{ $cli->calificacion_rcc ?? '—' }}</div></div>
      <div><div class="detail-label">Tienda</div><div class="detail-value">{{ $cli->tienda->nombre ?? '—' }} · {{ $cli->zona->nombre ?? '' }}</div></div>
    </div>
  </div>
  <div class="card">
    <div class="card-title"><i class="bi bi-clipboard-data"></i> Avance del expediente</div>
    <div class="detail-grid" style="margin-bottom:14px">
      <div><div class="detail-label">Registrado</div><div class="detail-value">{{ $expediente->fecha_registro?->format('d/m/Y') }}</div></div>
      <div><div class="detail-label">Responsable</div><div class="detail-value">{{ $expediente->responsable }}</div></div>
      <div><div class="detail-label">Confirmación Dpto.</div><div class="detail-value">{{ $expediente->confirmacion_dpto ? 'Listado recibido' : 'Pendiente' }}</div></div>
    </div>
    <div class="metric-item-head"><span>Documentos conformes</span><span>{{ $conformes }} de {{ count($checklist) }}</span></div>
    <div class="bar-track"><div class="bar-fill {{ $conformes === count($checklist) ? 'ok' : 'warn' }}" style="width:{{ round($conformes / count($checklist) * 100) }}%"></div></div>
  </div>
</div>

<div class="card" data-validar-url="{{ route('validacion.documento') }}" data-contexto="gobierno" data-expediente-id="{{ $expediente->id }}">
  <div class="card-title"><i class="bi bi-list-check"></i> Checklist documentario</div>
  <div class="doc-grid">
    @foreach($checklist as $tipo)
      @php
        $doc = $docs->get($tipo);
        $estado = $doc ? ($doc->conforme_manual ? 'conforme manual' : $doc->estado_validacion) : null;
      @endphp
      <div class="doc-slot {{ $doc ? 'is-'.($doc->esConforme() ? 'conforme' : $doc->estado_validacion) : '' }}" data-tipo="{{ $tipo }}">
        <div class="doc-slot-head">
          <span class="doc-slot-title"><i class="bi bi-file-earmark-text"></i>{{ $tipo }}</span>
          @if($doc)
            <span class="badge {{ Estilo::badge($estado) }}">{{ $doc->conforme_manual ? 'Conforme manual' : ResultadoValidacion::etiqueta($doc->estado_validacion) }}</span>
          @else
            <span class="badge badge-gray">Faltante</span>
          @endif
        </div>

        @if($doc)
          <a href="{{ route('cartera.gobierno.descargar', $doc) }}" class="file-chip"><i class="bi bi-file-earmark-pdf"></i><span>{{ $doc->nombre_archivo }}</span></a>
          @if($doc->hallazgos)
            <ul class="hallazgos">
              @foreach($doc->hallazgos as $h)
                <li class="{{ $h['severidad'] === 'rechazado' ? 'text-danger' : 'text-mostaza' }}"><i class="bi bi-exclamation-circle"></i><span>{{ $h['mensaje'] }}</span></li>
              @endforeach
            </ul>
          @endif
          @if($doc->conforme_manual && $doc->comentario_revisor)
            <div class="cell-sub" style="margin-top:6px"><i class="bi bi-person-check"></i> {{ $doc->comentario_revisor }}</div>
          @endif

          @if(!$doc->esConforme())
            <form method="POST" action="{{ route('cartera.gobierno.conforme', $doc) }}" class="d-flex gap-2" style="margin-top:10px">
              @csrf
              <input class="input-rct" name="comentario" placeholder="Motivo de la conformidad manual" required>
              <button class="btn btn-secondary btn-sm"><i class="bi bi-check-lg"></i> Conforme</button>
            </form>
          @endif
        @endif

        @if(!$doc || !$doc->esConforme())
          <form method="POST" action="{{ route('cartera.gobierno.documento', $expediente) }}" enctype="multipart/form-data" style="margin-top:10px">
            @csrf
            <input type="hidden" name="tipo" value="{{ $tipo }}">
            <label class="doc-drop"><i class="bi bi-cloud-arrow-up"></i>
              <span class="doc-file-name">{{ $doc ? 'Reemplazar documento' : 'Seleccionar archivo' }}</span>
              <input type="file" name="archivo" accept=".pdf,.jpg,.jpeg,.png">
            </label>
            <div class="val-result"></div>
            <button type="submit" class="btn btn-primary btn-sm" style="margin-top:8px;display:none" data-guardar>
              <i class="bi bi-save"></i> Guardar documento
            </button>
          </form>
        @endif
      </div>
    @endforeach
  </div>
</div>

<div class="actions actions-start">
  <a href="{{ route('cartera.exclusiones') }}" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Volver</a>
</div>
@endsection

@push('scripts')
<script>
// Al terminar la validación automática se habilita "Guardar documento" en ese espacio
document.addEventListener('rct:validado', function (e) {
  var slot = e.target.closest('.doc-slot');
  var btn = slot && slot.querySelector('[data-guardar]');
  if (btn) btn.style.display = slot.querySelector('input[type=file]').files.length ? '' : 'none';
});
</script>
@endpush
