{{-- resources/views/campana/lineamientos.blade.php --}}
@extends('layouts.app')

@section('title', 'Lineamientos · Campaña Sin Atrasos')

@section('content')

{{-- ══════════════════════════════════════════════════════════════════
     MENÚ DE NAVEGACIÓN INTERNO (FIJO / STICKY FLOTANTE SUPERIOR)
══════════════════════════════════════════════════════════════════ --}}
<div class="sticky-top bg-white border-bottom shadow-sm py-2 px-3 mb-4 rounded-bottom" style="top: 66px; z-index: 1030;">
    <div class="container-fluid">
        <nav class="nav nav-pills nav-fill small gap-1 flex-nowrap overflow-auto" style="white-space: nowrap;">
            <a class="nav-link text-dark py-1 px-2 fw-semibold" href="#sec-banner">Inicio</a>
            <a class="nav-link text-dark py-1 px-2 fw-semibold" href="#sec-elegibilidad">Elegibilidad</a>
            <a class="nav-link text-dark py-1 px-2 fw-semibold" href="#sec-calculadora">Calculadora</a>
            <a class="nav-link text-dark py-1 px-2 fw-semibold" href="#sec-autonomia">Autonomía</a>
            <a class="nav-link text-dark py-1 px-2 fw-semibold" href="#sec-documentos">Documentos</a>
            <a class="nav-link text-dark py-1 px-2 fw-semibold" href="#sec-exclusiones">Exclusiones</a>
            <a class="nav-link text-dark py-1 px-2 fw-semibold" href="#sec-reglas">Reglas Críticas</a>
            <a class="nav-link text-dark py-1 px-2 fw-semibold" href="#sec-flujo">Flujo Operativo</a>
        </nav>
    </div>
</div>

<div class="container-fluid px-0">

    {{-- PAGE HEAD --}}
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
        <div>
            <h2 class="h4 fw-bold text-dark mb-1">Lineamientos de Campaña</h2>
            <p class="text-muted small mb-0">Sin Atrasos, Que Atrasan · Departamento de Recuperaciones</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-light text-dark border px-3 py-2 fw-semibold">
                📅 01 Sep 2026 – 28 Feb 2027
            </span>
            @php
                $fin = \Carbon\Carbon::parse('2027-02-28');
                $dias = (int) now()->diffInDays($fin, false);
            @endphp
            <span class="badge {{ $dias <= 30 ? 'bg-danger text-white' : 'bg-secondary text-white' }} px-3 py-2 fw-semibold">
                ⏳ {{ $dias > 0 ? $dias . ' días restantes' : 'Campaña vencida' }}
            </span>
        </div>
    </div>


    {{-- ══════════════════════════════════════════════════════
         BLOQUE 1 — BANNER DE CAMPAÑA
    ════════════════════════════════════════════════════════ --}}
    <div id="sec-banner" class="card border shadow-sm rounded-3 mb-4 bg-white">
        <div class="card-body p-4">
            <div class="row align-items-center justify-content-between g-3">
                <div class="col-lg-8">
                    <span class="text-uppercase text-muted fw-bold tracking-wider" style="font-size: 11px;">Campaña Activa · Recuperaciones</span>
                    <h3 class="fw-bold text-dark mb-1" style="font-size: 1.5rem;">"Sin Atrasos, Que Atrasan"</h3>
                    <p class="text-secondary small mb-0">Vigencia oficial: 01 de septiembre de 2026 al 28 de febrero de 2027.</p>
                </div>
                <div class="col-lg-4 text-lg-end">
                    <span class="badge bg-light text-secondary border px-3 py-2">Norma Supletoria: Manual de Gestión</span>
                </div>
            </div>
            <hr class="text-muted opacity-25 my-3">
            <div class="row g-3 small text-secondary">
                <div class="col-md-6">
                    <strong class="text-dark">Objetivo 1:</strong> Disminuir el gasto de provisiones reduciendo la cartera en mora mediante beneficios a clientes en cartera vencida, refinanciada, extrajudicial y judicial.
                </div>
                <div class="col-md-6">
                    <strong class="text-dark">Objetivo 2:</strong> Incrementar los ingresos financieros percibidos mediante la recuperación de créditos en morosidad, cartera castigada y cartera FOCMAC.
                </div>
            </div>
        </div>
    </div>


{{-- ══════════════════════════════════════════════════════
     BLOQUE 2 — ELEGIBILIDAD (SEMÁFORO RCC ARMONIZADO)
════════════════════════════════════════════════════════ --}}
<div id="sec-elegibilidad" class="card border shadow-sm rounded-3 mb-4 p-4 bg-white">
    <h4 class="h6 fw-bold text-dark text-uppercase pb-2 mb-3 border-bottom">1. Criterios de Elegibilidad</h4>
    
    <div class="row g-3 mb-3">
        <div class="col-md-6">
            <div class="p-3 bg-light rounded-2 border h-100">
                <h6 class="fw-bold text-dark mb-1 small text-uppercase">Inclusión en base</h6>
                <p class="text-secondary small mb-0">El cliente debe figurar en la base mensual remitida por Recuperaciones. Inclusiones extraordinarias requieren visto bueno de Jefatura.</p>
            </div>
        </div>
        <div class="col-md-6">
            <div class="p-3 bg-light rounded-2 border h-100">
                <h6 class="fw-bold text-dark mb-1 small text-uppercase">Límite de uso</h6>
                <p class="text-secondary small mb-0">Máximo <strong>4 veces por cada 12 meses</strong> (incluye campañas anteriores). Aplica para calificaciones Normal, CPP, Deficiente, Dudoso y Pérdida.</p>
            </div>
        </div>
        <div class="col-md-6">
            <div class="p-3 bg-light rounded-2 border h-100">
                <h6 class="fw-bold text-dark mb-1 small text-uppercase">Calificaciones RCC permitidas</h6>
                <div class="d-flex gap-2 flex-wrap mt-2">
                    <span class="badge text-white px-2 py-1" style="background-color: #334f34;">NORMAL</span>
                    <span class="badge text-white px-2 py-1" style="background-color: #06354e;">CPP</span>
                    <span class="badge text-white px-2 py-1" style="background-color: #92442a;">DEFICIENTE</span>
                    <span class="badge text-white px-2 py-1" style="background-color: #610f04;">DUDOSO</span>
                    <span class="badge text-white px-2 py-1" style="background-color: #6d0000;">PÉRDIDA</span>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="p-3 bg-light rounded-2 border h-100">
                <h6 class="fw-bold text-dark mb-1 small text-uppercase">Múltiples créditos en mora</h6>
                <p class="text-secondary small mb-0">Si el cliente posee más de un crédito en mora, se gestionará la negociación conjunta de <strong>todos los créditos</strong> afectados.</p>
            </div>
        </div>
    </div>

    <div class="alert alert-light border small text-secondary mb-0">
        <strong>Excepción al límite de 4 veces:</strong> Calificación CPP/Deficiente requiere aprobación del Jefe de Recuperaciones. Dudoso/Pérdida requiere autorización del Coordinador Zonal por correo con copia a Jefatura.
    </div>
</div>


    {{-- ══════════════════════════════════════════════════════
         BLOQUE 3 — CALCULADORA DE BENEFICIO
    ════════════════════════════════════════════════════════ --}}
    <div id="sec-calculadora" class="card border shadow-sm rounded-3 mb-4 p-4 bg-white">
        <h4 class="h6 fw-bold text-dark text-uppercase pb-2 mb-2 border-bottom">2. Calculadora de Beneficio Máximo</h4>
        <p class="text-muted small mb-3">Seleccione los parámetros operativos para conocer el porcentaje máximo de exoneración aplicable.</p>

        <div class="row g-3 mb-3">
            <div class="col-md-4">
                <label class="form-label small fw-bold text-secondary">Tipo de cartera</label>
                <select id="calc_cartera" class="form-select form-select-sm" onchange="calcularBeneficio()">
                    <option value="">— Seleccionar —</option>
                    <option value="vigente_vencida">Vigente / Vencida / Reprogramada</option>
                    <option value="refinanciada">Refinanciada</option>
                    <option value="judicial">Judicial / Extrajudicial</option>
                    <option value="castigada">Castigada</option>
                    <option value="focmac">FOCMAC</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-bold text-secondary">Calificación RCC</label>
                <select id="calc_calificacion" class="form-select form-select-sm" onchange="calcularBeneficio()">
                    <option value="">— Seleccionar —</option>
                    <option value="normal">Normal</option>
                    <option value="cpp">CPP</option>
                    <option value="deficiente">Deficiente</option>
                    <option value="dudoso">Dudoso</option>
                    <option value="perdida">Pérdida</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-bold text-secondary">Tipo de crédito</label>
                <select id="calc_tipo_credito" class="form-select form-select-sm" onchange="calcularBeneficio()">
                    <option value="">— Seleccionar —</option>
                    <option value="general">General (exc. Pequeña Empresa)</option>
                    <option value="pequena_empresa">Pequeña Empresa</option>
                    <option value="hipotecario">Hipotecario</option>
                </select>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <label class="form-label small fw-bold text-secondary">Días de atraso</label>
                <input type="number" id="calc_dias" class="form-control form-control-sm" placeholder="Ej: 45" min="0" oninput="calcularBeneficio()">
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-bold text-secondary">N° cuotas a pagar</label>
                <input type="number" id="calc_cuotas" class="form-control form-control-sm" placeholder="Ej: 2" min="1" oninput="calcularBeneficio()">
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-bold text-secondary">Condición de pago</label>
                <select id="calc_condicion" class="form-select form-select-sm" onchange="calcularBeneficio()">
                    <option value="">— Seleccionar —</option>
                    <option value="cuotas">Pago de cuotas</option>
                    <option value="cancelacion">Cancelación total</option>
                </select>
            </div>
        </div>

        <div id="calc_resultado" style="display:none;" class="card bg-light border rounded-3 p-3">
            <h6 class="text-dark text-uppercase fw-bold small mb-2">Resultado del cálculo</h6>
            <div class="row g-3 small">
                <div class="col-md-4">
                    <span class="text-muted d-block">Intereses a exonerar:</span>
                    <strong class="fs-6 text-dark" id="res_intereses">—</strong>
                </div>
                <div class="col-md-4">
                    <span class="text-muted d-block">Saldo capital a exonerar:</span>
                    <strong class="fs-6 text-dark" id="res_capital">—</strong>
                </div>
                <div class="col-md-4">
                    <span class="text-muted d-block">Interés moratorio:</span>
                    <strong class="fs-6 text-dark">100%</strong>
                </div>
            </div>
            <hr class="my-2 opacity-25">
            <div class="small text-secondary">
                <strong>Autoriza:</strong> <span id="res_funcionario">—</span>
            </div>
        </div>

        <div id="calc_vacio" class="bg-light border rounded-3 p-3 text-center text-muted small">
            Ingrese los parámetros para visualizar el beneficio máximo correspondiente.
        </div>
    </div>


    {{-- ══════════════════════════════════════════════════════
         BLOQUE 4 — NIVELES DE AUTONOMÍA
    ════════════════════════════════════════════════════════ --}}
    <div id="sec-autonomia" class="card border shadow-sm rounded-3 mb-4 p-4 bg-white">
        <h4 class="h6 fw-bold text-dark text-uppercase pb-2 mb-3 border-bottom">3. Niveles de Autonomía y Autorizaciones</h4>
        <div class="table-responsive border rounded-2">
            <table class="table table-hover align-middle mb-0 small">
                <thead class="table-light text-uppercase">
                    <tr>
                        <th class="py-2">Caso / Operación</th>
                        <th class="py-2">Condición / Rango</th>
                        <th class="py-2">Funcionario Autorizador</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Pago de cuotas — Tramo temprano</td>
                        <td><span class="text-muted">Cualquier calificación</span></td>
                        <td><span class="badge bg-light text-dark border">Coordinador Zonal de Recuperaciones</span></td>
                    </tr>
                    <tr>
                        <td>Cancelación total · CPP</td>
                        <td><span class="text-muted">Todos los tipos</span></td>
                        <td><span class="badge bg-light text-dark border">Jefe Zonal de Tiendas</span></td>
                    </tr>
                    <tr>
                        <td>Cancelación total · Deficiente / Dudoso / Pérdida</td>
                        <td>Saldo Capital &lt; 3 UIT</td>
                        <td><span class="badge bg-light text-dark border">Coordinador Zonal de Recuperaciones</span></td>
                    </tr>
                    <tr>
                        <td>Cancelación total · Deficiente / Dudoso / Pérdida</td>
                        <td>Saldo Capital &gt; 3 UIT</td>
                        <td><span class="badge bg-secondary text-white">Jefe de Recuperaciones</span></td>
                    </tr>
                    <tr>
                        <td>Cartera Judicial / Extrajudicial</td>
                        <td>Saldo Capital &gt; 3 UIT</td>
                        <td><span class="badge bg-secondary text-white">Jefe de Recuperaciones</span></td>
                    </tr>
                    <tr>
                        <td>Cartera Castigada / FOCMAC</td>
                        <td>Cancelación total</td>
                        <td><span class="badge bg-light text-dark border">Coordinador de Normalización de Cartera</span></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>


    {{-- ══════════════════════════════════════════════════════
         BLOQUE 5 — DOCUMENTOS REQUERIDOS
    ════════════════════════════════════════════════════════ --}}
    <div id="sec-documentos" class="card border shadow-sm rounded-3 mb-4 p-4 bg-white">
        <h4 class="h6 fw-bold text-dark text-uppercase pb-2 mb-3 border-bottom">4. Documentos Requeridos por Negociación</h4>
        <div class="row g-3">
            @php
            $docs_info = [
                ['titulo' => 'Pago de Cuotas', 'req' => ['Acuerdo de Pago', 'Nota de Abono', 'FUD (Formato Exoneración)'], 'no' => ['Informe-Propuesta']],
                ['titulo' => 'Cancelación Total (SK ≤ 3 UIT)', 'req' => ['Acuerdo de Pago', 'Nota de Abono', 'FUD'], 'no' => ['Informe-Propuesta']],
                ['titulo' => 'Cancelación Total (SK > 3 UIT)', 'req' => ['Acuerdo de Pago', 'Nota de Abono', 'FUD', 'Informe-Propuesta', 'Búsquedas SUNARP (< 6 meses)'], 'no' => []],
                ['titulo' => 'Cartera Castigada / FOCMAC', 'req' => ['Nota de Abono', 'FUD'], 'no' => ['Acuerdo de Pago']],
            ];
            @endphp
            @foreach($docs_info as $di)
            <div class="col-md-6">
                <div class="p-3 bg-light rounded-2 border h-100">
                    <h6 class="fw-bold text-dark small mb-2">{{ $di['titulo'] }}</h6>
                    <ul class="list-unstyled small text-secondary mb-0">
                        @foreach($di['req'] as $r)
                            <li class="text-dark fw-semibold">✓ <span class="text-secondary">{{ $r }}</span></li>
                        @endforeach
                        @foreach($di['no'] as $n)
                            <li class="text-muted opacity-75">✕ <s>{{ $n }}</s></li>
                        @endforeach
                    </ul>
                </div>
            </div>
            @endforeach
        </div>
    </div>


    {{-- ══════════════════════════════════════════════════════
         BLOQUE 6 — EXCLUSIONES
    ════════════════════════════════════════════════════════ --}}
    <div id="sec-exclusiones" class="card border shadow-sm rounded-3 mb-4 p-4 bg-white">
        <h4 class="h6 fw-bold text-dark text-uppercase pb-2 mb-3 border-bottom">5. Créditos Excluidos de la Campaña</h4>
        <p class="text-secondary small mb-3">Las siguientes operaciones no participan bajo ninguna modalidad de descuento o exoneración:</p>
        
        <div class="row g-2 small">
            @php
            $excluidos = [
                'Créditos pignoraticios',
                'Operaciones de Leasing',
                'Créditos con garantía de depósito en efectivo',
                'Cartera Multigas',
                'Créditos administrativos (ex trabajadores requieren revisión)',
                'Carteras adquiridas de terceros',
                'Créditos registrados por fraude (excepto rechazados por seguro)',
                'Créditos en proceso masivo de venta',
                'Créditos con respaldo de programas de Gobierno vigentes'
            ];
            @endphp
            @foreach($excluidos as $exc)
            <div class="col-md-6">
                <div class="p-2 bg-light border rounded-2 text-secondary d-flex align-items-center gap-2">
                    <span class="badge bg-secondary text-white">✕</span> {{ $exc }}
                </div>
            </div>
            @endforeach
        </div>
    </div>


    {{-- ══════════════════════════════════════════════════════
         BLOQUE 7 — REGLAS CRÍTICAS
    ════════════════════════════════════════════════════════ --}}
    <div id="sec-reglas" class="card border shadow-sm rounded-3 mb-4 p-4 bg-white">
        <h4 class="h6 fw-bold text-dark text-uppercase pb-2 mb-3 border-bottom">6. Reglas Críticas Operativas</h4>
        
        <div class="row g-3 small">
            @php
            $reglas = [
                ['t' => 'Base de cálculo', 'd' => 'La conciliación se calcula estrictamente sobre Interés corriente + compensatorio. El interés de gracia no participa.'],
                ['t' => 'No exoneración de seguros', 'd' => 'Bajo ninguna modalidad se exoneran primas de seguros (desgravamen, desempleo, multirriesgo).'],
                ['t' => 'Cruce de créditos', 'd' => 'Verificar que el cliente no mantenga mora contable en otra operación institucional al cierre del mes.'],
                ['t' => 'Búsquedas SUNARP', 'd' => 'Obligatorio para cancelaciones con Saldo Capital > 3 UIT con vigencia menor a 6 meses.'],
                ['t' => 'Proporcionalidad', 'd' => 'Todo beneficio debe ser proporcional a la situación económica demostrable del deudor y garante.'],
                ['t' => 'Plazo de fraccionamiento', 'd' => 'El fraccionamiento autorizado tiene un límite improrrogable de 90 días desde la primera amortización.']
            ];
            @endphp
            @foreach($reglas as $reg)
            <div class="col-md-6">
                <div class="p-3 bg-light border rounded-2 h-100">
                    <strong class="text-dark d-block mb-1">▪ {{ $reg['t'] }}</strong>
                    <span class="text-secondary">{!! $reg['d'] !!}</span>
                </div>
            </div>
            @endforeach
        </div>
    </div>


    {{-- ══════════════════════════════════════════════════════
     BLOQUE 8 — FLUJO COMPLETO DE LA OPERACIÓN (LÍNEA DE TIEMPO)
════════════════════════════════════════════════════════ --}}
<div id="sec-flujo" class="card border shadow-sm rounded-3 mb-4 p-4 bg-white">
    <h4 class="h6 fw-bold text-dark text-uppercase pb-2 mb-3 border-bottom">7. Flujo Completo de la Operación</h4>
    <p class="text-secondary small mb-4">Secuencia operativa estricta y validaciones requeridas para procesar exitosamente la campaña de recuperación:</p>
    
    @php
    $flujos = [
        ['paso' => '01', 'fase' => 'Identificación y Contacto', 'resp' => 'Asesor / Gestor de Cobranza', 'desc' => 'Identificación del cliente en la base mensual y primer contacto domiciliario o telefónico para evaluar la voluntad de pago.'],
        ['paso' => '02', 'fase' => 'Evaluación de Elegibilidad', 'resp' => 'Administrador de Tienda / Gestor', 'desc' => 'Validación de calificaciones RCC permitidas, límites de uso de campaña (máximo 4 veces al año) y revisión de múltiples créditos en mora.'],
        ['paso' => '03', 'fase' => 'Cálculo de Beneficio y Simulación', 'resp' => 'Asesor / Sistema', 'desc' => 'Uso de la calculadora de beneficio máximo para determinar el porcentaje de exoneración de intereses corrientes, compensatorios, moratorios y saldo capital.'],
        ['paso' => '04', 'fase' => 'Solicitud de Autorización (FUD)', 'resp' => 'Asesor / Administrador', 'desc' => 'Llenado del Formato Único de Descuento (FUD), adjuntando sustentos, acuerdo de pago y reporte de búsquedas SUNARP si el saldo capital supera las 3 UIT.'],
        ['paso' => '05', 'fase' => 'Aprobación por Nivel de Autonomía', 'resp' => 'CZR / Jefe de Recuperaciones / Comité', 'desc' => 'Revisión y aprobación formal según la matriz de autonomías de la campaña (Jefe Zonal, CZR o Jefatura de Recuperaciones).'],
        ['paso' => '06', 'fase' => 'Ejecución y Pago en Ventanilla', 'resp' => 'Gestor de Servicios / Operaciones', 'desc' => 'Aplicación del descuento autorizado en el sistema, recepción del pago del cliente, emisión de comprobantes y nota de abono correspondiente.'],
        ['paso' => '07', 'fase' => 'Cierre, Reporte y Control', 'resp' => 'Administrador / Analista de Recuperaciones', 'desc' => 'Consolidación diaria de expedientes de la campaña y envío del reporte mensual consolidado dentro de los 2 primeros días hábiles del mes siguiente.']
    ];
    @endphp

    <div class="position-relative ps-3 ms-2" style="border-left: 2px solid #dee2e6;">
        @foreach($flujos as $f)
        <div class="mb-4 position-relative">
            {{-- Indicador de punto en la línea --}}
            <span class="position-absolute bg-dark text-white rounded-circle d-flex align-items-center justify-content-center fw-bold shadow-sm" 
                  style="width: 28px; height: 28px; left: -31px; top: 2px; font-size: 0.7rem;">
                {{ $f['paso'] }}
            </span>

            {{-- Contenido de la tarjeta de fase --}}
            <div class="card border bg-light rounded-3 p-3">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-1">
                    <h6 class="fw-bold text-dark mb-0" style="font-size: 0.95rem;">{{ $f['fase'] }}</h6>
                    <span class="badge bg-white text-secondary border px-2 py-1" style="font-size: 0.72rem;">
                        <strong>Responsable:</strong> {{ $f['resp'] }}
                    </span>
                </div>
                <p class="text-secondary small mb-0 mt-1" style="font-size: 0.83rem; line-height: 1.4;">{{ $f['desc'] }}</p>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endsection

@push('scripts')
<script>
function calcularBeneficio() {
    const cartera       = document.getElementById('calc_cartera').value;
    const calificacion  = document.getElementById('calc_calificacion').value;
    const tipoCredito   = document.getElementById('calc_tipo_credito').value;
    const dias          = parseInt(document.getElementById('calc_dias').value) || 0;
    const cuotas        = parseInt(document.getElementById('calc_cuotas').value) || 0;
    const condicion     = document.getElementById('calc_condicion').value;

    if (!cartera || !calificacion || !condicion) {
        document.getElementById('calc_resultado').style.display = 'none';
        document.getElementById('calc_vacio').style.display = 'block';
        return;
    }

    let intereses   = '—';
    let capital     = 'No aplica';
    let funcionario = '—';

    if (cartera === 'focmac') {
        intereses   = '100%';
        capital     = 'Hasta 90%';
        funcionario = 'Coordinador de Normalización de Cartera';
    } else if (cartera === 'castigada') {
        intereses   = '100%';
        capital     = dias <= 1095 ? 'Hasta 70%' : 'Hasta 80%';
        funcionario = 'Coordinador de Normalización de Cartera';
    } else if (cartera === 'judicial') {
        intereses   = '100%';
        capital     = 'Hasta 70%';
        funcionario = 'SK < 3 UIT: CZR · SK > 3 UIT: Jefe de Recuperaciones';
    } else if (condicion === 'cancelacion') {
        if (calificacion === 'cpp') {
            intereses   = 'Hasta 50% intereses + 100% moratorio';
            funcionario = 'Jefe Zonal de Tiendas';
        } else {
            intereses   = 'Hasta 100% intereses + 100% moratorio';
            capital     = dias >= 61 ? 'Hasta 50% SK' : 'No aplica';
            funcionario = 'CZR / Jefe de Recuperaciones';
        }
    } else {
        intereses   = 'Hasta 40% - 80% intereses + 100% moratorio';
        funcionario = 'Coordinador Zonal de Recuperaciones';
    }

    document.getElementById('res_intereses').textContent  = intereses;
    document.getElementById('res_capital').textContent    = capital;
    document.getElementById('res_funcionario').textContent = funcionario;
    document.getElementById('calc_vacio').style.display    = 'none';
    document.getElementById('calc_resultado').style.display = 'block';
}
</script>
@endpush