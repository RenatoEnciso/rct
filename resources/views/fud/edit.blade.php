{{-- resources/views/fud/edit.blade.php --}}
@extends('layouts.app')
@section('title', 'Editar FUD')

@push('styles')
<style>
.tabla-beneficios { width:100%;border-collapse:collapse;font-size:12px;margin-top:10px }
.tabla-beneficios th { background:#f8f9fa;color:#333;font-weight:700;padding:8px 10px;border:1px solid #e8e8e8;font-size:11px }
.tabla-beneficios td { padding:8px 10px;border:1px solid #e8e8e8 }
</style>
@endpush

@section('content')

<div class="page-head">
  <div class="page-head-text">
    <h2>✏️ Editar Propuesta FUD</h2>
    <p>{{ $fud->cliente->nombre_cliente }} · Operación {{ $fud->cliente->operacion }}</p>
  </div>
  <div style="display:flex;gap:8px;align-items:center">
    <span class="badge badge-danger" style="font-size:13px;padding:6px 14px">
      {{ $fud->estado_fud }}
      @if($fud->veces_observado > 0) · Observado {{ $fud->veces_observado }}x @endif
    </span>
  </div>
</div>

{{-- Aviso si viene de observación --}}
@if($fud->estaObservado())
  <div class="notice-danger">
    <div style="display:flex;align-items:flex-start;gap:10px">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
           stroke-width="2" style="flex-shrink:0;margin-top:1px">
        <circle cx="12" cy="12" r="10"/>
        <line x1="12" y1="8" x2="12" y2="12"/>
        <line x1="12" y1="16" x2="12.01" y2="16"/>
      </svg>
      <div>
        <strong>Motivo de la observación:</strong><br>
        <span style="font-size:13px">{{ $fud->comentario_aprobador }}</span><br>
        <span style="font-size:11px;color:#888;margin-top:4px;display:block">
          Observado por {{ $fud->aprobadoPor->name ?? '—' }}
          el {{ $fud->fecha_decision?->format('d/m/Y') }}
        </span>
      </div>
    </div>
  </div>
@endif

<div class="notice">
  <strong>ℹ Se creará una nueva versión del FUD.</strong>
  El historial completo quedará guardado y el aprobador podrá ver todos los envíos anteriores.
</div>

<form method="POST" action="{{ route('fud.update', $fud) }}"
      id="form-fud" enctype="multipart/form-data">
  @csrf @method('PUT')

  <input type="hidden" name="cliente_cartera_id" value="{{ $fud->cliente_cartera_id }}">
  <input type="hidden" name="tipo_negociacion_desc" id="input-tipo-desc"
         value="{{ $fud->tipo_negociacion_desc }}">

  {{-- DATOS CLIENTE (readonly) --}}
  <div class="card">
    <div class="card-title">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/>
      </svg>
      Datos del cliente (no modificables)
    </div>
    <div class="grid3">
      @foreach([
        'Cliente'        => $fud->cliente->nombre_cliente,
        'Nro. Cuenta'    => $fud->cliente->cuenta,
        'Nro. Operación' => $fud->cliente->operacion,
        'Saldo Capital'  => $fud->cliente->saldo_formateado,
        'Días de atraso' => $fud->cliente->dias_atraso . ' días',
        'Calificación'   => $fud->cliente->calificacion_rcc,
        'Tipo crédito'   => $fud->cliente->tipo_credito,
        'Estado'         => $fud->cliente->estado_cartera,
        'Veces conciliadas (12m)' => $fud->cliente->veces_conciliado . ' veces',
      ] as $label => $val)
        <div class="field">
          <label>{{ $label }}</label>
          <input readonly value="{{ $val }}">
        </div>
      @endforeach
    </div>
  </div>

  {{-- PROPUESTA --}}
  <div class="card">
    <div class="card-title">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
        <polyline points="14 2 14 8 20 8"/>
      </svg>
      Propuesta de exoneración
    </div>

    <div class="grid3">
      <div class="field">
        <label>Tipo de negociación</label>
        <select name="tipo_negociacion" id="tipo-negociacion" onchange="actualizarPanel()" required>
          <option value="">Seleccione</option>
          @foreach([
            'cuotas'       => 'Pago de Cuotas',
            'cancelacion'  => 'Cancelación Total',
            'extrajudicial'=> 'Cartera Extrajudicial / Judicial',
            'castigada'    => 'Cartera Castigada',
            'focmac'       => 'Cartera FOCMAC',
          ] as $val => $label)
            <option value="{{ $val }}"
              {{ old('tipo_negociacion', $fud->tipo_negociacion) === $val ? 'selected' : '' }}>
              {{ $label }}
            </option>
          @endforeach
        </select>
      </div>
      <div class="field">
        <label>Aprobador asignado</label>
        <select name="aprobador_id" required>
          @foreach($aprobadores as $apr)
            <option value="{{ $apr->id }}"
              {{ old('aprobador_id', $fud->aprobador_id) == $apr->id ? 'selected' : '' }}>
              {{ $apr->name }} ({{ $apr->rol->nombre }})
            </option>
          @endforeach
        </select>
      </div>
      <div class="field">
        <label>Tipo Operación</label>
        <select name="tipo_operacion">
          @foreach(['CAMPAÑA','ORDINARIA','ESPECIAL'] as $op)
            <option value="{{ $op }}"
              {{ old('tipo_operacion', $fud->tipo_operacion) === $op ? 'selected' : '' }}>
              {{ $op }}
            </option>
          @endforeach
        </select>
      </div>
    </div>

    {{-- Panel Cuotas --}}
    @php $dc = $fud->detalleCuotas; @endphp
    <div id="panel-cuotas" style="display:none;margin-top:16px">
      <div class="card-plain" style="background:#fafafa;border:1px solid #eee">
        <strong style="font-size:13px;color:var(--rojo)">Pago de Cuotas</strong>
        <div class="grid3" style="margin-top:12px">
          <div class="field">
            <label>Cuota desde</label>
            <input type="number" name="cuota_desde" min="1"
                   value="{{ old('cuota_desde', $dc->cuota_desde ?? '') }}"
                   placeholder="Ej: 3">
          </div>
          <div class="field">
            <label>Cuota hasta</label>
            <input type="number" name="cuota_hasta" min="1"
                   value="{{ old('cuota_hasta', $dc->cuota_hasta ?? '') }}"
                   placeholder="Ej: 5">
          </div>
          <div class="field">
            <label>% Descuento Interés</label>
            <input type="number" name="desc_interes" min="0" max="100"
                   value="{{ old('desc_interes', $dc->desc_interes ?? '') }}"
                   placeholder="%">
          </div>
          <div class="field">
            <label>% Descuento Moratorio</label>
            <input type="number" name="desc_moratorio" min="0" max="100"
                   value="{{ old('desc_moratorio', $dc->desc_moratorio ?? 100) }}">
          </div>
          <div class="field">
            <label>¿Pone crédito al día?</label>
            <select name="pone_al_dia">
              <option value="0" {{ old('pone_al_dia', $dc->pone_al_dia ?? 0) == 0 ? 'selected' : '' }}>NO</option>
              <option value="1" {{ old('pone_al_dia', $dc->pone_al_dia ?? 0) == 1 ? 'selected' : '' }}>SÍ – hasta 80% de interés</option>
            </select>
          </div>
          <div class="field">
            <label>¿Cierra mes en vigente?</label>
            <select name="cierra_mes_vigente">
              <option value="1" {{ old('cierra_mes_vigente', $dc->cierra_mes_vigente ?? 1) == 1 ? 'selected' : '' }}>SÍ</option>
              <option value="0" {{ old('cierra_mes_vigente', $dc->cierra_mes_vigente ?? 1) == 0 ? 'selected' : '' }}>NO</option>
            </select>
          </div>
        </div>
        <div class="notice-danger" style="margin-top:12px;font-size:12px">
          ⚠ El monto a conciliar <strong>no debe superar el 50%</strong> del valor de la cuota pactada.
        </div>
      </div>
    </div>

    {{-- Panel Cancelación --}}
    @php $dcan = $fud->detalleCancelacion; @endphp
    <div id="panel-cancelacion" style="display:none;margin-top:16px">
      <div class="card-plain" style="background:#fafafa;border:1px solid #eee">
        <strong style="font-size:13px;color:var(--rojo)">Cancelación Total</strong>
        <div class="grid3" style="margin-top:12px">
          <div class="field">
            <label>Monto a pagar S/</label>
            <input name="monto_a_pagar"
                   value="{{ old('monto_a_pagar', $dcan->monto_a_pagar ?? '') }}"
                   placeholder="S/ 0.00">
          </div>
          <div class="field">
            <label>% Descuento Interés</label>
            <input type="number" name="desc_interes_canc" min="0" max="100"
                   value="{{ old('desc_interes_canc', $dcan->desc_interes ?? '') }}"
                   placeholder="%">
          </div>
          <div class="field">
            <label>% Descuento Capital</label>
            <input type="number" name="desc_capital" min="0" max="50"
                   value="{{ old('desc_capital', $dcan->desc_capital ?? '') }}"
                   placeholder="%">
          </div>
          <div class="field">
            <label>Fecha límite de pago</label>
            <input type="date" name="fecha_limite"
                   value="{{ old('fecha_limite', $dcan->fecha_limite ?? '') }}">
          </div>
          <div class="field">
            <label>¿Saldo capital > 3 UIT?</label>
            <select name="mayor_tres_uit">
              <option value="0" {{ old('mayor_tres_uit', $dcan->mayor_tres_uit ?? 0) == 0 ? 'selected' : '' }}>NO</option>
              <option value="1" {{ old('mayor_tres_uit', $dcan->mayor_tres_uit ?? 0) == 1 ? 'selected' : '' }}>SÍ – adjuntar Informe-Propuesta</option>
            </select>
          </div>
          <div class="field">
            <label>¿Búsqueda registral SUNARP?</label>
            <select name="sunarp_positivo">
              <option value="0" {{ old('sunarp_positivo', $dcan->sunarp_positivo ?? 0) == 0 ? 'selected' : '' }}>Negativa</option>
              <option value="1" {{ old('sunarp_positivo', $dcan->sunarp_positivo ?? 0) == 1 ? 'selected' : '' }}>Positiva – adjuntar Informe</option>
            </select>
          </div>
        </div>
        <div class="notice" style="margin-top:12px;font-size:12px">
          Pago fraccionado: máx. 90 días desde primera amortización.
        </div>
      </div>
    </div>

    {{-- Panel Judicial --}}
    @php $dj = $fud->detalleJudicial; @endphp
    <div id="panel-judicial" style="display:none;margin-top:16px">
      <div class="card-plain" style="background:#fafafa;border:1px solid #eee">
        <strong style="font-size:13px;color:var(--rojo)">Cartera Extrajudicial / Judicial / Castigada / FOCMAC</strong>
        <div class="grid3" style="margin-top:12px">
          <div class="field">
            <label>Monto del Gravamen / Embargo</label>
            <input name="monto_gravamen"
                   value="{{ old('monto_gravamen', $dj->monto_gravamen ?? '') }}"
                   placeholder="S/ — si aplica">
          </div>
          <div class="field">
            <label>Etapa procesal</label>
            <select name="etapa_procesal">
              @foreach(['Pre-judicial','Ejecución de garantías','Medidas cautelares','Etapa de remate'] as $etapa)
                <option {{ old('etapa_procesal', $dj->etapa_procesal ?? '') === $etapa ? 'selected' : '' }}>
                  {{ $etapa }}
                </option>
              @endforeach
            </select>
          </div>
          <div class="field">
            <label>Beneficio propuesto (máx. 70% SK)</label>
            <input type="number" name="beneficio_pct_sk" max="70"
                   value="{{ old('beneficio_pct_sk', $dj->beneficio_pct_sk ?? '') }}"
                   placeholder="%">
          </div>
        </div>
        <div class="notice" style="margin-top:12px;font-size:12px">
          SK &lt; 3 UITs: autoriza CZR. SK &gt; 3 UITs: autoriza Jefe de Recuperaciones.
        </div>
      </div>
    </div>
  </div>

  {{-- MOTIVO --}}
  <div class="card">
    <div class="card-title">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/>
      </svg>
      Motivo / Sustento
    </div>
    <div class="field">
      <label>Motivo / Factor de mora (sustento obligatorio)</label>
      <textarea name="motivo" rows="5" required
        placeholder="Describa la situación del cliente: afectación económica, voluntad de pago, etc.">{{ old('motivo', $fud->motivo) }}</textarea>
      <span style="font-size:11px;color:#999;margin-top:4px">Mínimo 20 caracteres.</span>
    </div>
  </div>

{{-- ADJUNTOS --}}
{{-- ADJUNTOS --}}
<div class="card">
  <div class="card-title">
    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
      <path d="M21.44 11.05l-9.19 9.19a6 6 0 01-8.49-8.49l9.19-9.19a4 4 0 015.66 5.66l-9.2 9.19a2 2 0 01-2.83-2.83l8.49-8.48"/>
    </svg>
    Archivos adjuntos
    <span class="badge badge-gray" style="margin-left:6px" id="badge-count">{{ $fud->adjuntos->count() }}</span>
  </div>

  <div style="overflow-x:auto">
    <table id="tabla-adjuntos">
      <thead>
        <tr>
          <th>Archivo</th>
          <th>Tipo</th>
          <th>Subido por</th>
          <th>Fecha</th>
          <th>Tamaño</th>
          <th></th>
        </tr>
      </thead>
      <tbody id="tbody-adjuntos">

        {{-- Adjuntos existentes --}}
        @foreach($fud->adjuntos as $adj)
          <tr id="fila-adj-{{ $adj->id }}">
            <td>
              <div style="display:flex;align-items:center;gap:8px">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--rojo)" stroke-width="2">
                  <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
                  <polyline points="14 2 14 8 20 8"/>
                </svg>
                <span style="font-size:13px">{{ $adj->nombre_archivo }}</span>
              </div>
              {{-- input oculto para marcar eliminación --}}
              <input type="hidden" name="adjuntos_eliminar[]"
                     id="eliminar-{{ $adj->id }}" value="" disabled>
            </td>
            <td><span class="badge badge-info">{{ $adj->tipo_adjunto }}</span></td>
            <td style="font-size:12px">{{ $adj->subidor->name }}</td>
            <td style="font-size:12px;color:#888">
              {{ \Carbon\Carbon::parse($adj->created_at)->format('d/m/Y H:i') }}
            </td>
            <td style="font-size:12px;color:#888">{{ $adj->size_formateado }}</td>
            <td>
              <button type="button"
                onclick="descartarExistente({{ $adj->id }})"
                style="background:none;border:none;cursor:pointer;color:#c0392b;
                       font-size:18px;line-height:1;padding:0 4px"
                title="Quitar adjunto">×</button>
            </td>
          </tr>
        @endforeach

        {{-- Filas de nuevos archivos (insertadas por JS) --}}
      </tbody>
    </table>

    <p id="msg-sin-adjuntos" style="color:#999;font-size:13px;margin:8px 0 0;
       display:{{ $fud->adjuntos->count() ? 'none' : 'block' }}">
      No hay archivos adjuntos.
    </p>
  </div>

  {{-- Separador + formulario de agregar --}}
  <div style="margin-top:16px;padding-top:16px;border-top:1px solid var(--borde)">
    <strong style="font-size:13px;color:var(--rojo)">Agregar adjunto</strong>

    <div class="grid2" style="margin-top:10px;gap:10px;align-items:end">
      <div class="field" style="grid-column:span 1">
        <label style="font-size:11px;font-weight:700;text-transform:uppercase;
                      letter-spacing:.04em;color:#555">
          Archivo (PDF, imagen, Excel, Word — máx 10MB)
        </label>
        <input type="file" id="input-adjuntos" name="adjuntos[]" multiple
               accept=".pdf,.jpg,.jpeg,.png,.xlsx,.docx"
               style="width:100%;padding:7px 10px;border:1px solid var(--borde);
                      border-radius:7px;font-size:13px;background:#fff"
               onchange="agregarArchivos(this.files)">
      </div>
      <div class="field" style="grid-column:span 1">
        <label style="font-size:11px;font-weight:700;text-transform:uppercase;
                      letter-spacing:.04em;color:#555">
          Tipo de adjunto
        </label>
        <select name="tipo_adjunto_nuevo" id="select-tipo-adjunto">
          <option>Evidencia negociación</option>
          <option>Captura sistema</option>
          <option>Documento cliente</option>
          <option>Otro</option>
        </select>
      </div>
    </div>

    <div class="actions" style="margin-top:10px;justify-content:flex-end">
      <button type="button" class="btn btn-primary btn-sm"
              onclick="confirmarAgregarArchivo()">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M21.44 11.05l-9.19 9.19a6 6 0 01-8.49-8.49l9.19-9.19a4 4 0 015.66 5.66l-9.2 9.19a2 2 0 01-2.83-2.83l8.49-8.48"/>
        </svg>
        Adjuntar archivo
      </button>
    </div>
  </div>
</div>

  <div class="actions">
    <a href="{{ route('fud.show', $fud) }}" class="btn btn-ghost">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/>
      </svg>
      Cancelar
    </a>
    <button type="submit" class="btn btn-primary">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <line x1="22" y1="2" x2="11" y2="13"/>
        <polygon points="22 2 15 22 11 13 2 9 22 2"/>
      </svg>
      Enviar nueva versión
    </button>
  </div>

</form>
@endsection

@push('scripts')
<script>

// ── Adjuntos existentes: descartar ──────────────────────────
function descartarExistente(id) {
  const fila  = document.getElementById('fila-adj-' + id);
  const input = document.getElementById('eliminar-' + id);
  if (!fila || !input) return;
  // Tachado visual
  fila.style.opacity   = '.4';
  fila.style.textDecoration = 'line-through';
  // Activar el hidden para enviarlo con el form
  input.value    = id;
  input.disabled = false;
  // Reemplazar × por deshacer
  const btn = fila.querySelector('button[onclick]');
  if (btn) {
    btn.innerHTML  = '↩';
    btn.title      = 'Restaurar adjunto';
    btn.style.color = '#2980b9';
    btn.setAttribute('onclick', 'restaurarExistente(' + id + ')');
  }
  actualizarBadge();
}

function restaurarExistente(id) {
  const fila  = document.getElementById('fila-adj-' + id);
  const input = document.getElementById('eliminar-' + id);
  if (!fila || !input) return;
  fila.style.opacity        = '1';
  fila.style.textDecoration = '';
  input.value    = '';
  input.disabled = true;
  const btn = fila.querySelector('button[onclick]');
  if (btn) {
    btn.innerHTML  = '×';
    btn.title      = 'Quitar adjunto';
    btn.style.color = '#c0392b';
    btn.setAttribute('onclick', 'descartarExistente(' + id + ')');
  }
  actualizarBadge();
}

// ── Nuevos archivos ─────────────────────────────────────────
let archivosSeleccionados = new DataTransfer();

function agregarArchivos(nuevos) {
  for (const archivo of nuevos) {
    const yaExiste = Array.from(archivosSeleccionados.files)
      .some(f => f.name === archivo.name && f.size === archivo.size);
    if (!yaExiste) archivosSeleccionados.items.add(archivo);
  }
  document.getElementById('input-adjuntos').files = archivosSeleccionados.files;
  // No renderiza aún — renderiza al hacer clic en "Adjuntar archivo"
}

function confirmarAgregarArchivo() {
  // Simplemente fuerza re-render de la tabla con los archivos pendientes
  renderizarNuevos();
}

function quitarNuevo(nombre, size) {
  const nuevos = new DataTransfer();
  for (const f of archivosSeleccionados.files) {
    if (!(f.name === nombre && f.size === size)) nuevos.items.add(f);
  }
  archivosSeleccionados = nuevos;
  document.getElementById('input-adjuntos').files = archivosSeleccionados.files;
  renderizarNuevos();
}

function renderizarNuevos() {
  const tbody  = document.getElementById('tbody-adjuntos');
  // Eliminar filas previas de nuevos
  tbody.querySelectorAll('tr[data-nuevo]').forEach(r => r.remove());

  const archivos = Array.from(archivosSeleccionados.files);
  archivos.forEach(f => {
    const ext  = f.name.split('.').pop().toUpperCase();
    const kb   = f.size < 1024 * 1024
      ? (f.size / 1024).toFixed(1) + ' KB'
      : (f.size / 1024 / 1024).toFixed(1) + ' MB';
    const tr = document.createElement('tr');
    tr.setAttribute('data-nuevo', '1');
    tr.innerHTML = `
      <td>
        <div style="display:flex;align-items:center;gap:8px">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--rojo)" stroke-width="2">
            <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
            <polyline points="14 2 14 8 20 8"/>
          </svg>
          <span style="font-size:13px">${f.name}</span>
          <span class="badge badge-ok" style="font-size:10px">Nuevo</span>
        </div>
      </td>
      <td><span class="badge badge-info">${document.getElementById('select-tipo-adjunto').value}</span></td>
      <td style="font-size:12px;color:#aaa">—</td>
      <td style="font-size:12px;color:#aaa">Ahora</td>
      <td style="font-size:12px;color:#888">${kb}</td>
      <td>
        <button type="button"
          onclick="quitarNuevo('${f.name}', ${f.size})"
          style="background:none;border:none;cursor:pointer;color:#c0392b;
                 font-size:18px;line-height:1;padding:0 4px"
          title="Quitar">×</button>
      </td>`;
    tbody.appendChild(tr);
  });
  actualizarBadge();
}

function actualizarBadge() {
  const filas = document.getElementById('tbody-adjuntos').querySelectorAll('tr');
  const activas = Array.from(filas).filter(r => r.style.opacity !== '.4').length;
  document.getElementById('badge-count').textContent = activas;
  const msg = document.getElementById('msg-sin-adjuntos');
  if (msg) msg.style.display = activas === 0 ? 'block' : 'none';
}

// ── Panel según tipo ────────────────────────────────────────
const textos = {
  'cuotas':       'Pago de Cuotas (conciliación parcial intereses)',
  'cancelacion':  'Cancelación Total (conciliación intereses + capital)',
  'extrajudicial':'Cartera Extrajudicial / Judicial',
  'castigada':    'Cartera Castigada',
  'focmac':       'Cartera FOCMAC',
};

function actualizarPanel() {
  const tipo = document.getElementById('tipo-negociacion').value;
  document.getElementById('input-tipo-desc').value = textos[tipo] ?? '';
  ['panel-cuotas','panel-cancelacion','panel-judicial'].forEach(id => {
    document.getElementById(id).style.display = 'none';
  });
  if (tipo === 'cuotas')       document.getElementById('panel-cuotas').style.display      = 'block';
  if (tipo === 'cancelacion')  document.getElementById('panel-cancelacion').style.display = 'block';
  if (['extrajudicial','castigada','focmac'].includes(tipo))
    document.getElementById('panel-judicial').style.display = 'block';
}

actualizarPanel();
</script>
@endpush
