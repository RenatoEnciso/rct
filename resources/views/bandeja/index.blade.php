@extends('layouts.app')
@section('title', 'Bandeja de Aprobación')

@section('content')

<div class="page-head">
  <div class="page-head-text">
    <h2>Bandeja de Aprobación</h2>
    <p>FUDs pendientes de revisión para su nivel de autonomía.</p>
  </div>
  <div style="display:flex;gap:8px;flex-wrap:wrap">
    <span class="badge badge-info">
      <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/>
      </svg>
      Nivel {{ auth()->user()->nivel() }} · {{ auth()->user()->rol->nombre }}
    </span>
    <span class="badge {{ $pendientes->count() ? 'badge-danger' : 'badge-ok' }}">
      {{ $pendientes->count() }} pendientes
    </span>
  </div>
</div>

@php
  $avisos = [
    1 => 'Como <strong>Coordinador Zonal</strong> puedes aprobar: conciliaciones de intereses (cartera vencida no hipotecaria), cancelaciones totales Deficiente/Dudoso/Pérdida con SK &lt; 3 UITs.',
    2 => 'Como <strong>Jefe de Recuperaciones</strong> puedes aprobar todos los casos de nivel 1 más: cancelaciones con SK &gt; 3 UITs y excedentes del límite de 4 conciliaciones.',
    3 => 'Como <strong>Gerente de División</strong> tienes visibilidad total y autorización de casos escalados.',
  ];
@endphp
<div class="notice">{!! $avisos[auth()->user()->nivel()] ?? '' !!}</div>

<div class="card">
  <div class="card-title">
    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
      <circle cx="12" cy="12" r="10"/><polyline points="9 12 11 14 15 10"/>
    </svg>
    FUDs pendientes de decisión
  </div>

  <div style="overflow-x:auto">
    <table>
      <thead>
        <tr>
          <th>Fecha</th><th>Enviado por</th><th>Cliente</th><th>Operación</th>
          <th>Saldo Capital</th><th>Calificación</th><th>Tipo</th><th>Versión</th><th>Acción</th>
        </tr>
      </thead>
      <tbody>
        @forelse($pendientes as $p)
          <tr>
            <td>{{ $p->fecha_envio->format('d/m/Y') }}</td>
            <td style="font-size:12px">{{ $p->enviador->name }}</td>
            <td>
              <strong>{{ $p->cliente->nombre_cliente }}</strong><br>
              <span style="font-size:11px;color:#999">{{ $p->cliente->cuenta }}</span>
            </td>
            <td>{{ $p->cliente->operacion }}</td>
            <td><strong>{{ $p->cliente->saldo_formateado }}</strong></td>
            <td><span class="badge badge-info">{{ $p->cliente->calificacion_rcc }}</span></td>
            <td style="font-size:12px">{{ $p->tipo_negociacion_desc }}</td>
            <td>
              @if($p->veces_observado > 0)
                <span class="badge badge-mostaza">
                  Corrección #{{ $p->veces_observado }}
                </span>
              @else
                <span class="badge badge-gray">1er envío</span>
              @endif
            </td>
            <td>
              <button class="btn btn-primary btn-sm" onclick="abrirModal({{ $p->id }})">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                </svg>
                Revisar
              </button>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="9" style="text-align:center;padding:32px;color:#999">
              <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#ccc" stroke-width="1.5"
                   style="display:block;margin:0 auto 10px">
                <circle cx="12" cy="12" r="10"/><polyline points="9 12 11 14 15 10"/>
              </svg>
              No hay FUDs pendientes en tu bandeja. ✓
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

{{-- MODALES de decisión --}}
@foreach($pendientes as $p)
<div id="modal-{{ $p->id }}" class="modal">
  <div class="modal-box" style="width:min(700px,96vw)">
    <div class="modal-head">
      <h3 class="modal-title">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
        </svg>
        Revisar FUD · {{ $p->cliente->nombre_cliente }}
      </h3>
      <button class="modal-close" onclick="cerrarModal({{ $p->id }})">×</button>
    </div>

    {{-- Datos principales --}}
    <div class="grid2" style="font-size:13px;margin-bottom:16px;gap:10px">
      @foreach([
        'Cliente'          => $p->cliente->nombre_cliente,
        'Operación'        => $p->cliente->operacion,
        'Saldo Capital'    => $p->cliente->saldo_formateado,
        'Días de atraso'   => $p->cliente->dias_atraso . ' días',
        'Calificación'     => $p->cliente->calificacion_rcc,
        'Estado crédito'   => $p->cliente->estado_cartera,
        'Tipo negociación' => $p->tipo_negociacion_desc,
        'Enviado por'      => $p->enviador->name,
      ] as $label => $val)
        <div>
          <div class="detail-label">{{ $label }}</div>
          <div class="detail-value">{{ $val }}</div>
        </div>
      @endforeach
    </div>

    {{-- Indicador de corrección --}}
    @if($p->veces_observado > 0)
      <div style="background:#fff8e7;border-left:4px solid var(--mostaza);
                  padding:10px 14px;border-radius:0 8px 8px 0;font-size:12px;margin-bottom:14px">
        <strong>⚠ Esta es la corrección #{{ $p->veces_observado }}</strong> de este FUD.
        El ejecutivo corrigió y re-envió tras una observación anterior.
      </div>
    @endif

    {{-- Motivo --}}
    <div style="background:#fafafa;border-radius:8px;padding:12px;margin-bottom:14px">
      <div class="detail-label" style="margin-bottom:6px">Motivo / Sustento del ejecutivo</div>
      <p style="margin:0;font-size:13px;color:#333;line-height:1.6">{{ $p->motivo }}</p>
    </div>

    {{-- Adjuntos del ejecutivo --}}
    @if($p->adjuntos->count())
      <div style="margin-bottom:14px">
        <div class="detail-label" style="margin-bottom:8px">
          Archivos adjuntos ({{ $p->adjuntos->count() }})
        </div>
        <div style="display:flex;flex-wrap:wrap;gap:6px">
          @foreach($p->adjuntos as $adj)
            <div style="display:flex;align-items:center;gap:5px;padding:5px 10px;
                        background:#f0f0f0;border-radius:7px;font-size:11px">
              <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="var(--rojo)" stroke-width="2">
                <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
                <polyline points="14 2 14 8 20 8"/>
              </svg>
              {{ $adj->nombre_archivo }}
            </div>
          @endforeach
        </div>
      </div>
    @endif

    {{-- Nivel requerido --}}
    <div style="background:var(--mostaza-soft);border-left:4px solid var(--mostaza);
                padding:10px 14px;border-radius:0 8px 8px 0;margin-bottom:20px;font-size:12px">
      <strong>Beneficio solicitado:</strong> Nivel {{ $p->nivel_autonomia_req }}
      — Aprobador: {{ $p->aprobador->name ?? '—' }}
    </div>

    {{-- Formulario decisión --}}
    <form method="POST" action="{{ route('bandeja.decidir', $p) }}"
          enctype="multipart/form-data">
      @csrf

      <div class="field" style="margin-bottom:14px">
        <label>Comentario del aprobador (obligatorio al observar o rechazar)</label>
        <textarea name="comentario" rows="3"
          placeholder="Observaciones, condiciones, documentos faltantes, etc."></textarea>
      </div>

      {{-- Adjuntos del aprobador --}}
      <div class="field" style="margin-bottom:20px">
        <label>Adjuntar archivos (opcional)</label>
        <input type="file" name="adjuntos_aprobador[]" multiple
               accept=".pdf,.jpg,.jpeg,.png,.xlsx,.docx"
               style="margin-bottom:4px">
        <span style="font-size:11px;color:#999">PDF, imagen, Excel o Word. Máx 10MB por archivo.</span>
      </div>

      <div style="display:grid;gap:8px">
        <button type="submit" name="decision" value="Aprobado"
          class="btn btn-success btn-block" style="padding:13px;font-size:14px">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <circle cx="12" cy="12" r="10"/><polyline points="9 12 11 14 15 10"/>
          </svg>
          APROBAR E INGRESAR EN SISTEMA
        </button>
        <button type="submit" name="decision" value="Observado"
          class="btn btn-secondary btn-block" style="padding:11px;font-size:13px">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="10"/>
            <line x1="12" y1="8" x2="12" y2="12"/>
            <line x1="12" y1="16" x2="12.01" y2="16"/>
          </svg>
          OBSERVAR (devolver para corrección)
        </button>
        <button type="submit" name="decision" value="Rechazado"
          class="btn btn-danger btn-block" style="padding:11px;font-size:13px">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="10"/>
            <line x1="15" y1="9" x2="9" y2="15"/>
            <line x1="9" y1="9" x2="15" y2="15"/>
          </svg>
          RECHAZAR DEFINITIVAMENTE
        </button>
      </div>
    </form>
  </div>
</div>
@endforeach

@endsection

@push('scripts')
<script>
function abrirModal(id)  { document.getElementById('modal-' + id).classList.add('show'); }
function cerrarModal(id) { document.getElementById('modal-' + id).classList.remove('show'); }

// Cerrar modal con Escape
document.addEventListener('keydown', e => {
  if (e.key === 'Escape') {
    document.querySelectorAll('.modal.show')
      .forEach(m => m.classList.remove('show'));
  }
});
</script>
@endpush