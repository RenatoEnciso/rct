@extends('layouts.app')
@section('title', 'Detalle de Revisión')

@section('content')

<div class="page-head">
  <div class="page-head-text">
    <h2>🔍 Revisión de Sustento</h2>
    <p>{{ $sustento->propuesta->cliente->nombre_cliente }} — {{ $sustento->propuesta->cliente->cuenta }}</p>
  </div>
</div>

{{-- Info general --}}
<div class="card" style="margin-bottom:16px">
  <div class="card-title">Información del expediente</div>
  <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:12px;font-size:13px">
    <div><span style="color:#6b7280;font-size:11px">Cliente</span><br><strong>{{ $sustento->propuesta->cliente->nombre_cliente }}</strong></div>
    <div><span style="color:#6b7280;font-size:11px">Cuenta</span><br>{{ $sustento->propuesta->cliente->cuenta }}</div>
    <div><span style="color:#6b7280;font-size:11px">Operación</span><br>{{ $sustento->propuesta->cliente->operacion }}</div>
    <div><span style="color:#6b7280;font-size:11px">Analista</span><br>{{ $sustento->cargador->name ?? '—' }}</div>
    <div><span style="color:#6b7280;font-size:11px">Fecha carga</span><br>{{ $sustento->fecha_carga?->format('d/m/Y') ?? '—' }}</div>
    <div><span style="color:#6b7280;font-size:11px">Estado actual</span><br>
      <span class="badge {{ $sustento->estado === 'Aprobado' ? 'badge-ok' : ($sustento->estado === 'Observado' ? 'badge-warn' : ($sustento->estado === 'Rechazado' ? 'badge-error' : 'badge-info')) }}">
        {{ $sustento->estado }}
      </span>
    </div>
  </div>

  @if($sustento->comentario_revision)
    <div style="margin-top:14px;padding:12px;background:#fffbeb;border:1px solid #fde68a;border-radius:8px;font-size:12px">
      <strong>Comentario anterior del revisor:</strong><br>
      {{ $sustento->comentario_revision }}
      <span style="color:#9ca3af;font-size:11px"> — {{ $sustento->revisador->name ?? '' }} {{ $sustento->fecha_revision?->format('d/m/Y H:i') }}</span>
    </div>
  @endif
</div>

{{-- Documentos --}}
<div class="card" style="margin-bottom:16px">
  <div class="card-title">Documentos cargados</div>
  <div style="display:flex;flex-direction:column;gap:12px;margin-top:8px">
    @foreach($sustento->documentos as $doc)
      @php
        $colorBorde = match($doc->estado_validacion) {
          'ok'    => '#22c55e',
          'warn'  => '#f59e0b',
          'error' => '#ef4444',
          default => '#d1d5db',
        };
        $bgDoc = match($doc->estado_validacion) {
          'ok'    => '#f0fdf4',
          'warn'  => '#fffbeb',
          'error' => '#fff5f5',
          default => '#f9fafb',
        };
      @endphp
      <div style="border:1px solid #e5e7eb;border-left:4px solid {{ $colorBorde }};
                  border-radius:9px;padding:14px;background:{{ $bgDoc }}">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px">
          <strong style="font-size:13px">{{ $doc->tipo_doc }}</strong>
          <div style="display:flex;gap:8px;align-items:center">
            @if($doc->estado_validacion === 'ok')
              <span class="badge badge-ok">✔ Válido</span>
            @elseif($doc->estado_validacion === 'warn')
              <span class="badge badge-warn">⚠ Advertencia</span>
            @elseif($doc->estado_validacion === 'error')
              <span class="badge badge-error">✖ Con errores</span>
            @endif
            <a href="{{ route('sustentos.documento.descargar', $doc->id) }}"
               class="btn btn-ghost" style="font-size:11px;padding:4px 10px">
              ⬇ Descargar
            </a>
          </div>
        </div>

        <div style="font-size:11px;color:#6b7280;margin-bottom:8px">
          📄 {{ $doc->nombre_archivo }}
          &nbsp;·&nbsp; {{ number_format($doc->size_bytes / 1024, 1) }} KB
        </div>

        {{-- Observaciones del sistema al momento de carga --}}
        @if($doc->observaciones_carga)
          <div style="margin-bottom:10px">
            <div style="font-size:11px;font-weight:600;color:#374151;margin-bottom:4px">
              Observaciones del sistema al cargar:
            </div>
            @foreach(is_array($doc->observaciones_carga) ? $doc->observaciones_carga : json_decode($doc->observaciones_carga, true) as $obs)
              <div style="display:flex;align-items:flex-start;gap:6px;padding:4px 8px;border-radius:5px;font-size:11px;margin-bottom:3px;
                          background:{{ $obs['t']==='ok' ? '#d1fae5' : ($obs['t']==='warn' ? '#fef3c7' : '#fee2e2') }};
                          color:{{ $obs['t']==='ok' ? '#065f46' : ($obs['t']==='warn' ? '#92400e' : '#991b1b') }}">
                <span>{{ $obs['t']==='ok' ? '✔' : ($obs['t']==='warn' ? '⚠' : '✖') }}</span>
                <span>{{ strip_tags($obs['tx']) }}</span>
              </div>
            @endforeach
          </div>
        @endif

        {{-- Comentario del revisor por documento --}}
        <div style="margin-top:8px">
          <label style="font-size:11px;font-weight:600;color:#374151">
            Comentario del revisor para este documento:
          </label>
          <textarea name="docs[{{ $doc->id }}][comentario]"
                    form="form-observar"
                    placeholder="Ej: El prefijo no corresponde, adjuntar versión firmada..."
                    style="width:100%;margin-top:4px;font-size:12px;padding:7px 10px;
                           border:1px solid #d1d5db;border-radius:6px;resize:vertical;min-height:50px">{{ $doc->comentario_revisor }}</textarea>
          <div style="display:flex;gap:8px;margin-top:6px">
            <label style="font-size:11px;display:flex;align-items:center;gap:4px;cursor:pointer">
              <input type="radio" name="docs[{{ $doc->id }}][estado_revision]"
                     form="form-observar" value="aprobado"
                     {{ $doc->estado_revision === 'aprobado' ? 'checked' : '' }}>
              <span style="color:#065f46;font-weight:600">✔ Aprobar este doc</span>
            </label>
            <label style="font-size:11px;display:flex;align-items:center;gap:4px;cursor:pointer">
              <input type="radio" name="docs[{{ $doc->id }}][estado_revision]"
                     form="form-observar" value="observado"
                     {{ $doc->estado_revision === 'observado' ? 'checked' : '' }}>
              <span style="color:#92400e;font-weight:600">⚠ Observar este doc</span>
            </label>
          </div>
          {{-- id oculto para el controller --}}
          <input type="hidden" name="docs[{{ $doc->id }}][id]" value="{{ $doc->id }}" form="form-observar">
        </div>
      </div>
    @endforeach
  </div>
</div>

{{-- Acciones finales --}}
@if($sustento->puedeRevisarse())
<div class="card">
  <div class="card-title">Decisión final</div>

  {{-- Form aprobar --}}
  <form action="{{ route('revision.aprobar', $sustento->id) }}" method="POST" style="margin-bottom:16px">
    @csrf
    <textarea name="comentario" placeholder="Comentario opcional al aprobar..."
              style="width:100%;font-size:12px;padding:8px 10px;border:1px solid #d1d5db;
                     border-radius:6px;resize:vertical;min-height:60px;margin-bottom:10px"></textarea>
    <button type="submit" class="btn btn-primary"
            onclick="return confirm('¿Confirma aprobar todos los sustentos?')">
      ✅ Aprobar todo
    </button>
  </form>

  <hr style="border:none;border-top:1px solid #e5e7eb;margin:16px 0">

  {{-- Form observar (usa los radios/textareas de arriba via form="form-observar") --}}
  <form id="form-observar" action="{{ route('revision.observar', $sustento->id) }}" method="POST">
    @csrf
    <textarea name="comentario" placeholder="Comentario general de la observación (obligatorio)..."
              style="width:100%;font-size:12px;padding:8px 10px;border:1px solid #d1d5db;
                     border-radius:6px;resize:vertical;min-height:60px;margin-bottom:10px"
              required></textarea>
    <button type="submit" class="btn" style="background:#fef3c7;color:#92400e;border:1px solid #fde68a;font-size:13px;padding:8px 16px"
            onclick="return confirm('¿Marcar como observado? El analista deberá corregir y recargar.')">
      ⚠️ Marcar como observado
    </button>
  </form>

  <hr style="border:none;border-top:1px solid #e5e7eb;margin:16px 0">

  {{-- Form rechazar --}}
  <form action="{{ route('revision.rechazar', $sustento->id) }}" method="POST">
    @csrf
    <textarea name="comentario" placeholder="Motivo del rechazo (obligatorio)..."
              style="width:100%;font-size:12px;padding:8px 10px;border:1px solid #d1d5db;
                     border-radius:6px;resize:vertical;min-height:60px;margin-bottom:10px"
              required></textarea>
    <button type="submit" class="btn" style="background:#fee2e2;color:#991b1b;border:1px solid #fca5a5;font-size:13px;padding:8px 16px"
            onclick="return confirm('¿Rechazar definitivamente este sustento? Esta acción no se puede deshacer.')">
      ❌ Rechazar
    </button>
  </form>
</div>
@else
<div class="notice" style="background:#f0fdf4;border-color:#6ee7b7;color:#065f46">
  Este sustento ya fue procesado el {{ $sustento->fecha_revision?->format('d/m/Y H:i') }}
  por {{ $sustento->revisador->name ?? '—' }}.
</div>
@endif

<div style="margin-top:16px">
  <a href="{{ route('revision.index') }}" class="btn btn-ghost">← Volver a la lista</a>
</div>

@endsection