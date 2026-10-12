  @extends('layouts.app')
  @section('title', 'Envío de FUD')

  @push('styles')
  <style>
  .campo-sistema {
      background: #f8f9fa !important;
      border: 1.5px solid #dee2e6 !important;
      color: #495057 !important;
      cursor: not-allowed !important;
      pointer-events: none;
  }
  .campo-calculado-rojo {
      background: #fff0f2 !important;
      border: 1.5px solid #f5c6cb !important;
      color: var(--rojo) !important;
      font-weight: 700 !important;
      cursor: not-allowed !important;
      pointer-events: none;
  }
  .campo-calculado-verde {
      background: #f0faf5 !important;
      border: 1.5px solid #b7dfc8 !important;
      color: var(--verde) !important;
      font-weight: 600 !important;
      cursor: not-allowed !important;
      pointer-events: none;
  }
  .campo-calculado-azul {
      background: #e8f0fe !important;
      border: 1.5px solid #b3c9f5 !important;
      color: #1a56a0 !important;
      font-weight: 700 !important;
      cursor: not-allowed !important;
      pointer-events: none;
  }
  .badge-sistema {
      display: inline-flex; align-items: center; gap: 4px;
      padding: 3px 8px; border-radius: 5px;
      font-size: 10px; font-weight: 700;
      background: #e8f0fe; color: #1a56a0;
      text-transform: uppercase; letter-spacing: 0.3px;
  }
  </style>
  @endpush

  @section('content')

  <div class="page-head">
    <div class="page-head-text">
      <h2>Envío de FUD</h2>
      <p>Registre la propuesta de conciliación para un cliente de la cartera de campaña.</p>
    </div>
    <span class="badge badge-info">{{ auth()->user()->rol->nombre }}</span>
  </div>

  {{-- PASO 1: BÚSQUEDA ─────────────────────────────────────── --}}
  <div class="card">
    <div class="card-title">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
      </svg>
      Paso 1 · Búsqueda de cliente en cartera de campaña
    </div>

    <div class="notice">
      Solo puede gestionar clientes incluidos en la base de campaña.
      <strong>El tipo de negociación, lineamiento y aprobador se asignan automáticamente</strong>
      según los datos del sistema — no son editables por el ejecutivo.
    </div>

    <div class="grid3" style="margin-top:16px">
      <div class="field">
        <label>Zona</label>
        <select id="filtro-zona" onchange="filtrarClientes()">
          <option value="">Todas las zonas</option>
          @foreach($zonas as $zona)
            <option value="{{ $zona->id }}">{{ $zona->nombre }}</option>
          @endforeach
        </select>
      </div>
      <div class="field">
        <label>Tienda</label>
        <select id="filtro-tienda" onchange="filtrarClientes()">
          <option value="">Todas las tiendas</option>
          @foreach($zonas as $zona)
            @foreach($zona->tiendas as $tienda)
              <option value="{{ $tienda->id }}" data-zona="{{ $zona->id }}">
                {{ $tienda->nombre }}
              </option>
            @endforeach
          @endforeach
        </select>
      </div>
      <div class="field">
        <label>Estado de cartera</label>
        <select id="filtro-estado" onchange="filtrarClientes()">
          <option value="">Todos</option>
          @foreach(['VENCIDO','JUDICIAL','CASTIGADO','EXTRAJUDICIAL'] as $e)
            <option value="{{ $e }}">{{ $e }}</option>
          @endforeach
        </select>
      </div>
      <div class="field">
        <label>Nro. Cuenta</label>
        <input id="filtro-cuenta" oninput="filtrarClientes()" placeholder="Buscar por cuenta">
      </div>
      <div class="field">
        <label>Nro. Operación</label>
        <input id="filtro-operacion" oninput="filtrarClientes()" placeholder="Buscar por operación">
      </div>
      <div class="field">
        <label>Resultados encontrados</label>
        <input id="match-count" readonly value="Cargando...">
      </div>
      <div class="field">
  <label>Nombre cliente</label>
  <input id="filtro-nombre" oninput="filtrarClientes()" placeholder="Buscar por nombre">
</div>
<div class="field">
  <label>DNI / RUC / CE</label>
  <input id="filtro-documento" oninput="filtrarClientes()" placeholder="Buscar por documento">
</div>
    </div>

    <div style="overflow-x:auto;margin-top:16px">
      <table>
        <thead>
          <tr>
            <th>Zona</th>
            <th>Tienda</th>
            <th>Cuenta</th>
            <th>Operación</th>
            <th>Cliente</th>
            <th>Tipo crédito</th>
            <th>Saldo Capital</th>
            <th>Días Atraso</th>
            <th>Calificación</th>
            <th>Estado</th>
            <th>Tipo Negociación</th>
            <th>Autonomía</th>
            <th>Acción</th>
          </tr>
        </thead>
        <tbody id="tabla-clientes">
          <tr>
            <td colspan="13" style="text-align:center;padding:24px;color:#999">
              Cargando clientes...
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

  {{-- PASO 2: DATOS CLIENTE (todos readonly, vienen del sistema) ── --}}
  <div class="card" id="card-cliente" style="display:none">
    <div class="card-title">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/>
        <circle cx="12" cy="7" r="4"/>
      </svg>
      Paso 2 · Datos del cliente seleccionado
      <span class="badge-sistema" style="margin-left:8px">Solo lectura — datos del sistema</span>
    </div>

    {{-- Fila 1: Identificación --}}
    <div class="grid3" style="margin-bottom:12px">
      <div class="field">
        <label>Cliente</label>
        <input id="info-cliente" readonly class="campo-sistema">
      </div>
      <div class="field">
        <label>Documento</label>
        <input id="info-documento" readonly class="campo-sistema">
      </div>
      <div class="field">
        <label>Teléfono</label>
        <input id="info-telefono" readonly class="campo-sistema">
      </div>
      <div class="field">
        <label>Nro. Cuenta</label>
        <input id="info-cuenta" readonly class="campo-sistema">
      </div>
      <div class="field">
        <label>Nro. Operación</label>
        <input id="info-operacion" readonly class="campo-sistema">
      </div>
      <div class="field">
        <label>Crédito SICMACT</label>
        <input id="info-sicmact" readonly class="campo-sistema">
      </div>
    </div>

    {{-- Fila 2: Datos del crédito --}}
    <div class="grid3" style="margin-bottom:12px">
      <div class="field">
        <label>Saldo Capital</label>
        <input id="info-saldo" readonly class="campo-sistema">
      </div>
      <div class="field">
        <label>Monto desembolsado</label>
        <input id="info-desembolso" readonly class="campo-sistema">
      </div>
      <div class="field">
        <label>Moneda</label>
        <input id="info-moneda" readonly class="campo-sistema">
      </div>
      <div class="field">
        <label>Días de atraso</label>
        <input id="info-atraso" readonly class="campo-sistema">
      </div>
      <div class="field">
        <label>Rango de atraso</label>
        <input id="info-rango" readonly class="campo-sistema">
      </div>
      <div class="field">
        <label>Calificación RCC</label>
        <input id="info-calif" readonly class="campo-sistema">
      </div>
      <div class="field">
        <label>Tipo de crédito</label>
        <input id="info-tipo" readonly class="campo-sistema">
      </div>
      <div class="field">
        <label>Módulo</label>
        <input id="info-modulo" readonly class="campo-sistema">
      </div>
      <div class="field">
        <label>Estado cartera</label>
        <input id="info-estado" readonly class="campo-sistema">
      </div>
    </div>

    {{-- Fila 3: Conciliaciones históricas --}}
    <div class="grid3" style="margin-bottom:12px">
      <div class="field">
        <label>Conc. operación últimos 12m</label>
        <input id="info-conc12m" readonly class="campo-sistema">
      </div>
      <div class="field">
        <label>Conc. operación campaña anterior</label>
        <input id="info-concamp" readonly class="campo-sistema">
      </div>
      <div class="field">
        <label>Conc. operación mes actual</label>
        <input id="info-concmes" readonly class="campo-sistema">
      </div>
    </div>

    {{-- Fila 4: Calculados por el sistema (highlight especial) --}}
    <div style="background:#f8f9fa;border-radius:10px;padding:16px;border:1px solid var(--borde)">
      <div style="font-size:11px;font-weight:700;color:#888;text-transform:uppercase;
                  letter-spacing:0.5px;margin-bottom:12px;display:flex;align-items:center;gap:6px">
        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <circle cx="12" cy="12" r="10"/>
          <line x1="12" y1="8" x2="12" y2="12"/>
          <line x1="12" y1="16" x2="12.01" y2="16"/>
        </svg>
        Campos calculados por el sistema — el ejecutivo no puede modificarlos
      </div>
      <div class="grid3">
        <div class="field" style="grid-column:span 2">
          <label>Tipo de negociación</label>
          <input id="info-tipo-neg" readonly class="campo-calculado-rojo">
        </div>
        <div class="field">
          <label>Autonomía requerida</label>
          <input id="info-autonomia" readonly class="campo-calculado-azul">
        </div>
        <div class="field" style="grid-column:span 3">
          <label>Lineamiento de campaña</label>
          <input id="info-lineamiento" readonly class="campo-calculado-verde">
        </div>
      </div>
    </div>

    {{-- Garante y asesor --}}
    <div class="grid2" style="margin-top:12px">
      <div class="field">
        <label>Garante</label>
        <input id="info-garante" readonly class="campo-sistema">
      </div>
      <div class="field">
        <label>Asesor de negocio (originó el crédito)</label>
        <input id="info-asesor" readonly class="campo-sistema">
      </div>
    </div>
  </div>

  {{-- PASO 3: PROPUESTA FUD ──────────────────────────────────── --}}
  <div class="card" id="card-propuesta" style="display:none">
    <div class="card-title">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
        <polyline points="14 2 14 8 20 8"/>
      </svg>
      Paso 3 · Propuesta de exoneración (FUD)
    </div>

    <form method="POST" action="{{ route('fud.store') }}" id="form-fud" enctype="multipart/form-data">
      @csrf

      {{-- ── Campos ocultos: vienen del sistema, no los elige el ejecutivo ── --}}
      <input type="hidden" name="cliente_cartera_id"    id="input-cliente-id">
      <input type="hidden" name="tipo_negociacion"      id="input-tipo-neg">
      <input type="hidden" name="tipo_negociacion_desc" id="input-tipo-desc">

      {{-- ── Resumen de lo que el sistema determinó ── --}}
      <div style="background:#f8f9fa;border-radius:10px;padding:16px;
                  border:1px solid var(--borde);margin-bottom:20px">
        <div style="font-size:11px;font-weight:700;color:#888;text-transform:uppercase;
                    letter-spacing:0.5px;margin-bottom:12px">
          Resumen determinado por el sistema
        </div>
        <div class="grid3">
          <div>
            <div style="font-size:10px;color:#aaa;text-transform:uppercase;
                        letter-spacing:0.4px;margin-bottom:3px">Tipo de negociación</div>
            <div id="resumen-tipo-neg"
                style="font-size:13px;font-weight:700;color:var(--rojo)">—</div>
          </div>
          <div>
            <div style="font-size:10px;color:#aaa;text-transform:uppercase;
                        letter-spacing:0.4px;margin-bottom:3px">Aprobador requerido</div>
            <div id="resumen-autonomia"
                style="font-size:13px;font-weight:700;color:#1a56a0">—</div>
          </div>
          <div>
            <div style="font-size:10px;color:#aaa;text-transform:uppercase;
                        letter-spacing:0.4px;margin-bottom:3px">Lineamiento</div>
            <div id="resumen-lineamiento"
                style="font-size:13px;font-weight:600;color:var(--verde)">—</div>
          </div>
        </div>
      </div>

      {{-- ── El aprobador lo asigna el sistema; el ejecutivo solo elige tipo de operación y motivo ── --}}
      <div class="grid3" style="margin-bottom:20px">
        <div class="field" style="grid-column:span 2">
          <label>Aprobador asignado automáticamente</label>
          <div id="resumen-aprobador"
               style="padding:9px 12px;border:1px solid var(--borde);border-radius:8px;
                      background:#f8f9fa;font-size:13px;font-weight:600;color:#1a56a0">—</div>
          <span style="font-size:11px;color:#888">
            Asignado por el sistema según el nivel de autonomía del crédito y la zona.
          </span>
        </div>

        <div class="field">
          <label>Tipo Operación</label>
          <select name="tipo_operacion">
            <option value="CAMPAÑA">CAMPAÑA</option>
            <option value="ORDINARIA">ORDINARIA</option>
            <option value="ESPECIAL">ESPECIAL</option>
          </select>
        </div>
      </div>

      {{-- ── Panel Cuotas (aparece automáticamente según tipo del cliente) ── --}}
      <div id="panel-cuotas" style="display:none;margin-bottom:20px">
        <div class="card-plain" style="background:#fafafa;border:1px solid #eee">
          <strong style="font-size:13px;color:var(--rojo)">Detalle — Pago de Cuotas</strong>
          <div class="grid3" style="margin-top:12px">
            <div class="field">
              <label>Cuota desde</label>
              <input type="number" name="cuota_desde" min="1" placeholder="Ej: 3">
            </div>
            <div class="field">
              <label>Cuota hasta</label>
              <input type="number" name="cuota_hasta" min="1" placeholder="Ej: 5">
            </div>
            <div class="field">
              <label>% Descuento Interés</label>
              <input type="number" name="desc_interes" min="0" max="100" placeholder="%">
            </div>
            <div class="field">
              <label>% Descuento Moratorio</label>
              <input type="number" name="desc_moratorio" value="100" min="0" max="100">
            </div>
            <div class="field">
              <label>¿Pone crédito al día?</label>
              <select name="pone_al_dia">
                <option value="0">NO</option>
                <option value="1">SÍ – hasta 80% de interés</option>
              </select>
            </div>
            <div class="field">
              <label>¿Cierra mes en vigente?</label>
              <select name="cierra_mes_vigente">
                <option value="1">SÍ</option>
                <option value="0">NO</option>
              </select>
            </div>
          </div>
          <div class="notice-danger" style="margin-top:12px;font-size:12px">
            ⚠ El monto a conciliar <strong>no debe superar el 50%</strong> del valor de la cuota pactada.
          </div>
        </div>
      </div>

      {{-- ── Panel Cancelación Total ── --}}
      <div id="panel-cancelacion" style="display:none;margin-bottom:20px">
        <div class="card-plain" style="background:#fafafa;border:1px solid #eee">
          <strong style="font-size:13px;color:var(--rojo)">Detalle — Cancelación Total</strong>
          <div class="grid3" style="margin-top:12px">
            <div class="field">
              <label>Monto a pagar</label>
              <input name="monto_a_pagar" placeholder="S/ 0.00">
            </div>
            <div class="field">
              <label>% Descuento Interés</label>
              <input type="number" name="desc_interes_canc" min="0" max="100" placeholder="%">
            </div>
            <div class="field">
              <label>% Descuento Capital</label>
              <input type="number" name="desc_capital" min="0" max="50" placeholder="%">
            </div>
            <div class="field">
              <label>Fecha límite de pago</label>
              <input type="date" name="fecha_limite">
            </div>
            <div class="field">
              <label>¿Saldo capital > 3 UIT?</label>
              <select name="mayor_tres_uit" id="select-mayor-uit">
                <option value="0">NO</option>
                <option value="1">SÍ – adjuntar Informe-Propuesta</option>
              </select>
            </div>
            <div class="field">
              <label>¿Búsqueda registral SUNARP?</label>
              <select name="sunarp_positivo">
                <option value="0">Negativa</option>
                <option value="1">Positiva – adjuntar Informe</option>
              </select>
            </div>
          </div>
          <div class="notice" style="margin-top:12px;font-size:12px">
            Pago fraccionado: máx. 90 días desde primera amortización.
          </div>
        </div>
      </div>

      {{-- ── Panel Judicial / Castigada / FOCMAC ── --}}
      <div id="panel-judicial" style="display:none;margin-bottom:20px">
        <div class="card-plain" style="background:#fafafa;border:1px solid #eee">
          <strong style="font-size:13px;color:var(--rojo)">Detalle — Cartera Especial</strong>
          <div class="grid3" style="margin-top:12px">
            <div class="field">
              <label>Monto del Gravamen / Embargo</label>
              <input name="monto_gravamen" placeholder="S/ — si aplica">
            </div>
            <div class="field">
              <label>Etapa procesal</label>
              <select name="etapa_procesal">
                <option>Pre-judicial</option>
                <option>Ejecución de garantías</option>
                <option>Medidas cautelares</option>
                <option>Etapa de remate</option>
              </select>
            </div>
            <div class="field">
              <label>Beneficio propuesto (máx. 70% SK)</label>
              <input type="number" name="beneficio_pct_sk" max="70" placeholder="%">
            </div>
          </div>
          <div class="notice" style="margin-top:12px;font-size:12px">
            SK &lt; 3 UITs: autoriza CZR. SK &gt; 3 UITs: autoriza Jefe de Recuperaciones.
          </div>
        </div>
      </div>

      {{-- ── Motivo ── --}}
      <div class="field" style="margin-bottom:20px">
        <label>
          Motivo / Factor de mora (sustento obligatorio)
          <span style="color:var(--rojo)">*</span>
        </label>
        <textarea name="motivo" rows="5" required
          placeholder="Describa la situación del cliente: afectación económica, voluntad de pago, historial de pagos, coordenadas de contacto realizados, etc."></textarea>
      </div>

      {{-- ── Adjuntos ── --}}
      <div class="card-plain" style="border-left:4px solid var(--rojo);margin-bottom:20px">
        <div style="display:flex;align-items:center;gap:8px;
                    font-size:13px;font-weight:700;color:var(--rojo);margin-bottom:4px">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none"
              stroke="currentColor" stroke-width="2">
            <path d="M21.44 11.05l-9.19 9.19a6 6 0 01-8.49-8.49l9.19-9.19a4 4 0 015.66 5.66l-9.2 9.19a2 2 0 01-2.83-2.83l8.49-8.48"/>
          </svg>
          Documentos adjuntos
        </div>
        <div style="font-size:12px;color:var(--gris);margin-bottom:14px">
          Adjunta los documentos que sustentan la propuesta.
          PDF, imagen, Excel o Word · Máx. 10MB por archivo.
        </div>

        <div id="lista-adjuntos" style="display:flex;flex-direction:column;gap:8px;margin-bottom:12px"></div>

        <input type="file" id="input-adjuntos" name="adjuntos[]" multiple
              accept=".pdf,.jpg,.jpeg,.png,.xlsx,.docx"
              style="display:none"
              onchange="agregarArchivos(this.files)">

        <button type="button" class="btn btn-outline btn-sm"
                onclick="document.getElementById('input-adjuntos').click()">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none"
              stroke="currentColor" stroke-width="2">
            <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/>
            <polyline points="17 8 12 3 7 8"/>
            <line x1="12" y1="3" x2="12" y2="15"/>
          </svg>
          Seleccionar archivos
        </button>

        <div class="notice" style="margin-top:14px;font-size:12px">
          <strong>Documentos según tipo:</strong>
          Acuerdo de Pago · Nota de Abono · FUD firmado ·
          Informe-Propuesta (SK &gt; 3 UIT) · Búsqueda SUNARP (si aplica).
        </div>
      </div>

      <div class="actions">
        <a href="{{ route('dashboard') }}" class="btn btn-ghost">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none"
              stroke="currentColor" stroke-width="2">
            <line x1="19" y1="12" x2="5" y2="12"/>
            <polyline points="12 19 5 12 12 5"/>
          </svg>
          Cancelar
        </a>
        <button type="submit" class="btn btn-primary">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none"
              stroke="currentColor" stroke-width="2">
            <line x1="22" y1="2" x2="11" y2="13"/>
            <polygon points="22 2 15 22 11 13 2 9 22 2"/>
          </svg>
          Enviar FUD al nivel de autonomía
        </button>
      </div>
    </form>
  </div>

  @endsection

  @push('scripts')
  <script>
  // ── Adjuntos múltiples ──────────────────────────────────────
  let archivosSeleccionados = new DataTransfer();

  function agregarArchivos(nuevos) {
    for (const archivo of nuevos) {
      const yaExiste = Array.from(archivosSeleccionados.files)
        .some(f => f.name === archivo.name && f.size === archivo.size);
      if (!yaExiste) archivosSeleccionados.items.add(archivo);
    }
    document.getElementById('input-adjuntos').files = archivosSeleccionados.files;
    renderizarAdjuntos();
  }

  function quitarArchivo(nombre, size) {
    const nuevos = new DataTransfer();
    for (const f of archivosSeleccionados.files) {
      if (!(f.name === nombre && f.size === size)) nuevos.items.add(f);
    }
    archivosSeleccionados = nuevos;
    document.getElementById('input-adjuntos').files = archivosSeleccionados.files;
    renderizarAdjuntos();
  }

  function renderizarAdjuntos() {
    const lista    = document.getElementById('lista-adjuntos');
    const archivos = Array.from(archivosSeleccionados.files);
    if (!archivos.length) { lista.innerHTML = ''; return; }

    lista.innerHTML = archivos.map(f => {
      const kb      = (f.size / 1024).toFixed(1);
      const ext     = f.name.split('.').pop().toUpperCase();
      const colores = { PDF:'#c0392b', XLSX:'#1a7a4a', DOCX:'#1a56a0',
                        JPG:'#7b2d8b', JPEG:'#7b2d8b', PNG:'#7b2d8b' };
      const color   = colores[ext] ?? '#555';
      return `
        <div style="display:flex;align-items:center;justify-content:space-between;
                    background:#f8f9fa;border:1px solid var(--borde);border-radius:8px;
                    padding:9px 12px;gap:10px">
          <div style="display:flex;align-items:center;gap:8px;min-width:0">
            <div style="width:28px;height:28px;border-radius:6px;background:${color}18;
                        display:flex;align-items:center;justify-content:center;flex-shrink:0">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none"
                  stroke="${color}" stroke-width="2">
                <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
                <polyline points="14 2 14 8 20 8"/>
              </svg>
            </div>
            <div style="min-width:0">
              <div style="font-size:12px;font-weight:600;color:#333;
                          white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                ${f.name}
              </div>
              <div style="font-size:11px;color:#999">${ext} · ${kb} KB</div>
            </div>
          </div>
          <button type="button" onclick="quitarArchivo('${f.name}', ${f.size})"
            style="background:none;border:none;cursor:pointer;color:#c0392b;
                  padding:4px;border-radius:4px;display:flex;align-items:center"
            title="Quitar">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2">
              <line x1="18" y1="6" x2="6" y2="18"/>
              <line x1="6" y1="6" x2="18" y2="18"/>
            </svg>
          </button>
        </div>`;
    }).join('');
  }

  // ── API y filtros ───────────────────────────────────────────
  const API_URL = '{{ route("api.clientes") }}';
  let clienteSeleccionado = null;

async function filtrarClientes() {
  const zona      = document.getElementById('filtro-zona').value;
  const tienda    = document.getElementById('filtro-tienda').value;
  const estado    = document.getElementById('filtro-estado').value;
  const cuenta    = document.getElementById('filtro-cuenta').value;
  const operacion = document.getElementById('filtro-operacion').value;
  const nombre    = document.getElementById('filtro-nombre').value;
  const documento = document.getElementById('filtro-documento').value;

  const params = new URLSearchParams({
    zona_id: zona, tienda_id: tienda, estado,
    cuenta, operacion, nombre, documento
  }); 

    const res      = await fetch(`${API_URL}?${params}`, {
      headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }
    });
    const clientes = await res.json();

    document.getElementById('match-count').value = clientes.length + ' registros';
    const tbody = document.getElementById('tabla-clientes');

    if (!clientes.length) {
      tbody.innerHTML = `<tr><td colspan="13"
        style="text-align:center;color:#999;padding:24px">
        Sin resultados para los filtros aplicados.
      </td></tr>`;
      return;
    }

    tbody.innerHTML = clientes.map(c => {
      const conc12m  = c.conc_operacion_12m ?? c.veces_conciliado ?? 0;
      const bloq     = conc12m >= 4;
      const cercano  = conc12m === 3;
      const simbolo  = (c.moneda ?? 'PEN') === 'USD' ? 'US$' : 'S/';
      const saldo    = parseFloat(c.saldo_capital)
                        .toLocaleString('es-PE', { minimumFractionDigits: 2 });
      const tipoNeg  = ETIQUETAS_NEG[c.tipo_negociacion_campana] ?? c.tipo_negociacion_campana ?? '—';
      const autonomia = c.autonomia ?? '—';

      // Badge de disponibilidad
      let badgeDisp = '';
      if (bloq) {
        badgeDisp = '<span class="badge badge-danger" style="font-size:10px">⛔ Límite 4x</span>';
      } else if (cercano) {
        badgeDisp = '<span class="badge badge-mostaza" style="font-size:10px">⚠ 3/4</span>';
      }

      return `<tr>
        <td style="font-size:12px">${c.zona?.nombre ?? '—'}</td>
        <td style="font-size:12px">${c.tienda?.nombre ?? '—'}</td>
        <td style="font-size:12px">${c.cuenta}</td>
        <td style="font-size:12px">${c.operacion}</td>
        <td><strong style="font-size:12px">${c.nombre_cliente}</strong></td>
        <td style="font-size:11px">${c.tipo_credito}</td>
        <td style="font-size:12px"><strong>${simbolo} ${saldo}</strong></td>
        <td>
          <span class="badge ${c.dias_atraso > 90 ? 'badge-danger' : 'badge-pending'}"
                style="font-size:10px">
            ${c.dias_atraso}d
          </span>
        </td>
        <td><span class="badge badge-info" style="font-size:10px">${c.calificacion_rcc}</span></td>
        <td><span class="badge badge-gray" style="font-size:10px">${c.estado_cartera}</span></td>
        <td style="font-size:11px;color:var(--rojo);font-weight:600;max-width:160px">
          ${tipoNeg}
        </td>
        <td>
          <span class="badge badge-info" style="font-size:10px">${autonomia}</span>
          ${badgeDisp}
        </td>
        <td>
          <button class="btn btn-primary btn-sm"
            onclick='seleccionar(${JSON.stringify(c)})'
            ${bloq ? 'disabled title="Supera límite de 4 conciliaciones en 12 meses"' : ''}>
            Seleccionar
          </button>
        </td>
      </tr>`;
    }).join('');
  }

  // ── Seleccionar cliente — rellena TODO automáticamente ──────
  function seleccionar(c) {
    clienteSeleccionado = c;

    // ── Paso 2: datos básicos ──
    document.getElementById('info-cliente').value    = c.nombre_cliente;
    document.getElementById('info-sicmact').value    = c.credito_sicmact ?? '—';
    document.getElementById('info-documento').value  =
      ((c.tipo_documento ?? '') + ' ' + (c.nro_documento ?? '')).trim() || '—';
    document.getElementById('info-telefono').value   = c.telefono ?? '—';
    document.getElementById('info-cuenta').value     = c.cuenta;
    document.getElementById('info-operacion').value  = c.operacion;

    const simbolo = (c.moneda ?? 'PEN') === 'USD' ? 'US$' : 'S/';
    document.getElementById('info-saldo').value =
      simbolo + ' ' + parseFloat(c.saldo_capital)
        .toLocaleString('es-PE', { minimumFractionDigits: 2 });
    document.getElementById('info-desembolso').value =
      c.monto_desembolso
        ? simbolo + ' ' + parseFloat(c.monto_desembolso)
            .toLocaleString('es-PE', { minimumFractionDigits: 2 })
        : '—';
    document.getElementById('info-moneda').value     = c.moneda ?? 'PEN';
    document.getElementById('info-atraso').value     = c.dias_atraso + ' días';
    document.getElementById('info-rango').value      = c.rango_atraso ?? calcularRango(c.dias_atraso);
    document.getElementById('info-calif').value      = c.calificacion_rcc;
    document.getElementById('info-tipo').value       = c.tipo_credito;
    document.getElementById('info-modulo').value     = c.modulo ?? '—';
    document.getElementById('info-estado').value     = c.estado_cartera;

    // Conciliaciones
    const conc12m = c.conc_operacion_12m ?? c.veces_conciliado ?? 0;
    const concEl  = document.getElementById('info-conc12m');
    concEl.value  = conc12m + ' veces';
    // Alerta visual si está cerca del límite
    if (conc12m === 3) {
      concEl.style.background   = '#fff3cd';
      concEl.style.color        = '#856404';
      concEl.style.borderColor  = '#ffc107';
    } else {
      concEl.style.background   = '';
      concEl.style.color        = '';
      concEl.style.borderColor  = '';
    }
    document.getElementById('info-concamp').value =
      (c.conc_operacion_camp_ant ?? 0) + ' veces';
    document.getElementById('info-concmes').value =
      (c.conc_operacion_mes_actual ?? 0) + ' veces';

    // ── Calculados por el sistema (readonly highlight) ──
    const tipoNegTexto = ETIQUETAS_NEG[c.tipo_negociacion_campana] ?? c.tipo_negociacion_campana ?? '—';
    const lineamiento  = c.lineamiento_campana      ?? '—';
    const autonomia    = c.autonomia                ?? '—';

    document.getElementById('info-tipo-neg').value   = tipoNegTexto;
    document.getElementById('info-lineamiento').value = lineamiento;
    document.getElementById('info-autonomia').value  = autonomia;

    // Garante y asesor
    document.getElementById('info-garante').value =
      c.garante_nombre
        ? (c.garante_nombre + (c.garante_celular ? ' · ' + c.garante_celular : ''))
        : '—';
    document.getElementById('info-asesor').value = c.asesor_negocio ?? '—';

    // ── Paso 3: campos hidden del sistema ──
    document.getElementById('input-cliente-id').value = c.id;

    const codigo = calcularCodigo(tipoNegTexto);
    document.getElementById('input-tipo-neg').value  = codigo;
    document.getElementById('input-tipo-desc').value = tipoNegTexto;

    // ── Resumen visual en el paso 3 ──
    document.getElementById('resumen-tipo-neg').textContent    = tipoNegTexto;
    document.getElementById('resumen-autonomia').textContent   = autonomia;
    document.getElementById('resumen-lineamiento').textContent = lineamiento;

    // ── Mostrar panel de detalle automáticamente ──
    mostrarPanel(codigo);

    // ── Auto-detectar mayor 3 UIT para cancelación ──
    if (codigo === 'cancelacion') {
      const uitRef  = c.uit_referencia ? c.uit_referencia * 3 : 15450;
      const esMayor = parseFloat(c.saldo_capital) > uitRef;
      const sel     = document.getElementById('select-mayor-uit');
      if (sel) sel.value = esMayor ? '1' : '0';
    }

    // ── Aprobador asignado por el sistema ──
    document.getElementById('resumen-aprobador').textContent =
      c.aprobador_asignado ?? 'Sin aprobador disponible para este nivel';

    // Mostrar los pasos 2 y 3
    document.getElementById('card-cliente').style.display   = 'block';
    document.getElementById('card-propuesta').style.display = 'block';
    document.getElementById('card-cliente').scrollIntoView({ behavior: 'smooth' });
  }

  const ETIQUETAS_NEG = {
    cuotas:        'Pago de Cuotas',
    cancelacion:   'Cancelación Total',
    extrajudicial: 'Cartera Extrajudicial / Judicial',
    castigada:     'Cartera Castigada',
    focmac:        'Cartera FOCMAC',
  };

  function calcularCodigo(texto) {
    texto = (texto ?? '').toLowerCase();
    // La cartera ya trae el código (cuotas, cancelacion, …)
    if (ETIQUETAS_NEG[texto]) return texto;
    if (texto.includes('cancelacion total'))  return 'cancelacion';
    if (texto.includes('pago de cuotas'))     return 'cuotas';
    if (texto.includes('judicial'))           return 'extrajudicial';
    if (texto.includes('castigada'))          return 'castigada';
    if (texto.includes('focmac'))             return 'focmac';
    return 'cancelacion';
  }

  function calcularRango(dias) {
    if (dias <= 30)  return '1-30';
    if (dias <= 60)  return '31-60';
    if (dias <= 90)  return '61-90';
    if (dias <= 180) return '91-180';
    if (dias <= 365) return '181-365';
    return '>365';
  }

  // ── Muestra el panel de detalle según código ──
  function mostrarPanel(codigo) {
    ['panel-cuotas', 'panel-cancelacion', 'panel-judicial'].forEach(id => {
      document.getElementById(id).style.display = 'none';
    });
    if (codigo === 'cuotas')      document.getElementById('panel-cuotas').style.display      = 'block';
    if (codigo === 'cancelacion') document.getElementById('panel-cancelacion').style.display = 'block';
    if (['extrajudicial', 'castigada', 'focmac'].includes(codigo))
      document.getElementById('panel-judicial').style.display = 'block';
  }

  // ── Filtrar tiendas al cambiar zona ──
  document.getElementById('filtro-zona').addEventListener('change', function () {
    const zonaId = this.value;
    document.querySelectorAll('#filtro-tienda option[data-zona]').forEach(opt => {
      opt.style.display = (!zonaId || opt.dataset.zona === zonaId) ? '' : 'none';
    });
    document.getElementById('filtro-tienda').value = '';
    filtrarClientes();
  });

  // Carga inicial
  filtrarClientes();
  </script>
  @endpush