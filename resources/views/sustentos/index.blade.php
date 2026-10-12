@extends('layouts.app')
@section('title', 'Carga de Sustentos')

@section('content')

<div class="page-head">
  <div class="page-head-text">
    <h2>📁 Carga de Sustentos</h2>
    <p>Conciliaciones aprobadas pendientes de documentación.</p>
  </div>
</div>

<div class="notice">
  <strong>📋 Documentos obligatorios por operación:</strong>
  Acuerdo de Pago + Nota de Abono + Comprobante de Pago.
  Para conciliación de capital (&gt;3 UIT): también Informe-Propuesta.
  Transacción 300/140: sin Acuerdo de Pago.
</div>

{{-- Glosario --}}
<div class="card" style="margin-bottom:16px">
  <div class="card-title" style="cursor:pointer;display:flex;justify-content:space-between;align-items:center"
       onclick="toggleGlosario()">
    <span>📖 Glosario de nomenclatura de archivos</span>
    <span id="ico-glosario" style="font-size:18px;transition:transform .3s">▾</span>
  </div>
  <div id="body-glosario" style="margin-top:12px">
    <p style="font-size:12px;color:#666;margin-bottom:10px">
      Todos los archivos deben nombrarse según esta convención.
      Formato: <code style="background:#f3f4f6;padding:2px 6px;border-radius:4px">PREFIJO_CUENTA_FECHA.ext</code>
    </p>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:10px;margin-bottom:12px">
      <div style="border:1px solid #e5e7eb;border-left:4px solid #3b82f6;border-radius:8px;padding:12px">
        <div style="font-weight:600;font-size:12px;margin-bottom:6px;color:#1e40af">📄 dc_ — Acuerdo de Pago</div>
        <div style="font-size:11px;color:#374151;line-height:1.6">
          Palabras clave: <strong>firma</strong>, <strong>saldo</strong><br>
          Ej: <code style="background:#eff6ff;padding:1px 4px;border-radius:3px">dc_001234_20250601.pdf</code><br>
          Formatos: PDF, JPG, PNG
        </div>
      </div>
      <div style="border:1px solid #e5e7eb;border-left:4px solid #10b981;border-radius:8px;padding:12px">
        <div style="font-weight:600;font-size:12px;margin-bottom:6px;color:#065f46">📄 na_ — Nota de Abono</div>
        <div style="font-size:11px;color:#374151;line-height:1.6">
          Palabras clave: <strong>abono</strong>, <strong>monto</strong><br>
          Ej: <code style="background:#ecfdf5;padding:1px 4px;border-radius:3px">na_001234_20250601.pdf</code><br>
          Formatos: PDF, JPG, PNG
        </div>
      </div>
      <div style="border:1px solid #e5e7eb;border-left:4px solid #f59e0b;border-radius:8px;padding:12px">
        <div style="font-weight:600;font-size:12px;margin-bottom:6px;color:#92400e">📄 cp_ — Comprobante de Pago</div>
        <div style="font-size:11px;color:#374151;line-height:1.6">
          Palabras clave: <strong>comp</strong>, <strong>pago</strong><br>
          Ej: <code style="background:#fffbeb;padding:1px 4px;border-radius:3px">cp_001234_20250601.pdf</code><br>
          Formatos: PDF, JPG, PNG
        </div>
      </div>
      <div style="border:1px solid #e5e7eb;border-left:4px solid #8b5cf6;border-radius:8px;padding:12px">
        <div style="font-weight:600;font-size:12px;margin-bottom:6px;color:#4c1d95">📄 nf_ — Hoja de Convenio</div>
        <div style="font-size:11px;color:#374151;line-height:1.6">
          Palabras clave: <strong>convenio</strong><br>
          Ej: <code style="background:#f5f3ff;padding:1px 4px;border-radius:3px">nf_001234_20250601.pdf</code><br>
          Formatos: PDF, JPG, PNG
        </div>
      </div>
      <div style="border:1px solid #e5e7eb;border-left:4px solid #ef4444;border-radius:8px;padding:12px">
        <div style="font-weight:600;font-size:12px;margin-bottom:6px;color:#7f1d1d">📄 ip_ — Informe-Propuesta (&gt;3 UIT)</div>
        <div style="font-size:11px;color:#374151;line-height:1.6">
          Palabras clave: <strong>informe</strong>, <strong>propuesta</strong><br>
          Ej: <code style="background:#fff5f5;padding:1px 4px;border-radius:3px">ip_001234_20250601.pdf</code><br>
          Formatos: PDF únicamente
        </div>
      </div>
    </div>
    <div style="background:#fefce8;border:1px solid #fde68a;border-radius:8px;padding:10px 14px;font-size:11px;color:#78350f">
      ⚠️ <strong>Palabras bloqueadas en el nombre:</strong>
      <code>borrador</code>, <code>copia</code>, <code>tmp</code>, <code>test</code>, <code>prueba</code>, <code>v1</code>, <code>v2</code>, <code>final_final</code>
    </div>
  </div>
</div>

{{-- Tabla --}}
<div class="card">
  <div class="card-title">Conciliaciones aprobadas – sustentos por cargar o corregir</div>
  <div style="overflow-x:auto">
  <table>
    <thead>
      <tr>
        <th>Cliente</th><th>Cuenta</th><th>Operación</th>
        <th>Aprobado por</th><th>Estado</th><th>Acción</th>
      </tr>
    </thead>
    <tbody>
      @forelse($sustentos as $s)
        @php
          $cli = $s->propuesta->cliente;
          $obsTipos = $s->documentos->where('estado_revision', 'observado')->pluck('tipo_doc')->values();
          $args = [
            $s->id, $cli->nombre_cliente, (string) $cli->transaccion,
            $cli->esMayor3UIT(), $cli->estado_cartera === 'CASTIGADO',
            $s->estaObservado() ? route('sustentos.recargar', $s) : route('sustentos.cargar', $s),
            $s->estaObservado() ? $obsTipos : [],
          ];
        @endphp
        <tr>
          <td><strong>{{ $cli->nombre_cliente }}</strong></td>
          <td>{{ $cli->cuenta }}</td>
          <td>{{ $cli->operacion }}</td>
          <td style="font-size:12px">{{ $s->propuesta->aprobadoPor->name ?? '—' }}</td>
          <td>
            @if($s->estaObservado())
              <span class="badge badge-warn">Observado</span>
              @if($s->comentario_revision)
                <div style="font-size:11px;color:#92400e;margin-top:4px;max-width:260px">{{ $s->comentario_revision }}</div>
              @endif
            @elseif($s->estado === 'En revisión')
              <span class="badge badge-info">En revisión</span>
            @else
              <span class="badge badge-pending">Pendiente docs.</span>
            @endif
          </td>
          <td>
            @if($s->estado === 'En revisión')
              <span style="font-size:12px;color:#888">Enviado el {{ $s->fecha_carga?->format('d/m/Y') }}</span>
            @else
              <button type="button" class="btn btn-primary" style="font-size:12px;padding:7px 12px"
                onclick='abrirCarga(...@json($args))'>
                {{ $s->estaObservado() ? 'Corregir documentos' : 'Cargar sustentos' }}
              </button>
            @endif
          </td>
        </tr>
      @empty
        <tr>
          <td colspan="6" style="text-align:center;padding:24px;color:#999">
            No hay conciliaciones aprobadas pendientes de sustentos.
          </td>
        </tr>
      @endforelse
    </tbody>
  </table>
  </div>
</div>

{{-- Panel carga --}}
<div class="card" id="card-carga" style="display:none;margin-top:20px">
  <div class="card-title">Carga de documentación</div>
  <div id="carga-info" class="notice"></div>
  <form id="form-sustentos" method="POST" enctype="multipart/form-data"
        onsubmit="return confirmarRegistro(event)">
    @csrf
    <div id="carga-docs" style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-top:16px"></div>
    <div style="margin-top:20px">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px">
        <strong id="carga-count" style="font-size:13px">0 de 0 documentos válidos</strong>
        <span id="carga-pct" style="font-size:12px;color:#888">0%</span>
      </div>
      <div class="progress-bar" style="margin:8px 0 20px">
        <div class="progress-fill" id="carga-progress" style="width:0%"></div>
      </div>
      <div id="resumen-obs" style="display:none;margin-bottom:16px;
           border:1px solid #fca5a5;border-left:4px solid #ef4444;border-radius:8px;
           padding:14px;background:#fff5f5">
        <div style="font-weight:600;font-size:13px;color:#7f1d1d;margin-bottom:8px">
          ⚠️ Se encontraron observaciones antes de registrar
        </div>
        <ul id="lista-obs" style="margin:0;padding-left:18px;font-size:12px;color:#991b1b;line-height:1.8"></ul>
        <div style="margin-top:10px;font-size:12px;color:#7f1d1d">
          Puedes continuar si estás seguro, o regresa y corrige los archivos observados.
        </div>
      </div>
      <div class="actions">
        <button type="button" class="btn btn-ghost" onclick="cancelarCarga()">Cancelar</button>
        <button type="submit" class="btn btn-primary" id="btn-registrar" disabled>
          📤 Registrar sustentos
        </button>
      </div>
    </div>
  </form>
</div>

@endsection

@push('styles')
<style>
@keyframes _spin { to { transform: rotate(360deg); } }
.val-spinner {
  width:13px;height:13px;
  border:2px solid #d1d5db;
  border-top-color:#3b82f6;
  border-radius:50%;
  animation:_spin .7s linear infinite;
  flex-shrink:0;margin-top:1px;
}
.doc-slot {
  border:1px solid #e5e7eb;
  border-left:4px solid #d1d5db;
  border-radius:9px;padding:14px;
  background:#fff;
  transition:border-left-color .3s,background .3s;
}
.doc-slot.slot-ok    { border-left-color:#22c55e !important; background:#f0fdf4; }
.doc-slot.slot-warn  { border-left-color:#f59e0b !important; background:#fffbeb; }
.doc-slot.slot-error { border-left-color:#ef4444 !important; background:#fff5f5; }
.val-box {
  display:none;flex-direction:column;gap:5px;
  margin-top:10px;font-size:12px;
  border-radius:7px;padding:10px 12px;
}
.val-box.procesando {
  display:flex;flex-direction:row;align-items:center;gap:8px;
  background:#f3f4f6;color:#6b7280;
}
.val-box.valido      { display:flex;background:#ecfdf5;border:1px solid #6ee7b7;color:#064e3b; }
.val-box.advertencia { display:flex;background:#fffbeb;border:1px solid #fcd34d;color:#78350f; }
.val-box.invalido    { display:flex;background:#fff5f5;border:1px solid #fca5a5;color:#7f1d1d; }
.obs-item {
  display:flex;align-items:flex-start;gap:6px;
  padding:5px 8px;border-radius:5px;
  font-size:11px;line-height:1.5;
}
.obs-item.ok   { background:#d1fae5;color:#065f46; }
.obs-item.warn { background:#fef3c7;color:#92400e; }
.obs-item.err  { background:#fee2e2;color:#991b1b; }
.file-detected {
  font-size:11px;padding:4px 8px;
  background:#f9fafb;border:1px solid #e5e7eb;
  border-radius:5px;color:#374151;
  margin-top:6px;word-break:break-all;
}
.badge-warn  { background:#fef3c7;color:#92400e;font-size:11px;padding:3px 9px;border-radius:20px;font-weight:500; }
.badge-error { background:#fee2e2;color:#991b1b;font-size:11px;padding:3px 9px;border-radius:20px;font-weight:500; }
</style>
@endpush

@push('scripts')
<script>
/* ── Reglas por tipo de documento ─────────────────────── */
var REGLAS = {
  'Acuerdo de Pago': {
    prefijo:'dc_', color:'#3b82f6', formatos:['pdf','jpg','jpeg','png'],
    palabrasWarn:[
      { palabra:'firma',   msg:'No se detectó "firma" — verifique que el documento esté firmado.' },
      { palabra:'saldo',   msg:'No se detectó "saldo" — el saldo podría no estar acorde a la base de datos.' },
    ],
    bloqueadas:[
      { palabra:'borrador', msg:'Contiene "borrador" — no se admiten borradores.' },
      { palabra:'copia',    msg:'Contiene "copia" — use el documento original.' },
      { palabra:'tmp',      msg:'Contiene "tmp" — parece un archivo temporal.' },
      { palabra:'test',     msg:'Contiene "test" — no usar archivos de prueba.' },
      { palabra:'prueba',   msg:'Contiene "prueba" — no usar archivos de prueba.' },
    ],
  },
  'Nota de Abono': {
    prefijo:'na_', color:'#10b981', formatos:['pdf','jpg','jpeg','png'],
    palabrasWarn:[
      { palabra:'abono', msg:'No se detectó "abono" — confirme que corresponde a la nota de abono.' },
      { palabra:'monto', msg:'No se detectó "monto" — el monto podría no estar acorde al registro.' },
    ],
    bloqueadas:[
      { palabra:'borrador', msg:'Contiene "borrador" — no admitido.' },
      { palabra:'copia',    msg:'Contiene "copia" — use el original.' },
      { palabra:'tmp',      msg:'Contiene "tmp" — temporal no válido.' },
      { palabra:'prueba',   msg:'Contiene "prueba" — no válido.' },
    ],
  },
  'Comprobante de Pago': {
    prefijo:'cp_', color:'#f59e0b', formatos:['pdf','jpg','jpeg','png'],
    palabrasWarn:[
      { palabra:'pago', msg:'No se detectó "pago" — verifique que sea el comprobante correcto.' },
    ],
    bloqueadas:[
      { palabra:'borrador', msg:'Contiene "borrador" — no admitido.' },
      { palabra:'tmp',      msg:'Contiene "tmp" — temporal no válido.' },
      { palabra:'prueba',   msg:'Contiene "prueba" — no válido.' },
      { palabra:'copia',    msg:'Contiene "copia" — use el original.' },
    ],
  },
  'Hoja de Convenio': {
    prefijo:'nf_', color:'#8b5cf6', formatos:['pdf','jpg','jpeg','png'],
    palabrasWarn:[
      { palabra:'convenio', msg:'No se detectó "convenio" — confirme el documento.' },
    ],
    bloqueadas:[
      { palabra:'borrador', msg:'Contiene "borrador" — no admitido.' },
      { palabra:'copia',    msg:'Contiene "copia" — use el original.' },
      { palabra:'tmp',      msg:'Contiene "tmp" — temporal no válido.' },
    ],
  },
  'Informe-Propuesta SK>3UIT': {
    prefijo:'ip_', color:'#ef4444', formatos:['pdf'],
    palabrasWarn:[
      { palabra:'informe',   msg:'No se detectó "informe" — verifique que sea el informe-propuesta.' },
      { palabra:'propuesta', msg:'No se detectó "propuesta" — verifique el documento.' },
    ],
    bloqueadas:[
      { palabra:'borrador',    msg:'Contiene "borrador" — no admitido.' },
      { palabra:'tmp',         msg:'Contiene "tmp" — temporal no válido.' },
      { palabra:'prueba',      msg:'Contiene "prueba" — no válido.' },
      { palabra:'v1',          msg:'Contiene "v1" — suba la versión final.' },
      { palabra:'v2',          msg:'Contiene "v2" — suba la versión final.' },
      { palabra:'final_final', msg:'Nombre "final_final" — use la versión definitiva.' },
    ],
  },
};

function _escHtml(t) {
  return String(t).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

var FOTOS_WARN = ['scan_','img_','dsc','photo','foto','cam','whatsapp','screenshot','captura'];
var docsReq    = [];
var valState   = {};

/* ── Abrir panel ─────────────────────────────────────── */
function abrirCarga(sustentoId, cliente, transaccion, mayor3UIT, castigado, accion, soloTipos) {
  docsReq = [];
  if (!castigado)            docsReq.push('Acuerdo de Pago');
  docsReq.push('Nota de Abono', 'Comprobante de Pago');
  if (transaccion !== '300/140') docsReq.push('Hoja de Convenio');
  if (mayor3UIT)             docsReq.push('Informe-Propuesta SK>3UIT');
  // Corrección: solo se vuelven a cargar los documentos observados por el revisor
  var esCorreccion = soloTipos && soloTipos.length;
  if (esCorreccion) docsReq = soloTipos.slice();

  valState = {};
  for (var i = 0; i < docsReq.length; i++) {
    valState[i] = { status:'pending', obs:[] };
  }

  document.getElementById('carga-info').innerHTML =
    '<strong>' + _escHtml(cliente) + '</strong><br>' +
    '<small style="color:#666">' + (
      esCorreccion               ? 'Corrección: vuelva a cargar solo los documentos observados por el revisor.' :
      transaccion === '300/140'  ? 'Transacción 300/140: no requiere Acuerdo de Pago.' :
      castigado                  ? 'Crédito castigado: no aplica Acuerdo de Pago.'     :
                                   'Cargue todos los documentos antes de registrar.'
    ) + '</small>';

  /* Construir HTML completo */
  var html = '';
  for (var j = 0; j < docsReq.length; j++) {
    var nombre = docsReq[j];
    var regla  = REGLAS[nombre] || {};
    var color  = regla.color    || '#6b7280';
    var fmts   = (regla.formatos || ['pdf']).map(function(f){ return '.'+f; }).join(', ');
    var accept = (regla.formatos || ['pdf']).map(function(f){ return '.'+f; }).join(',');

    html +=
      '<div class="doc-slot" id="slot-' + j + '" style="border-left:4px solid ' + color + '">' +
        '<div style="display:flex;justify-content:space-between;align-items:center;font-weight:600;font-size:12px;margin-bottom:8px">' +
          '<span>' + nombre + '</span>' +
          '<span class="badge badge-pending" id="cs' + j + '">Pendiente</span>' +
        '</div>' +
        '<div style="font-size:10px;color:#9ca3af;margin-bottom:8px">' +
          'Prefijo requerido: <code style="background:#f3f4f6;padding:1px 5px;border-radius:3px">' + (regla.prefijo || '—') + '</code>' +
          ' &nbsp;·&nbsp; Formatos: <strong>' + fmts + '</strong>' +
        '</div>' +
        '<input type="hidden" name="documentos[' + j + '][tipo]" value="' + nombre + '">' +
        '<input type="file" id="finput-' + j + '" name="documentos[' + j + '][archivo]"' +
          ' accept="' + accept + '" style="width:100%;font-size:12px">' +
        '<div class="val-box" id="vb' + j + '"></div>' +
      '</div>';
  }

  document.getElementById('carga-docs').innerHTML = html;

  /* ✅ Asignar listeners con IIFE para capturar idx y tipo correctamente */
  for (var k = 0; k < docsReq.length; k++) {
    (function(idx, tipo) {
      document.getElementById('finput-' + idx).addEventListener('change', function() {
        iniciarValidacion(this, idx, tipo);
      });
    })(k, docsReq[k]);
  }

  document.getElementById('form-sustentos').action = accion;
  document.getElementById('btn-registrar').disabled = true;
  document.getElementById('resumen-obs').style.display = 'none';
  document.getElementById('card-carga').style.display = 'block';
  document.getElementById('card-carga').scrollIntoView({ behavior:'smooth' });
  actualizarBarra();
}

/* ── Validar ─────────────────────────────────────────── */
function iniciarValidacion(inputEl, idx, tipoDoc) {
  var file = inputEl.files[0];
  if (!file) return;

  valState[idx] = { status:'loading', obs:[] };

  var badge = document.getElementById('cs' + idx);
  badge.className   = 'badge badge-pending';
  badge.textContent = '⏳ Verificando...';

  var vb = document.getElementById('vb' + idx);
  vb.className = 'val-box procesando';
  vb.innerHTML = '<div class="val-spinner"></div><span>Verificando formato del archivo…</span>';

  setTimeout(function() {
    var nombre  = file.name.toLowerCase();
    var ext     = nombre.split('.').pop();
    var regla   = REGLAS[tipoDoc] || {};
    var obs     = [];
    var hayErr  = false;
    var hayWarn = false;

    /* 1. Prefijo */
    if (regla.prefijo) {
      if (nombre.startsWith(regla.prefijo)) {
        obs.push({ t:'ok',  tx:'Prefijo <strong>' + regla.prefijo + '</strong> correcto.' });
      } else {
        obs.push({ t:'err', tx:'Prefijo incorrecto — se esperaba <strong>' + regla.prefijo + '</strong>.' });
        hayErr = true;
      }
    }

    /* 2. Formato */
    if (regla.formatos && regla.formatos.length) {
      if (regla.formatos.indexOf(ext) !== -1) {
        obs.push({ t:'ok', tx:'Formato <strong>.' + ext + '</strong> aceptado.' });
      } else {
        obs.push({ t:'err', tx:'Formato <strong>.' + ext + '</strong> no permitido — use: ' +
          regla.formatos.map(function(f){ return '<strong>.'+f+'</strong>'; }).join(', ') + '.' });
        hayErr = true;
      }
    }

    /* 3. Palabras bloqueadas */
    if (regla.bloqueadas) {
      for (var b = 0; b < regla.bloqueadas.length; b++) {
        if (nombre.indexOf(regla.bloqueadas[b].palabra) !== -1) {
          obs.push({ t:'err', tx:'🚫 ' + regla.bloqueadas[b].msg });
          hayErr = true;
        }
      }
    }

    /* 4. Palabras que deben estar */
    if (regla.palabrasWarn) {
      for (var w = 0; w < regla.palabrasWarn.length; w++) {
        if (nombre.indexOf(regla.palabrasWarn[w].palabra) === -1) {
          obs.push({ t:'warn', tx:'⚠ ' + regla.palabrasWarn[w].msg });
          hayWarn = true;
        }
      }
    }

    /* 5. Foto/captura sospechosa */
    for (var f = 0; f < FOTOS_WARN.length; f++) {
      if (nombre.indexOf(FOTOS_WARN[f]) !== -1) {
        obs.push({ t:'warn', tx:'📷 El nombre sugiere foto o captura — verifique que la imagen no esté borrosa y que todo el texto sea legible.' });
        hayWarn = true;
        break;
      }
    }

    /* 6. Nombre muy corto */
    var sinExt = nombre.replace(/\.[^.]+$/, '');
    if (sinExt.length < 6) {
      obs.push({ t:'warn', tx:'⚠ Nombre muy corto — verifique que esté correctamente nombrado.' });
      hayWarn = true;
    }

    var estado = hayErr ? 'error' : hayWarn ? 'warn' : 'ok';
    valState[idx] = { status: estado, obs: obs };

    /* Render badge */
    var badgeEl = document.getElementById('cs' + idx);
    if (estado === 'ok') {
      badgeEl.className   = 'badge badge-ok';
      badgeEl.textContent = '✓ Válido';
    } else if (estado === 'warn') {
      badgeEl.className   = 'badge badge-warn';
      badgeEl.textContent = '⚠ Advertencia';
    } else {
      badgeEl.className   = 'badge badge-error';
      badgeEl.textContent = '✗ Revisar';
    }

    /* Render slot borde */
    var slot = document.getElementById('slot-' + idx);
    slot.classList.remove('slot-ok','slot-warn','slot-error');
    if (estado === 'ok')    slot.classList.add('slot-ok');
    if (estado === 'warn')  slot.classList.add('slot-warn');
    if (estado === 'error') slot.classList.add('slot-error');

    /* Render caja de resultados */
    var titulos = {
      ok:   { cls:'valido',      ico:'✅', txt:'Archivo validado correctamente' },
      warn: { cls:'advertencia', ico:'⚠️',  txt:'Validado con advertencias'      },
      error:{ cls:'invalido',    ico:'❌', txt:'Se encontraron errores'          },
    };
    var t = titulos[estado];
    var itemsHTML = '';
    for (var o = 0; o < obs.length; o++) {
      itemsHTML +=
        '<div class="obs-item ' + obs[o].t + '">' +
          '<span style="flex-shrink:0">' + (obs[o].t==='ok'?'✔':obs[o].t==='warn'?'⚠':'✖') + '</span>' +
          '<span>' + obs[o].tx + '</span>' +
        '</div>';
    }

    var vbEl = document.getElementById('vb' + idx);
    vbEl.className = 'val-box ' + t.cls;
    vbEl.innerHTML =
      '<div style="display:flex;align-items:center;gap:6px;font-weight:600;font-size:12px;margin-bottom:6px">' +
        '<span>' + t.ico + '</span><span>' + t.txt + '</span>' +
      '</div>' +
      itemsHTML +
      '<div class="file-detected">📄 ' + file.name + '</div>';

    actualizarBarra();
    renderResumenObs();

  }, 1500);
}

/* ── Barra de progreso ───────────────────────────────── */
function actualizarBarra() {
  var total    = docsReq.length;
  var validos  = 0;
  var cargados = 0;
  for (var i = 0; i < total; i++) {
    var s = valState[i] ? valState[i].status : 'pending';
    if (s === 'ok')                        validos++;
    if (s === 'ok' || s === 'warn' || s === 'error') cargados++;
  }
  var pct = total ? Math.round(cargados / total * 100) : 0;

  document.getElementById('carga-count').textContent = validos + ' de ' + total + ' documentos válidos';
  document.getElementById('carga-pct').textContent   = pct + '%';
  document.getElementById('carga-progress').style.width = pct + '%';

  // var hayErr    = false;
  // for (var j = 0; j < total; j++) {
  //   if (valState[j] && valState[j].status === 'error') { hayErr = true; break; }
  // }
document.getElementById('btn-registrar').disabled = cargados < total;
}

/* ── Resumen observaciones ───────────────────────────── */
function renderResumenObs() {
  var filas = [];
  for (var i = 0; i < docsReq.length; i++) {
    var s = valState[i];
    if (!s || s.status === 'pending' || s.status === 'loading') continue;
    for (var o = 0; o < s.obs.length; o++) {
      if (s.obs[o].t === 'warn' || s.obs[o].t === 'err') {
        var pureText = s.obs[o].tx.replace(/<[^>]+>/g,'');
        filas.push('<li><strong>' + docsReq[i] + ':</strong> ' + pureText + '</li>');
      }
    }
  }
  var resumen = document.getElementById('resumen-obs');
  if (filas.length) {
    document.getElementById('lista-obs').innerHTML = filas.join('');
    resumen.style.display = 'block';
  } else {
    resumen.style.display = 'none';
  }
}

/* ── Confirmación al enviar ──────────────────────────── */
function confirmarRegistro(e) {
  // Limpiar hidden inputs anteriores para evitar duplicados
  var anteriores = document.querySelectorAll('.hidden-val');
  anteriores.forEach(function(el){ el.remove(); });

  // Agregar hidden inputs siempre (antes de cualquier return)
  for (var i = 0; i < docsReq.length; i++) {
    var s = valState[i];
    if (!s) continue;

    var hiddenEst = document.createElement('input');
    hiddenEst.type      = 'hidden';
    hiddenEst.name      = 'documentos[' + i + '][estado_validacion]';
    hiddenEst.value     = s.status;
    hiddenEst.className = 'hidden-val';
    document.getElementById('form-sustentos').appendChild(hiddenEst);

    var hiddenObs = document.createElement('input');
    hiddenObs.type      = 'hidden';
    hiddenObs.name      = 'documentos[' + i + '][observaciones]';
    hiddenObs.value     = JSON.stringify(s.obs);
    hiddenObs.className = 'hidden-val';
    document.getElementById('form-sustentos').appendChild(hiddenObs);
  }

  // Después del confirm, si cancela se detiene el submit
  var hayWarn = false;
  for (var i = 0; i < docsReq.length; i++) {
    if (valState[i] && valState[i].status === 'warn') { hayWarn = true; break; }
  }

  if (hayWarn) {
    return confirm(
      '⚠️ Se encontraron observaciones en uno o más documentos.\n\n' +
      '¿Está seguro de que desea registrar los sustentos con estas advertencias?\n\n' +
      'Si no está seguro, cancele y revise los archivos marcados.'
    );
  }


  return true;
}

/* ── Glosario toggle ─────────────────────────────────── */
function toggleGlosario() {
  var body  = document.getElementById('body-glosario');
  var ico   = document.getElementById('ico-glosario');
  var open  = body.style.display !== 'none';
  body.style.display  = open ? 'none' : 'block';
  ico.style.transform = open ? 'rotate(-90deg)' : 'rotate(0deg)';
}

/* ── Cancelar ────────────────────────────────────────── */
function cancelarCarga() {
  document.getElementById('card-carga').style.display = 'none';
}
</script>
@endpush