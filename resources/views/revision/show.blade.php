@extends('layouts.app')
@section('title', 'Revisión de sustento')

@use('App\Support\Estilo')
@use('App\Services\Validacion\ResultadoValidacion')

@section('content')
@php
  $fud = $sustento->propuesta;
  $cli = $fud->cliente;
  $conHallazgos = $sustento->documentos->filter->tieneObservacionesCarga()->count();
@endphp

<div class="page-head">
  <div>
    <div class="page-eyebrow"><a href="{{ route('revision.index') }}">Revisión de sustentos</a> / Detalle</div>
    <h2>{{ $cli->nombre_cliente }}</h2>
    <p>Cuenta {{ $cli->cuenta }} · Operación {{ $cli->operacion }} · {{ $fud->tipo_negociacion_desc }}</p>
  </div>
  <div class="page-head-actions">
    <a href="{{ route('aprobaciones.show', $fud) }}" class="btn btn-secondary btn-sm"><i class="bi bi-diagram-3"></i> Trazabilidad completa</a>
    <span class="badge badge-lg {{ Estilo::badge($sustento->estado) }}">
      <i class="bi {{ Estilo::icono($sustento->estado) }}"></i> {{ $sustento->estado === 'Aprobado' ? 'Conforme' : $sustento->estado }}
    </span>
  </div>
</div>

<div class="card">
  <div class="card-title"><i class="bi bi-info-circle"></i> Datos de la operación</div>
  <div class="detail-grid">
    <div><div class="detail-label">Tienda</div><div class="detail-value">{{ $cli->tienda->nombre ?? '—' }}</div></div>
    <div><div class="detail-label">Saldo capital</div><div class="detail-value">{{ $cli->saldo_formateado }}</div></div>
    <div><div class="detail-label">Monto aprobado</div><div class="detail-value">{{ Estilo::soles($fud->detalleCancelacion->monto_a_pagar ?? $cli->saldo_capital) }}</div></div>
    <div><div class="detail-label">Aprobado por</div><div class="detail-value">{{ $fud->aprobadoPor->name ?? '—' }}</div></div>
    <div><div class="detail-label">Ejecutivo</div><div class="detail-value">{{ $sustento->cargador->name ?? '—' }}</div></div>
    <div><div class="detail-label">Fecha de carga</div><div class="detail-value">{{ $sustento->fecha_carga?->format('d/m/Y') ?? '—' }}</div></div>
  </div>
  @if($sustento->comentario_revision)
    <div class="quote" style="margin-top:16px">
      <div class="quote-label">Comentario de la revisión · {{ $sustento->revisador->name ?? '' }} {{ $sustento->fecha_revision?->format('d/m/Y H:i') }}</div>
      {{ $sustento->comentario_revision }}
    </div>
  @endif
</div>

<div class="card">
  <div class="card-head">
    <div class="card-title"><i class="bi bi-files"></i> Documentos ({{ $sustento->documentos->count() }})</div>
    @if($conHallazgos)
      <span class="badge badge-pending"><i class="bi bi-exclamation-triangle"></i> {{ $conHallazgos }} con observaciones de la validación automática</span>
    @endif
  </div>

  @forelse($sustento->documentos as $doc)
    @php
      $estadoVal = $doc->conforme_manual ? 'conforme manual' : $doc->estado_validacion;
      $etiquetaVal = $doc->conforme_manual ? 'Conforme manual' : ResultadoValidacion::etiqueta($doc->estado_validacion);
    @endphp
    <div class="doc-review">
      <div class="doc-review-head">
        <div>
          <div class="doc-slot-title"><i class="bi bi-file-earmark-text"></i>{{ Estilo::tipoDoc($doc->tipo_doc) }}</div>
          <div class="doc-review-meta">{{ $doc->nombre_archivo }} · {{ Estilo::tamano($doc->size_bytes) }}
            @if($doc->cargado_en) · cargado el {{ $doc->cargado_en->format('d/m/Y H:i') }} @endif</div>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
          <span class="badge {{ Estilo::badge($estadoVal) }}" title="Validación automática"><i class="bi bi-shield-check"></i> {{ $etiquetaVal }}</span>
          @if($doc->estado_revision !== 'pendiente')
            <span class="badge {{ $doc->estado_revision === 'aprobado' ? 'badge-ok' : 'badge-pending' }}">Revisor: {{ $doc->estado_revision === 'aprobado' ? 'aprobado' : 'observado' }}</span>
          @endif
          <a href="{{ route('sustentos.documento.descargar', $doc->id) }}" class="btn btn-secondary btn-sm"><i class="bi bi-download"></i> Descargar</a>
        </div>
      </div>

      @if($doc->hallazgos)
        <ul class="hallazgos">
          @foreach($doc->hallazgos as $h)
            <li class="{{ $h['severidad'] === 'rechazado' ? 'text-danger' : 'text-mostaza' }}">
              <i class="bi {{ $h['severidad'] === 'rechazado' ? 'bi-x-circle' : 'bi-exclamation-circle' }}"></i><span>{{ $h['mensaje'] }}</span>
            </li>
          @endforeach
        </ul>
      @elseif($doc->estado_validacion === 'conforme')
        <div class="fs-12 text-verde" style="margin-top:6px"><i class="bi bi-check-circle"></i> {{ ResultadoValidacion::MENSAJE_CONFORME }}</div>
      @endif

      @if($sustento->puedeRevisarse())
        <div class="d-flex gap-3 flex-wrap align-items-start" style="margin-top:12px">
          <div class="segmented" role="radiogroup" aria-label="Decisión sobre el documento">
            <label><input type="radio" name="docs[{{ $doc->id }}][estado_revision]" form="form-observar" value="aprobado" @checked($doc->estado_revision === 'aprobado')>
              <span class="opt-ok"><i class="bi bi-check-lg"></i> {{ $doc->tieneObservacionesCarga() ? 'Conforme manual' : 'Aprobar' }}</span></label>
            <label><input type="radio" name="docs[{{ $doc->id }}][estado_revision]" form="form-observar" value="observado" @checked($doc->estado_revision === 'observado')>
              <span class="opt-warn"><i class="bi bi-exclamation-lg"></i> Observar</span></label>
          </div>
          <div class="field" style="flex:1;min-width:240px">
            <textarea name="docs[{{ $doc->id }}][comentario_revisor]" form="form-observar" rows="2" style="min-height:38px"
                      placeholder="{{ $doc->tieneObservacionesCarga() ? 'Obligatorio si marca conforme manual: indique el motivo' : 'Comentario para el ejecutivo (opcional)' }}">{{ $doc->comentario_revisor }}</textarea>
          </div>
          <input type="hidden" name="docs[{{ $doc->id }}][id]" value="{{ $doc->id }}" form="form-observar">
        </div>
      @elseif($doc->comentario_revisor)
        <div class="quote" style="margin-top:10px"><div class="quote-label">{{ $doc->conforme_manual ? 'Motivo de la conformidad manual' : 'Comentario del revisor' }}</div>{{ $doc->comentario_revisor }}</div>
      @endif
    </div>
  @empty
    <div class="empty-state"><i class="bi bi-file-earmark"></i><strong>Sin documentos</strong><span>El ejecutivo aún no ha cargado los sustentos.</span></div>
  @endforelse

  @if($sustento->historial->count())
    <details style="margin-top:16px">
      <summary class="fs-13 text-gris" style="cursor:pointer">Historial de documentos reemplazados ({{ $sustento->historial->count() }})</summary>
      <div class="table-wrap" style="margin-top:10px">
        <table>
          <thead><tr><th>Documento</th><th>Archivo</th><th>Validación</th><th>Comentario</th><th>Cargado</th><th></th></tr></thead>
          <tbody>
            @foreach($sustento->historial as $h)
              <tr>
                <td>{{ Estilo::tipoDoc($h->tipo_doc) }}</td>
                <td class="cell-sub">{{ $h->nombre_archivo }}</td>
                <td><span class="badge {{ Estilo::badge($h->estado_validacion) }}">{{ ResultadoValidacion::etiqueta($h->estado_validacion) }}</span></td>
                <td class="cell-sub">{{ $h->comentario_revisor ?? '—' }}</td>
                <td class="nowrap cell-sub">{{ $h->cargado_en?->format('d/m/Y') }}</td>
                <td class="num"><a href="{{ route('sustentos.documento.descargar', $h->id) }}" class="btn btn-ghost btn-sm"><i class="bi bi-download"></i></a></td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </details>
  @endif
</div>

@if($sustento->puedeRevisarse())
  <div class="card">
    <div class="card-title"><i class="bi bi-check2-square"></i> Decisión de la revisión</div>
    <div class="grid3">
      <form action="{{ route('revision.aprobar', $sustento->id) }}" method="POST" class="card-plain" style="margin:0"
            data-confirm="¿Confirma la conformidad de todos los documentos de este sustento?">
        @csrf
        <div class="form-section-title text-verde"><i class="bi bi-check-circle"></i> Dar conformidad</div>
        <div class="field">
          <textarea name="comentario" rows="3" placeholder="{{ $conHallazgos ? 'Obligatorio: motivo de la conformidad manual' : 'Comentario (opcional)' }}"
                    {{ $conHallazgos ? 'required' : '' }}></textarea>
          @if($conHallazgos)<span class="field-hint">Los documentos observados por la validación automática quedarán como conformidad manual.</span>@endif
        </div>
        <button type="submit" class="btn btn-success btn-block" style="margin-top:12px">Aprobar sustento</button>
      </form>

      <form id="form-observar" action="{{ route('revision.observar', $sustento->id) }}" method="POST" class="card-plain" style="margin:0"
            data-confirm="El sustento volverá al ejecutivo para que corrija los documentos observados. ¿Continuar?">
        @csrf
        <div class="form-section-title text-mostaza"><i class="bi bi-arrow-return-left"></i> Observar</div>
        <div class="field">
          <textarea name="comentario" rows="3" required placeholder="Indique qué debe corregir el ejecutivo">{{ old('comentario') }}</textarea>
          <span class="field-hint">Marque arriba los documentos a observar.</span>
        </div>
        <button type="submit" class="btn btn-warning btn-block" style="margin-top:12px">Devolver para corrección</button>
      </form>

      <form action="{{ route('revision.rechazar', $sustento->id) }}" method="POST" class="card-plain" style="margin:0"
            data-confirm="El rechazo es definitivo. ¿Desea rechazar este sustento?">
        @csrf
        <div class="form-section-title text-danger"><i class="bi bi-x-circle"></i> Rechazar</div>
        <div class="field">
          <textarea name="comentario" rows="3" required placeholder="Motivo del rechazo"></textarea>
          <span class="field-hint">El rechazo es definitivo.</span>
        </div>
        <button type="submit" class="btn btn-danger btn-block" style="margin-top:12px">Rechazar sustento</button>
      </form>
    </div>
  </div>
@else
  <div class="notice-neutral notice">
    <i class="bi bi-info-circle"></i>
    <span>
      @if($sustento->fecha_revision)
        Revisado el {{ $sustento->fecha_revision->format('d/m/Y H:i') }} por {{ $sustento->revisador->name ?? '—' }}.
      @else
        Este sustento no está pendiente de revisión.
      @endif
    </span>
  </div>
@endif

<div class="actions actions-start">
  <a href="{{ route('revision.index') }}" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Volver al listado</a>
</div>
@endsection
