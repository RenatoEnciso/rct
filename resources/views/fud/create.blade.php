@extends('layouts.app')
@section('title', 'Nueva propuesta FUD')

@section('content')
<div class="page-head">
  <div>
    <h2>Nueva propuesta FUD</h2>
    <p>Registre la propuesta de conciliación para un cliente de la base de campaña.</p>
  </div>
</div>

{{-- Paso 1 --}}
<div class="card">
  <div class="card-head">
    <div class="card-title"><span class="paso-n" aria-label="Paso 1">1</span> Búsqueda del cliente en la base de campaña</div>
    <span class="cell-sub" id="match-count">Cargando…</span>
  </div>
  <div class="notice"><i class="bi bi-info-circle"></i>
    <span>Solo se gestionan clientes de la base de campaña. El tipo de negociación, el lineamiento y el aprobador
    los determina el sistema según los datos del crédito.</span></div>

  <div class="grid4">
    <div class="field">
      <label for="filtro-zona">Zona</label>
      <select id="filtro-zona">
        <option value="">Todas las zonas</option>
        @foreach($zonas as $zona)<option value="{{ $zona->id }}">{{ $zona->nombre }}</option>@endforeach
      </select>
    </div>
    <div class="field">
      <label for="filtro-tienda">Tienda</label>
      <select id="filtro-tienda" data-filtro>
        <option value="">Todas las tiendas</option>
        @foreach($zonas as $zona)
          @foreach($zona->tiendas as $tienda)<option value="{{ $tienda->id }}" data-zona="{{ $zona->id }}">{{ $tienda->nombre }}</option>@endforeach
        @endforeach
      </select>
    </div>
    <div class="field">
      <label for="filtro-estado">Situación de cartera</label>
      <select id="filtro-estado" data-filtro>
        <option value="">Todas</option>
        @foreach(['VENCIDO','JUDICIAL','EXTRAJUDICIAL','CASTIGADO','FOCMAC'] as $e)<option value="{{ $e }}">{{ ucfirst(strtolower($e)) }}</option>@endforeach
      </select>
    </div>
    <div class="field">
      <label for="filtro-nombre">Nombre del cliente</label>
      <input id="filtro-nombre" data-filtro placeholder="Apellidos o nombres">
    </div>
    <div class="field">
      <label for="filtro-documento">DNI / RUC</label>
      <input id="filtro-documento" data-filtro inputmode="numeric" placeholder="Número de documento">
    </div>
    <div class="field">
      <label for="filtro-cuenta">Cuenta</label>
      <input id="filtro-cuenta" data-filtro inputmode="numeric" placeholder="Número de cuenta">
    </div>
    <div class="field">
      <label for="filtro-operacion">Operación</label>
      <input id="filtro-operacion" data-filtro inputmode="numeric" placeholder="Número de operación">
    </div>
    <div class="field field-accion">
      <button type="button" class="btn btn-ghost" onclick="limpiarFiltros()"><i class="bi bi-x-circle"></i> Limpiar filtros</button>
    </div>
  </div>

  <div class="table-wrap tabla-scroll">
    <table>
      <thead>
        <tr>
          <th>Cliente</th><th>Cuenta / Operación</th><th>Tienda</th><th class="num">Saldo capital</th>
          <th class="center">Atraso</th><th>Calificación</th><th>Negociación</th><th>Autonomía</th><th></th>
        </tr>
      </thead>
      <tbody id="tabla-clientes">
        <tr><td colspan="9" class="empty-state"><span class="spinner d-inline-block"></span></td></tr>
      </tbody>
    </table>
  </div>
</div>

{{-- Paso 2 --}}
<div class="card" id="card-cliente" hidden>
  <div class="card-head">
    <div class="card-title"><span class="paso-n" aria-label="Paso 2">2</span> Datos del cliente seleccionado</div>
    <span class="badge badge-gray"><i class="bi bi-lock"></i> Información del sistema</span>
  </div>
  <div class="notice-warning notice-recaida" id="aviso-recaida" role="alert" hidden></div>
  <div class="notice notice-prioridad" id="aviso-prioridad" hidden></div>

  <div class="detail-grid">
    @foreach([
      'info-cliente' => 'Cliente', 'info-documento' => 'Documento', 'info-telefono' => 'Teléfono',
      'info-cuenta' => 'Cuenta', 'info-operacion' => 'Operación', 'info-sicmact' => 'Crédito SICMACT',
      'info-saldo' => 'Saldo capital', 'info-desembolso' => 'Monto desembolsado', 'info-atraso' => 'Días de atraso',
      'info-rango' => 'Rango de atraso', 'info-calif' => 'Calificación RCC', 'info-tipo' => 'Tipo de crédito',
      'info-modulo' => 'Módulo', 'info-estado' => 'Situación de cartera', 'info-conc12m' => 'Conciliaciones (12 meses)',
      'info-garante' => 'Garante', 'info-asesor' => 'Asesor de negocios',
    ] as $id => $label)
      <div><div class="detail-label">{{ $label }}</div><div class="detail-value" id="{{ $id }}">—</div></div>
    @endforeach
  </div>

  <div class="kpi-grid banda-decision">
    <div class="kpi-card">
      <div class="detail-label">Tipo de negociación</div>
      <div class="detail-value lg text-rojo" id="info-tipo-neg">—</div>
    </div>
    <div class="kpi-card">
      <div class="detail-label">Autonomía requerida</div>
      <div class="detail-value lg" id="info-autonomia">—</div>
    </div>
    <div class="kpi-card">
      <div class="detail-label">Lineamiento de campaña</div>
      <div class="detail-value" id="info-lineamiento">—</div>
    </div>
  </div>
</div>

{{-- Paso 3 --}}
<div class="card" id="card-propuesta" hidden>
  <div class="card-title"><span class="paso-n" aria-label="Paso 3">3</span> Propuesta de conciliación</div>

  <form method="POST" action="{{ route('fud.store') }}" id="form-fud" enctype="multipart/form-data">
    @csrf
    <input type="hidden" name="cliente_cartera_id" id="input-cliente-id" value="{{ old('cliente_cartera_id') }}">
    <input type="hidden" name="tipo_negociacion" id="input-tipo-neg" value="{{ old('tipo_negociacion') }}">
    <input type="hidden" name="tipo_negociacion_desc" id="input-tipo-desc" value="{{ old('tipo_negociacion_desc') }}">

    <div class="grid3">
      <div class="field span-2">
        <label>Aprobador asignado automáticamente</label>
        <div class="readonly-value"><i class="bi bi-person-check text-gris"></i><span id="resumen-aprobador">—</span></div>
        <span class="field-hint">Según el nivel de autonomía del crédito y la zona.</span>
      </div>
      <div class="field">
        <label for="tipo_operacion">Tipo de operación</label>
        <select id="tipo_operacion" name="tipo_operacion">
          <option value="CAMPAÑA">Campaña</option>
          <option value="ORDINARIA">Ordinaria</option>
          <option value="ESPECIAL">Especial</option>
        </select>
      </div>
    </div>

    {{-- Pago de cuotas --}}
    <div id="panel-cuotas" class="form-section" hidden>
      <div class="form-section-title">Condiciones · Pago de cuotas</div>
      <div class="grid3">
        <div class="field"><label>Cuota desde</label><input type="number" name="cuota_desde" min="1" placeholder="Ej. 3"></div>
        <div class="field"><label>Cuota hasta</label><input type="number" name="cuota_hasta" min="1" placeholder="Ej. 5"></div>
        <div class="field"><label>Descuento de intereses (%)</label><input type="number" name="desc_interes" min="0" max="100" step="0.01"></div>
        <div class="field"><label>Descuento de moratorio (%)</label><input type="number" name="desc_moratorio" value="100" min="0" max="100"></div>
        <div class="field"><label>¿Pone el crédito al día?</label>
          <select name="pone_al_dia"><option value="0">No</option><option value="1">Sí</option></select></div>
        <div class="field"><label>¿Cierra el mes en situación vigente?</label>
          <select name="cierra_mes_vigente"><option value="1">Sí</option><option value="0">No</option></select></div>
      </div>
      <div class="notice-warning mt-3 mb-0"><i class="bi bi-exclamation-triangle"></i>
        <span>Lo conciliado de cuotas atrasadas no debe superar el 50% del valor de la cuota pactada.</span></div>
    </div>

    {{-- Cancelación total --}}
    <div id="panel-cancelacion" class="form-section" hidden>
      <div class="form-section-title">Condiciones · Cancelación total</div>
      <div class="grid3">
        <div class="field"><label>Monto a pagar (S/)</label><input type="number" name="monto_a_pagar" min="0" step="0.01"></div>
        <div class="field"><label>Descuento de intereses (%)</label><input type="number" name="desc_interes_canc" min="0" max="100" step="0.01"></div>
        <div class="field"><label>Descuento de capital (%)</label><input type="number" name="desc_capital" min="0" max="100" step="0.01"></div>
        <div class="field"><label>Fecha límite de pago</label><input type="date" name="fecha_limite"></div>
        <div class="field"><label>Saldo capital mayor a 3 UIT</label>
          <select name="mayor_tres_uit" id="select-mayor-uit"><option value="0">No</option><option value="1">Sí — requiere Informe-Propuesta</option></select></div>
        <div class="field"><label>Búsqueda registral (SUNARP)</label>
          <select name="sunarp_positivo"><option value="0">Negativa</option><option value="1">Positiva — requiere informe</option></select></div>
      </div>
      <div class="notice mt-3 mb-0"><i class="bi bi-info-circle"></i>
        <span>Pago fraccionado: máximo 90 días desde la primera amortización.</span></div>
    </div>

    {{-- Cartera especial --}}
    <div id="panel-judicial" class="form-section" hidden>
      <div class="form-section-title">Condiciones · Cartera judicial, castigada o FOCMAC</div>
      <div class="grid3">
        <div class="field"><label>Monto del gravamen / embargo (S/)</label><input type="number" name="monto_gravamen" min="0" step="0.01"></div>
        <div class="field"><label>Etapa procesal</label>
          <select name="etapa_procesal">
            @foreach(['Pre-judicial','Ejecución de garantías','Medidas cautelares','Etapa de remate'] as $e)<option>{{ $e }}</option>@endforeach
          </select></div>
        <div class="field"><label>Beneficio propuesto sobre SK (%)</label><input type="number" name="beneficio_pct_sk" min="0" max="100" step="0.01"></div>
      </div>
    </div>

    <div class="form-section">
      <div class="field">
        <label for="motivo">Sustento de la propuesta <span class="req">*</span></label>
        <textarea id="motivo" name="motivo" rows="5" required minlength="20"
          placeholder="Situación del cliente y del garante, afectación del negocio, voluntad de pago y gestiones realizadas.">{{ old('motivo') }}</textarea>
        <span class="field-hint" id="motivo-hint">Mínimo 20 caracteres.</span>
      </div>
    </div>

    <div class="form-section">
      <div class="form-section-title">Documentos de la negociación</div>
      <div class="file-list mb-2" id="lista-adjuntos"></div>
      <input type="file" id="input-adjuntos" name="adjuntos[]" multiple accept=".pdf,.jpg,.jpeg,.png,.xlsx,.docx" hidden>
      <button type="button" class="btn btn-secondary btn-sm" onclick="document.getElementById('input-adjuntos').click()">
        <i class="bi bi-paperclip"></i> Adjuntar archivos
      </button>
      <span class="field-hint ms-2">PDF, imagen, Excel o Word · máximo 10 MB por archivo.</span>
    </div>

    <div class="actions">
      <a href="{{ route('dashboard') }}" class="btn btn-secondary">Cancelar</a>
      <button type="submit" class="btn btn-primary"><i class="bi bi-send"></i> Enviar al nivel de autonomía</button>
    </div>
  </form>
</div>
@endsection

@push('scripts')
<script>
(function () {
  const API_URL = '{{ route('api.clientes') }}';
  const ETIQUETAS_NEG = {
    cuotas: 'Pago de Cuotas', cancelacion: 'Cancelación Total',
    extrajudicial: 'Cartera Extrajudicial / Judicial', castigada: 'Cartera Castigada', focmac: 'Cartera FOCMAC',
  };
  const soles = (v, m) => ((m ?? 'PEN') === 'USD' ? 'US$ ' : 'S/ ') +
    parseFloat(v || 0).toLocaleString('es-PE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  const set = (id, v) => { document.getElementById(id).textContent = (v === null || v === undefined || v === '') ? '—' : v; };
  let clientes = [];
  let clienteInicial = {{ $clienteInicial ?? 'null' }};

  // ── Adjuntos ───────────────────────────────────────────────
  let seleccion = new DataTransfer();
  const inputAdj = document.getElementById('input-adjuntos');
  inputAdj.addEventListener('change', () => {
    for (const f of inputAdj.files) {
      if (![...seleccion.files].some(x => x.name === f.name && x.size === f.size)) seleccion.items.add(f);
    }
    inputAdj.files = seleccion.files; pintarAdjuntos();
  });
  window.quitarAdjunto = function (i) {
    const n = new DataTransfer();
    [...seleccion.files].forEach((f, j) => { if (j !== i) n.items.add(f); });
    seleccion = n; inputAdj.files = seleccion.files; pintarAdjuntos();
  };
  function pintarAdjuntos() {
    document.getElementById('lista-adjuntos').innerHTML = [...seleccion.files].map((f, i) =>
      `<span class="file-chip"><i class="bi bi-file-earmark"></i><span>${rctEsc(f.name)}</span>
        <button type="button" class="chip-quitar"
          onclick="quitarAdjunto(${i})" aria-label="Quitar"><i class="bi bi-x-lg"></i></button></span>`).join('');
  }

  // ── Búsqueda ───────────────────────────────────────────────
  let timer;
  async function filtrar() {
    const v = id => document.getElementById(id).value;
    const params = new URLSearchParams({
      zona_id: v('filtro-zona'), tienda_id: v('filtro-tienda'), estado: v('filtro-estado'),
      cuenta: v('filtro-cuenta'), operacion: v('filtro-operacion'), nombre: v('filtro-nombre'), documento: v('filtro-documento'),
    });
    if (clienteInicial) params.set('id', clienteInicial);
    const tbody = document.getElementById('tabla-clientes');
    try {
      const res = await fetch(`${API_URL}?${params}`, { headers: { Accept: 'application/json' } });
      clientes = await res.json();
    } catch (e) {
      tbody.innerHTML = `<tr><td colspan="9" class="empty-state"><i class="bi bi-wifi-off"></i><strong>No se pudo consultar la cartera</strong><span>Intente nuevamente.</span></td></tr>`;
      return;
    }
    document.getElementById('match-count').textContent = clientes.length + (clientes.length === 50 ? '+ clientes (refine la búsqueda)' : ' clientes');
    if (!clientes.length) {
      tbody.innerHTML = `<tr><td colspan="9" class="empty-state"><i class="bi bi-search"></i><strong>Sin resultados</strong><span>No hay clientes disponibles con los filtros aplicados.</span></td></tr>`;
      return;
    }
    tbody.innerHTML = clientes.map((c, i) => {
      const usos = c.conc_operacion_12m ?? c.veces_conciliado ?? 0;
      const bloq = usos >= 4;
      return `<tr>
        <td><div class="cell-main">${rctEsc(c.nombre_cliente)}</div><div class="cell-sub">${rctEsc(c.tipo_credito ?? '')}</div></td>
        <td class="nowrap"><div>${rctEsc(c.cuenta ?? '—')}</div><div class="cell-sub">${rctEsc(c.operacion)}</div></td>
        <td><div>${rctEsc(c.tienda?.nombre ?? '—')}</div><div class="cell-sub">${rctEsc(c.zona?.nombre ?? '')}</div></td>
        <td class="num">${soles(c.saldo_capital, c.moneda)}</td>
        <td class="center"><span class="badge ${c.dias_atraso > 90 ? 'badge-danger' : 'badge-pending'}">${c.dias_atraso} d</span></td>
        <td><span class="badge badge-gray">${rctEsc(c.calificacion_rcc ?? '—')}</span></td>
        <td>${rctEsc(ETIQUETAS_NEG[c.tipo_negociacion_campana] ?? c.tipo_negociacion_campana ?? '—')}</td>
        <td>${rctEsc(c.autonomia ?? '—')}${usos >= 3 ? `<div class="cell-sub ${bloq ? 'text-danger' : 'text-mostaza'}">${usos} de 4 usos</div>` : ''}</td>
        <td class="num"><button type="button" class="btn btn-primary btn-sm" onclick="seleccionar(${i})" ${bloq ? 'disabled title="Supera el límite de 4 conciliaciones en 12 meses"' : ''}>Seleccionar</button></td>
      </tr>`;
    }).join('');
    if (clienteInicial) {
      clienteInicial = null;
      document.getElementById('match-count').textContent = 'Cliente seleccionado desde Mi prioridad de hoy';
      if (clientes.length === 1) seleccionar(0);
    }
  }
  const programar = () => { clearTimeout(timer); timer = setTimeout(filtrar, 300); };
  document.querySelectorAll('[data-filtro]').forEach(el => el.addEventListener(el.tagName === 'SELECT' ? 'change' : 'input', programar));
  document.getElementById('filtro-zona').addEventListener('change', function () {
    document.querySelectorAll('#filtro-tienda option[data-zona]').forEach(o => { o.hidden = !!this.value && o.dataset.zona !== this.value; });
    document.getElementById('filtro-tienda').value = '';
    programar();
  });
  window.limpiarFiltros = function () {
    document.querySelectorAll('[data-filtro], #filtro-zona').forEach(el => el.value = '');
    document.querySelectorAll('#filtro-tienda option').forEach(o => o.hidden = false);
    filtrar();
  };

  // ── Selección: completa la ficha y la propuesta ────────────
  window.seleccionar = function (i) {
    const c = clientes[i];
    const codigo = ETIQUETAS_NEG[c.tipo_negociacion_campana] ? c.tipo_negociacion_campana : 'cancelacion';
    const etiqueta = ETIQUETAS_NEG[codigo];

    set('info-cliente', c.nombre_cliente);
    set('info-documento', ((c.tipo_documento ?? '') + ' ' + (c.nro_documento ?? '')).trim());
    set('info-telefono', c.telefono);
    set('info-cuenta', c.cuenta);
    set('info-operacion', c.operacion);
    set('info-sicmact', c.credito_sicmact);
    set('info-saldo', soles(c.saldo_capital, c.moneda));
    set('info-desembolso', c.monto_desembolso ? soles(c.monto_desembolso, c.moneda) : null);
    set('info-atraso', c.dias_atraso + ' días');
    set('info-rango', c.rango_atraso);
    set('info-calif', c.calificacion_rcc);
    set('info-tipo', c.tipo_credito);
    set('info-modulo', c.modulo);
    set('info-estado', c.estado_cartera);
    set('info-conc12m', (c.conc_operacion_12m ?? c.veces_conciliado ?? 0) + ' de 4');
    set('info-garante', c.garante_nombre ? c.garante_nombre + (c.garante_celular ? ' · ' + c.garante_celular : '') : null);
    set('info-asesor', c.asesor_negocio);
    set('info-tipo-neg', etiqueta);
    set('info-autonomia', c.autonomia);
    set('info-lineamiento', c.lineamiento_campana);
    set('resumen-aprobador', c.aprobador_asignado ?? 'Sin aprobador disponible para este nivel');

    // Análisis de recaída: el cliente ya recayó tras un beneficio
    const rec = c.recaida_previa, avisoRec = document.getElementById('aviso-recaida');
    avisoRec.hidden = !rec;
    if (rec) {
      avisoRec.innerHTML = `<i class="bi bi-arrow-repeat"></i><span><strong>Este cliente recayó tras ${rec.ultimo ? 'su último beneficio' : 'un beneficio anterior'}
        (hace ${rec.dias} ${rec.dias === 1 ? 'día' : 'días'}).</strong> Pagó su beneficio el ${rec.fecha_beneficio} y volvió a atrasarse el ${rec.fecha_recaida}.
        Evalúe si una nueva negociación logrará normalizarlo antes de otorgar otro beneficio.</span>`;
    }

    // Modelo de priorización
    const pr = c.priorizacion, aviso = document.getElementById('aviso-prioridad');
    aviso.hidden = !pr;
    if (pr) {
      aviso.innerHTML = `<i class="bi bi-bullseye"></i><span><strong>Modelo de priorización: puntaje ${pr.score} · prioridad ${rctEsc(pr.segmento.toLowerCase())}.</strong>
        Acción sugerida: ${rctEsc(pr.accion_sugerida)} (mejor horario ${rctEsc(pr.mejor_horario)}). Probabilidad de pago ${Number(pr.probabilidad_pago).toFixed(1)}%.
        ${(pr.motivos || []).map(m => `<span class="pr-chip">${rctEsc(m)}</span>`).join(' ')}</span>`;
    }

    document.getElementById('input-cliente-id').value = c.id;
    document.getElementById('input-tipo-neg').value = codigo;
    document.getElementById('input-tipo-desc').value = etiqueta;

    ['panel-cuotas', 'panel-cancelacion', 'panel-judicial'].forEach(id => document.getElementById(id).hidden = true);
    document.getElementById(codigo === 'cuotas' ? 'panel-cuotas' : codigo === 'cancelacion' ? 'panel-cancelacion' : 'panel-judicial').hidden = false;
    if (codigo === 'cancelacion') {
      const tope = c.uit_referencia ? c.uit_referencia * 3 : 15450;
      document.getElementById('select-mayor-uit').value = parseFloat(c.saldo_capital) > tope ? '1' : '0';
    }

    document.getElementById('card-cliente').hidden = false;
    document.getElementById('card-propuesta').hidden = false;
    document.getElementById('card-cliente').scrollIntoView({ behavior: 'smooth', block: 'start' });
  };

  // ── Sustento: contador ─────────────────────────────────────
  const motivo = document.getElementById('motivo');
  motivo.addEventListener('input', () => {
    const n = motivo.value.trim().length;
    document.getElementById('motivo-hint').textContent = n < 20 ? `Mínimo 20 caracteres (${n}/20).` : `${n} caracteres.`;
  });
  document.getElementById('form-fud').addEventListener('submit', e => {
    const b = e.target.querySelector('button[type=submit]');
    b.disabled = true; b.innerHTML = '<span class="spinner"></span> Enviando…';
  });

  filtrar();
})();
</script>
@endpush
