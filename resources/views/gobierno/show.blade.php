@extends('layouts.app')
@section('title', 'Expediente '.$e->id_externo)

@use('App\Support\Estilo')
@use('App\Services\Validacion\ResultadoValidacion')

@section('content')
@php
  $plazo = $e->situacionPlazo();
  $limite = $e->lote?->fechaLimite();
  $nExig = $exigidos->count();
  $okExig = $exigidos->whereIn('estado', ['cargado', 'conforme'])->count();
  $origenes = ['rct' => 'Archivo en RCT', 'sftp' => 'Presentado (archivo en SFTP)', 'excel' => 'Registrado desde Excel'];
@endphp

<div class="page-head">
  <div>
    <div class="page-eyebrow"><a href="{{ route('gobierno.index') }}">Expedientes de honramiento</a> / ID {{ $e->id_externo }}</div>
    <h2>{{ $e->cliente }}</h2>
    <p>{{ $e->tipo_persona === 'juridica' ? 'RUC' : 'DNI' }} {{ $e->dni }} · {{ $e->tipo_persona === 'juridica' ? 'Persona jurídica' : 'Persona natural' }} · {{ $e->lote->programa ?? 'Programa de Gobierno' }}</p>
  </div>
  <div class="page-head-actions">
    @if($plazo === 'vencido')<span class="badge badge-danger badge-lg"><i class="bi bi-alarm"></i> Plazo vencido</span>
    @elseif($plazo === 'por_vencer')<span class="badge badge-pending badge-lg"><i class="bi bi-alarm"></i> Vence en {{ $e->lote->diasRestantes() }} días</span>@endif
    <span class="badge badge-lg {{ Estilo::badgeHonramiento($e->estado) }}">{{ $e->estado }}</span>
  </div>
</div>

<div class="grid2" style="grid-template-columns:1.4fr 1fr;margin-bottom:20px">
  <div class="card">
    <div class="card-title"><i class="bi bi-person-vcard"></i> Datos del crédito honrado</div>
    <div class="detail-grid">
      <div><div class="detail-label">ID</div><div class="detail-value">{{ $e->id_externo }}</div></div>
      <div><div class="detail-label">Gestión</div><div class="detail-value">{{ $e->gestion->nombre ?? '—' }}</div></div>
      <div><div class="detail-label">Crédito SICMACT</div><div class="detail-value">{{ $e->credito_sicmact ?? '—' }}</div></div>
      <div><div class="detail-label">Cuenta BT</div><div class="detail-value">{{ $e->cuenta_bt ?? '—' }}</div></div>
      <div><div class="detail-label">Operación</div><div class="detail-value">{{ $e->operacion ?? '—' }}</div></div>
      <div><div class="detail-label">Zona / tienda</div><div class="detail-value">{{ $e->zona->nombre ?? '—' }} · {{ $e->tienda->nombre ?? '—' }}</div></div>
      <div><div class="detail-label">Monto honrado</div><div class="detail-value lg">{{ Estilo::soles($e->monto_honrado) }}</div></div>
      <div><div class="detail-label">Lote</div><div class="detail-value">{{ $e->lote->codigo ?? '—' }}</div></div>
    </div>
  </div>
  <div class="card">
    <div class="card-title"><i class="bi bi-clipboard-data"></i> Avance y plazo</div>
    <div class="metric-item-head"><span>Documentos exigidos por COFIDE</span><span>{{ $okExig }} de {{ $nExig }}</span></div>
    <div class="bar-track" style="margin-bottom:14px"><div class="bar-fill {{ $okExig === $nExig ? 'ok' : 'warn' }}" style="width:{{ $nExig ? round($okExig / $nExig * 100) : 0 }}%"></div></div>
    <div class="detail-grid">
      <div><div class="detail-label">Faltantes (COFIDE)</div><div class="detail-value lg {{ $e->faltantes ? 'text-rojo' : 'text-verde' }}">{{ $e->faltantes }}</div></div>
      <div><div class="detail-label">Conformidad de la solicitud</div><div class="detail-value">{{ $e->lote?->fecha_conformidad?->format('d/m/Y') ?? 'Sin registrar' }}</div></div>
      <div><div class="detail-label">Límite de regularización</div><div class="detail-value">{{ $limite?->format('d/m/Y') ?? '—' }}</div></div>
    </div>
    @if(!$limite)<div class="field-hint" style="margin-top:8px">Registre la fecha de conformidad del lote en <a href="{{ route('gobierno.lotes') }}">Lotes y plazos</a> para controlar los 30 días.</div>@endif
  </div>
</div>

@if($observacionesAuto || $e->observaciones)
  <div class="notice {{ $e->faltantes ? 'notice-warning' : 'notice-neutral' }}">
    <i class="bi bi-chat-left-text"></i>
    <div>@if($observacionesAuto)<div><strong>Observaciones:</strong> {{ $observacionesAuto }}</div>@endif
      @if($e->observaciones)<div style="margin-top:2px"><strong>Comentario:</strong> {{ $e->observaciones }}</div>@endif</div>
  </div>
@endif

@foreach(['Documentos exigidos por COFIDE' => $exigidos, 'Documentos complementarios' => $complementarios] as $titulo => $grupo)
  <div class="card" data-validar-url="{{ route('validacion.documento') }}" data-contexto="gobierno" data-expediente-id="{{ $e->id }}">
    <div class="card-head">
      <div class="card-title"><i class="bi {{ $loop->first ? 'bi-shield-check' : 'bi-folder2' }}"></i> {{ $titulo }}</div>
      <span class="cell-sub">{{ $loop->first ? 'Definen si el expediente está completo ('.($e->tipo_persona === 'juridica' ? '7 para persona jurídica' : '6 para persona natural').')' : 'Se registran, pero no bloquean el expediente' }}</span>
    </div>
    <div class="doc-grid">
      @foreach($grupo as $item)
        @php
          $t = $item['tipo']; $d = $item['doc']; $est = $item['estado'];
          [$ico, $color, $lbl] = Estilo::iconoDocumento($est);
          $badge = ['conforme' => 'badge-ok', 'cargado' => 'badge-info', 'observado' => 'badge-pending', 'no_disponible' => 'badge-rojo'][$est] ?? 'badge-gray';
          $puedeSubir = $puedeCargar && in_array($est, ['falta', 'observado', 'no_disponible', 'cargado']);
        @endphp
        <div class="doc-slot {{ $est === 'conforme' ? 'is-conforme' : ($est === 'observado' ? 'is-observado' : (in_array($est, ['falta', 'no_disponible']) && $item['exigido'] ? 'is-rechazado' : '')) }}" data-tipo="{{ $t->nombre }}">
          <div class="doc-slot-head">
            <span class="doc-slot-title"><i class="bi {{ $ico }} {{ $color }}"></i>{{ $t->nombre }}</span>
            <span class="badge {{ $badge }}">{{ $lbl }}</span>
          </div>

          @if($d)
            <div class="cell-sub" style="margin-bottom:6px">{{ $origenes[$d->origen] ?? '' }}
              @if($d->cargado_en) · {{ $d->cargado_en->format('d/m/Y H:i') }}@endif @if($d->cargador) · {{ $d->cargador->name }}@endif</div>
            @if($d->tieneArchivo())
              <div class="d-flex gap-2 flex-wrap" style="margin-bottom:6px">
                <a href="{{ route('gobierno.documento.ver', $d) }}" target="_blank" class="file-chip"><i class="bi bi-file-earmark-pdf"></i><span>{{ $d->nombre_archivo }}</span></a>
                <a href="{{ route('gobierno.documento.descargar', $d) }}" class="btn btn-ghost btn-sm btn-icon" title="Descargar"><i class="bi bi-download"></i></a>
              </div>
              @if($d->hallazgos)
                <ul class="hallazgos">@foreach($d->hallazgos as $h)<li class="text-mostaza"><i class="bi bi-exclamation-circle"></i><span>{{ $h['mensaje'] }}</span></li>@endforeach</ul>
              @elseif($d->estado_validacion)
                <div class="fs-12 text-verde"><i class="bi bi-shield-check"></i> Validación automática: {{ ResultadoValidacion::etiqueta($d->estado_validacion) }}</div>
              @endif
            @endif
            @if($est === 'no_disponible' && $d->motivo_no_disponible)
              <div class="quote" style="margin-top:6px"><div class="quote-label">Motivo de la tienda</div>{{ $d->motivo_no_disponible }}</div>
            @endif
            @if($d->comentario_revisor)
              <div class="quote" style="margin-top:6px;border-left-color:{{ $est === 'observado' ? '#d08a1e' : 'var(--verde)' }}">
                <div class="quote-label">Revisión · {{ $d->revisor->name ?? '' }} {{ $d->revisado_en?->format('d/m/Y H:i') }}</div>{{ $d->comentario_revisor }}</div>
            @endif
          @endif

          @if($puedeRevisarDocs && $d && in_array($est, ['cargado', 'conforme', 'observado']) && $e->estado !== 'Conforme')
            <div class="d-flex gap-2" style="margin-top:10px">
              @if($est !== 'conforme')
                <form method="POST" action="{{ route('gobierno.revision.documento', $d) }}">@csrf
                  <input type="hidden" name="resultado" value="conforme">
                  <button class="btn btn-success btn-sm"><i class="bi bi-check-lg"></i> Conforme</button></form>
              @endif
              @if($est !== 'observado')
                <button type="button" class="btn btn-warning btn-sm" data-modal-open="modal-obs" data-doc-url="{{ route('gobierno.revision.documento', $d) }}" data-doc-nombre="{{ $t->nombre }}">
                  <i class="bi bi-exclamation-lg"></i> Observar</button>
              @endif
            </div>
          @endif

          @if($puedeSubir)
            <form method="POST" action="{{ route('gobierno.documento', $e) }}" enctype="multipart/form-data" style="margin-top:10px">
              @csrf
              <input type="hidden" name="tipo_documento_id" value="{{ $t->id }}">
              <label class="doc-drop"><i class="bi bi-cloud-arrow-up"></i>
                <span class="doc-file-name">{{ $d && $d->tieneArchivo() ? 'Reemplazar PDF' : 'Cargar PDF' }}</span>
                <input type="file" name="archivo" accept=".pdf,application/pdf">
              </label>
              <div class="val-result"></div>
              <button type="submit" class="btn btn-primary btn-sm" style="margin-top:8px;display:none" data-guardar><i class="bi bi-save"></i> Guardar documento</button>
            </form>
          @endif
          @if($puedeCargar && $item['exigido'] && $est === 'falta')
            <button type="button" class="btn btn-ghost btn-sm" style="margin-top:6px" data-modal-open="modal-nd" data-tipo-id="{{ $t->id }}" data-tipo-nombre="{{ $t->nombre }}">
              <i class="bi bi-slash-circle"></i> No se cuenta con el documento
            </button>
          @endif
        </div>
      @endforeach
    </div>
  </div>
@endforeach

@if($nivelRevision)
  @php $hayObservados = $exigidos->contains('estado', 'observado') || $complementarios->contains('estado', 'observado'); @endphp
  <div class="card" style="border-color:var(--rojo-line)">
    <div class="card-head">
      <div class="card-title"><i class="bi bi-clipboard-check"></i> Revisión {{ $nivelRevision === 'zona' ? 'por zona' : 'central' }}</div>
      <span class="cell-sub">{{ $nivelRevision === 'zona' ? 'Coordinador Zonal de Recuperaciones' : 'Jefatura de Recuperaciones' }}</span>
    </div>
    @if($hayObservados)
      <div class="notice-warning"><i class="bi bi-exclamation-triangle"></i>
        <span>Hay documentos observados. Registre la observación para que la tienda los corrija; la conformidad estará disponible cuando se carguen de nuevo.</span></div>
    @else
      <p class="fs-13 text-gris" style="margin-top:0">Revise los documentos exigidos por COFIDE. Al dar conformidad, los documentos presentados quedan conformes
        {{ $nivelRevision === 'zona' ? 'y el expediente pasa a revisión central.' : 'y el expediente queda conforme.' }}
        Para observar, marque primero como «Observado» los documentos que debe corregir la tienda.</p>
    @endif
    <form method="POST" action="{{ route('gobierno.revision.expediente', $e) }}">
      @csrf
      <div class="field">
        <label for="comentario-rev">Comentario {{ $hayObservados ? '(obligatorio)' : '(opcional para la conformidad)' }}</label>
        <textarea id="comentario-rev" name="comentario" rows="2" {{ $hayObservados ? 'required' : '' }} placeholder="Comentario para el historial y la tienda">{{ old('comentario') }}</textarea>
      </div>
      <div class="actions">
        @if($hayObservados)
          <button name="resultado" value="observado" class="btn btn-warning"><i class="bi bi-arrow-return-left"></i> Registrar observación</button>
        @else
          <button name="resultado" value="conforme" class="btn btn-success" data-confirm="¿Dar conformidad {{ $nivelRevision === 'zona' ? 'de zona' : 'central' }} al expediente {{ $e->id_externo }}?">
            <i class="bi bi-check2-circle"></i> Dar conformidad {{ $nivelRevision === 'zona' ? 'de zona' : 'central' }}</button>
        @endif
      </div>
    </form>
  </div>
@endif

<div class="grid2" style="margin-bottom:20px">
  <div class="card">
    <div class="card-title"><i class="bi bi-clock-history"></i> Historial de revisiones</div>
    @if($e->revisiones->count())
      <div class="timeline">
        @foreach($e->revisiones as $r)
          <div class="timeline-item {{ $r->resultado === 'conforme' ? 'ok' : 'warn' }}">
            <div class="timeline-title">Revisión {{ $r->nivel === 'zona' ? 'por zona' : 'central' }}: {{ $r->resultado === 'conforme' ? 'conforme' : 'observado' }}</div>
            <div class="timeline-meta">{{ $r->revisado_en->format('d/m/Y H:i') }} · {{ $r->usuario->name ?? '—' }}{{ $r->usuario?->rol ? ' · '.$r->usuario->rol->nombre : '' }}{{ $r->origen === 'excel' ? ' · registrada en el Excel' : '' }}</div>
            @if($r->comentario)<div class="timeline-body text-gris">{{ $r->comentario }}</div>@endif
          </div>
        @endforeach
      </div>
    @else
      <div class="empty-state" style="padding:18px"><span>Aún no tiene revisiones.</span></div>
    @endif
  </div>
  <div class="card">
    <div class="card-title"><i class="bi bi-archive"></i> Documentos reemplazados</div>
    @forelse($e->historialDocumentos as $h)
      <div class="d-flex justify-content-between align-items-center gap-2" style="padding:7px 0;border-bottom:1px solid #eef0f2">
        <div style="min-width:0"><div class="fs-13 fw-600">{{ $h->tipo->nombre_corto ?? $h->tipo_doc }}</div>
          <div class="cell-sub">{{ $origenes[$h->origen] ?? '' }} · {{ \App\Services\Honramiento\Checklist::ETIQUETAS[$h->estado] ?? $h->estado }} · {{ $h->cargado_en?->format('d/m/Y') }}</div></div>
        @if($h->tieneArchivo())<a href="{{ route('gobierno.documento.descargar', $h) }}" class="btn btn-ghost btn-sm btn-icon" title="Descargar"><i class="bi bi-download"></i></a>@endif
      </div>
    @empty
      <div class="empty-state" style="padding:18px"><span>Sin documentos reemplazados.</span></div>
    @endforelse
  </div>
</div>

<div class="actions actions-start">
  <a href="{{ route('gobierno.index') }}" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Volver a la bandeja</a>
</div>

{{-- Observar documento --}}
<div class="rct-modal" id="modal-obs" role="dialog" aria-labelledby="obs-titulo">
  <div class="rct-modal-box" style="width:min(520px,100%)">
    <form method="POST" id="form-obs">
      @csrf
      <input type="hidden" name="resultado" value="observado">
      <div class="rct-modal-head"><h3 class="rct-modal-title" id="obs-titulo">Observar documento</h3>
        <button type="button" class="rct-modal-close" data-modal-close aria-label="Cerrar"><i class="bi bi-x-lg"></i></button></div>
      <div class="rct-modal-body">
        <p class="fs-13" style="margin-top:0">Documento: <strong id="obs-nombre"></strong></p>
        <div class="field">
          <label for="obs-comentario">Qué debe corregir la tienda <span class="req">*</span></label>
          <textarea id="obs-comentario" name="comentario" rows="3" required placeholder="Ej.: el pagaré no está firmado por el titular."></textarea>
        </div>
      </div>
      <div class="rct-modal-foot actions" style="margin-top:0">
        <button type="button" class="btn btn-secondary" data-modal-close>Cancelar</button>
        <button type="submit" class="btn btn-warning"><i class="bi bi-exclamation-lg"></i> Observar documento</button>
      </div>
    </form>
  </div>
</div>

{{-- Declarar documento no disponible --}}
<div class="rct-modal" id="modal-nd" role="dialog" aria-labelledby="nd-titulo">
  <div class="rct-modal-box" style="width:min(520px,100%)">
    <form method="POST" action="{{ route('gobierno.no-disponible', $e) }}">
      @csrf
      <input type="hidden" name="tipo_documento_id" id="nd-tipo">
      <div class="rct-modal-head"><h3 class="rct-modal-title" id="nd-titulo">Documento no disponible</h3>
        <button type="button" class="rct-modal-close" data-modal-close aria-label="Cerrar"><i class="bi bi-x-lg"></i></button></div>
      <div class="rct-modal-body">
        <p class="fs-13" style="margin-top:0">Declara que la tienda no cuenta con <strong id="nd-nombre"></strong> después de realizar las búsquedas.
          El expediente quedará como <strong>Incompleto definitivo</strong> si no tiene otros faltantes.</p>
        <div class="field">
          <label for="nd-motivo">Búsquedas realizadas y motivo <span class="req">*</span></label>
          <textarea id="nd-motivo" name="motivo" rows="3" required minlength="10" placeholder="Ej.: se revisó el file físico y el archivo de la tienda; el documento no fue firmado en el desembolso."></textarea>
        </div>
      </div>
      <div class="rct-modal-foot actions" style="margin-top:0">
        <button type="button" class="btn btn-secondary" data-modal-close>Cancelar</button>
        <button type="submit" class="btn btn-danger"><i class="bi bi-slash-circle"></i> Declarar no disponible</button>
      </div>
    </form>
  </div>
</div>
@endsection

@push('scripts')
<script>
document.querySelectorAll('[data-doc-url]').forEach(function (b) {
  b.addEventListener('click', function () {
    document.getElementById('form-obs').action = b.dataset.docUrl;
    document.getElementById('obs-nombre').textContent = b.dataset.docNombre;
  });
});
document.querySelectorAll('[data-tipo-id]').forEach(function (b) {
  b.addEventListener('click', function () {
    document.getElementById('nd-tipo').value = b.dataset.tipoId;
    document.getElementById('nd-nombre').textContent = b.dataset.tipoNombre;
  });
});
// Tras la validación automática se habilita "Guardar documento"
document.addEventListener('rct:validado', function (ev) {
  var slot = ev.target.closest('.doc-slot');
  var btn = slot && slot.querySelector('[data-guardar]');
  if (btn) btn.style.display = slot.querySelector('input[type=file]').files.length ? '' : 'none';
});
</script>
@endpush
