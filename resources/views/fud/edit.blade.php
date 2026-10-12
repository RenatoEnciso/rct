@extends('layouts.app')
@section('title', 'Corregir propuesta')

@section('content')
@php
  $dc   = $fud->detalleCuotas;
  $dcan = $fud->detalleCancelacion;
  $dj   = $fud->detalleJudicial;
  $apr  = $fud->aprobadoPor ?? $fud->aprobador;
  $tipos = [
    'cuotas' => 'Pago de Cuotas', 'cancelacion' => 'Cancelación Total',
    'extrajudicial' => 'Cartera Extrajudicial / Judicial', 'castigada' => 'Cartera Castigada', 'focmac' => 'Cartera FOCMAC',
  ];
  $tipoActual = old('tipo_negociacion', $fud->tipo_negociacion);
  $num = fn($v) => $v === null ? '' : rtrim(rtrim((string) $v, '0'), '.');
@endphp

<div class="page-head">
  <div>
    <div class="page-eyebrow"><a href="{{ route('fud.index') }}">Mis propuestas</a> / <a href="{{ route('fud.show', $fud) }}">FUD-{{ str_pad($fud->id, 5, '0', STR_PAD_LEFT) }}</a> / Corrección</div>
    <h2>Corregir propuesta</h2>
    <p>{{ $fud->cliente->nombre_cliente }} · Operación {{ $fud->cliente->operacion }}</p>
  </div>
  <div class="page-head-actions">
    <span class="badge badge-lg badge-pending"><i class="bi bi-exclamation-circle"></i> Observado</span>
  </div>
</div>

<div class="notice-warning">
  <i class="bi bi-chat-left-text"></i>
  <div>
    <strong>Observación del aprobador:</strong> {{ $fud->comentario_aprobador }}
    <div class="fs-12" style="margin-top:2px">{{ $apr->name ?? '—' }} · {{ $fud->fecha_decision?->format('d/m/Y') }}</div>
  </div>
</div>

<form method="POST" action="{{ route('fud.update', $fud) }}" id="form-fud" enctype="multipart/form-data">
  @csrf @method('PUT')
  <input type="hidden" name="cliente_cartera_id" value="{{ $fud->cliente_cartera_id }}">
  <input type="hidden" name="tipo_negociacion_desc" id="input-tipo-desc" value="{{ $tipos[$tipoActual] ?? $fud->tipo_negociacion_desc }}">

  <div class="card">
    <div class="card-title"><i class="bi bi-file-earmark-text"></i> Propuesta</div>
    <div class="notice"><i class="bi bi-info-circle"></i>
      <span>La corrección se registra como una nueva versión. El historial completo queda disponible para el aprobador.</span></div>

    <div class="grid3">
      <div class="field">
        <label for="tipo-negociacion">Tipo de negociación</label>
        <select name="tipo_negociacion" id="tipo-negociacion" required>
          @foreach($tipos as $val => $label)
            <option value="{{ $val }}" @selected($tipoActual === $val)>{{ $label }}</option>
          @endforeach
        </select>
      </div>
      <div class="field">
        <label>Aprobador asignado</label>
        <div class="readonly-value"><i class="bi bi-person-check text-gris"></i>{{ $apr->name ?? '—' }}
          @if($apr)<span class="cell-sub">· {{ $apr->rol->nombre }}</span>@endif</div>
        <span class="field-hint">La corrección vuelve a quien observó la propuesta.</span>
      </div>
      <div class="field">
        <label for="tipo_operacion">Tipo de operación</label>
        <select name="tipo_operacion" id="tipo_operacion">
          @foreach(['CAMPAÑA' => 'Campaña', 'ORDINARIA' => 'Ordinaria', 'ESPECIAL' => 'Especial'] as $op => $lbl)
            <option value="{{ $op }}" @selected(old('tipo_operacion', $fud->tipo_operacion) === $op)>{{ $lbl }}</option>
          @endforeach
        </select>
      </div>
    </div>

    <div id="panel-cuotas" class="form-section" data-panel="cuotas">
      <div class="form-section-title">Condiciones · Pago de cuotas</div>
      <div class="grid3">
        <div class="field"><label>Cuota desde</label><input type="number" name="cuota_desde" min="1" value="{{ old('cuota_desde', $dc->cuota_desde ?? '') }}"></div>
        <div class="field"><label>Cuota hasta</label><input type="number" name="cuota_hasta" min="1" value="{{ old('cuota_hasta', $dc->cuota_hasta ?? '') }}"></div>
        <div class="field"><label>Descuento de intereses (%)</label><input type="number" name="desc_interes" min="0" max="100" step="0.01" value="{{ old('desc_interes', $num($dc->desc_interes ?? null)) }}"></div>
        <div class="field"><label>Descuento de moratorio (%)</label><input type="number" name="desc_moratorio" min="0" max="100" value="{{ old('desc_moratorio', $num($dc->desc_moratorio ?? 100)) }}"></div>
        <div class="field"><label>¿Pone el crédito al día?</label>
          <select name="pone_al_dia">
            <option value="0" @selected(old('pone_al_dia', (int) ($dc->pone_al_dia ?? 0)) == 0)>No</option>
            <option value="1" @selected(old('pone_al_dia', (int) ($dc->pone_al_dia ?? 0)) == 1)>Sí</option>
          </select></div>
        <div class="field"><label>¿Cierra el mes en situación vigente?</label>
          <select name="cierra_mes_vigente">
            <option value="1" @selected(old('cierra_mes_vigente', (int) ($dc->cierra_mes_vigente ?? 1)) == 1)>Sí</option>
            <option value="0" @selected(old('cierra_mes_vigente', (int) ($dc->cierra_mes_vigente ?? 1)) == 0)>No</option>
          </select></div>
      </div>
    </div>

    <div id="panel-cancelacion" class="form-section" data-panel="cancelacion">
      <div class="form-section-title">Condiciones · Cancelación total</div>
      <div class="grid3">
        <div class="field"><label>Monto a pagar (S/)</label><input type="number" name="monto_a_pagar" min="0" step="0.01" value="{{ old('monto_a_pagar', $dcan->monto_a_pagar ?? '') }}"></div>
        <div class="field"><label>Descuento de intereses (%)</label><input type="number" name="desc_interes_canc" min="0" max="100" step="0.01" value="{{ old('desc_interes_canc', $num($dcan->desc_interes ?? null)) }}"></div>
        <div class="field"><label>Descuento de capital (%)</label><input type="number" name="desc_capital" min="0" max="100" step="0.01" value="{{ old('desc_capital', $num($dcan->desc_capital ?? null)) }}"></div>
        <div class="field"><label>Fecha límite de pago</label><input type="date" name="fecha_limite" value="{{ old('fecha_limite', $dcan?->fecha_limite?->format('Y-m-d')) }}"></div>
        <div class="field"><label>Saldo capital mayor a 3 UIT</label>
          <select name="mayor_tres_uit">
            <option value="0" @selected(old('mayor_tres_uit', (int) ($dcan->mayor_tres_uit ?? 0)) == 0)>No</option>
            <option value="1" @selected(old('mayor_tres_uit', (int) ($dcan->mayor_tres_uit ?? 0)) == 1)>Sí — requiere Informe-Propuesta</option>
          </select></div>
        <div class="field"><label>Búsqueda registral (SUNARP)</label>
          <select name="sunarp_positivo">
            <option value="0" @selected(old('sunarp_positivo', (int) ($dcan->sunarp_positivo ?? 0)) == 0)>Negativa</option>
            <option value="1" @selected(old('sunarp_positivo', (int) ($dcan->sunarp_positivo ?? 0)) == 1)>Positiva — requiere informe</option>
          </select></div>
      </div>
    </div>

    <div id="panel-judicial" class="form-section" data-panel="judicial">
      <div class="form-section-title">Condiciones · Cartera judicial, castigada o FOCMAC</div>
      <div class="grid3">
        <div class="field"><label>Monto del gravamen / embargo (S/)</label><input type="number" name="monto_gravamen" min="0" step="0.01" value="{{ old('monto_gravamen', $dj->monto_gravamen ?? '') }}"></div>
        <div class="field"><label>Etapa procesal</label>
          <select name="etapa_procesal">
            @foreach(['Pre-judicial','Ejecución de garantías','Medidas cautelares','Etapa de remate'] as $etapa)
              <option @selected(old('etapa_procesal', $dj->etapa_procesal ?? '') === $etapa)>{{ $etapa }}</option>
            @endforeach
          </select></div>
        <div class="field"><label>Beneficio propuesto sobre SK (%)</label><input type="number" name="beneficio_pct_sk" min="0" max="100" step="0.01" value="{{ old('beneficio_pct_sk', $num($dj->beneficio_pct_sk ?? null)) }}"></div>
      </div>
    </div>

    <div class="form-section">
      <div class="field">
        <label for="motivo">Sustento de la propuesta <span class="req">*</span></label>
        <textarea id="motivo" name="motivo" rows="5" required minlength="20">{{ old('motivo', $fud->motivo) }}</textarea>
        <span class="field-hint">Indique cómo se atendió la observación del aprobador.</span>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-title"><i class="bi bi-paperclip"></i> Documentos adjuntos</div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Archivo</th><th>Tipo</th><th>Cargado por</th><th>Fecha</th><th>Tamaño</th><th class="num"></th></tr></thead>
        <tbody>
          @forelse($fud->adjuntos as $adj)
            <tr id="fila-adj-{{ $adj->id }}">
              <td><span class="file-chip"><i class="bi bi-file-earmark"></i><span>{{ $adj->nombre_archivo }}</span></span>
                <input type="hidden" name="adjuntos_eliminar[]" id="eliminar-{{ $adj->id }}" value="{{ $adj->id }}" disabled></td>
              <td>{{ $adj->tipo_adjunto }}</td>
              <td>{{ $adj->subidor->name ?? '—' }}</td>
              <td class="nowrap">{{ \Carbon\Carbon::parse($adj->created_at)->format('d/m/Y') }}</td>
              <td>{{ $adj->size_formateado }}</td>
              <td class="num"><button type="button" class="btn btn-ghost btn-sm" onclick="alternarAdjunto({{ $adj->id }}, this)">Quitar</button></td>
            </tr>
          @empty
            <tr data-empty><td colspan="6" class="empty-state" style="padding:20px"><span>La versión anterior no tiene adjuntos.</span></td></tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="grid3 form-section">
      <div class="field span-2">
        <label for="input-adjuntos">Agregar archivos</label>
        <input type="file" id="input-adjuntos" name="adjuntos[]" multiple accept=".pdf,.jpg,.jpeg,.png,.xlsx,.docx" class="input-rct">
        <span class="field-hint">PDF, imagen, Excel o Word · máximo 10 MB por archivo.</span>
      </div>
      <div class="field">
        <label for="tipo_adjunto_nuevo">Tipo de documento</label>
        <select id="tipo_adjunto_nuevo" name="tipo_adjunto_nuevo">
          @foreach(['Evidencia negociación','Captura sistema','Documento cliente','Otro'] as $t)<option>{{ $t }}</option>@endforeach
        </select>
      </div>
    </div>
  </div>

  <div class="actions">
    <a href="{{ route('fud.show', $fud) }}" class="btn btn-secondary">Cancelar</a>
    <button type="submit" class="btn btn-primary"><i class="bi bi-send"></i> Enviar corrección</button>
  </div>
</form>
@endsection

@push('scripts')
<script>
(function () {
  const textos = @json($tipos);
  const sel = document.getElementById('tipo-negociacion');
  function actualizarPanel() {
    const t = sel.value;
    document.getElementById('input-tipo-desc').value = textos[t] ?? '';
    const panel = t === 'cuotas' ? 'cuotas' : t === 'cancelacion' ? 'cancelacion' : 'judicial';
    document.querySelectorAll('[data-panel]').forEach(p => p.hidden = p.dataset.panel !== panel);
  }
  sel.addEventListener('change', actualizarPanel);
  actualizarPanel();

  window.alternarAdjunto = function (id, btn) {
    const input = document.getElementById('eliminar-' + id);
    const fila = document.getElementById('fila-adj-' + id);
    input.disabled = !input.disabled;
    const quitado = !input.disabled;
    fila.style.opacity = quitado ? '.45' : '';
    fila.style.textDecoration = quitado ? 'line-through' : '';
    btn.textContent = quitado ? 'Restaurar' : 'Quitar';
  };

  document.getElementById('form-fud').addEventListener('submit', e => {
    const b = e.target.querySelector('button[type=submit]');
    b.disabled = true; b.innerHTML = '<span class="spinner"></span> Enviando…';
  });
})();
</script>
@endpush
