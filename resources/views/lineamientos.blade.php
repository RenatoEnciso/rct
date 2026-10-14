@extends('layouts.app')
@section('title', 'Lineamientos de campaña')

@push('styles')
<style>
  .lin-nav { position: sticky; top: calc(var(--header-h) + 8px); z-index: 10; display: flex; gap: 4px; overflow-x: auto;
             padding: 6px; margin-bottom: 20px; background: var(--superficie); border: 1px solid var(--borde); border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); }
  .lin-nav a { padding: 6px 12px; border-radius: var(--radius); color: var(--gris); font-size: 13px; font-weight: 500; white-space: nowrap; }
  .lin-nav a:hover { background: var(--sutil); color: var(--tinta); }
  .lin-section { scroll-margin-top: calc(var(--header-h) + 70px); }
  .lin-num { display: inline-grid; place-items: center; width: 22px; height: 22px; border-radius: 50%; background: var(--rojo-soft); color: var(--rojo); font-size: 11.5px; font-weight: 700; }
  .lin-box { padding: 14px; border: 1px solid var(--borde); border-radius: var(--radius); background: var(--sutil); height: 100%; }
  .lin-box h4 { font-size: 13px; margin: 0 0 4px; }
  .lin-box p { font-size: 13px; color: var(--gris); margin: 0; }
  .check-list { list-style: none; padding: 0; margin: 0; font-size: 13px; }
  .check-list li { display: flex; gap: 8px; padding: 3px 0; }
  .check-list .bi-check2 { color: var(--verde); } .check-list .no { color: var(--gris-claro); text-decoration: line-through; }
  .calif { display: inline-block; padding: 3px 9px; border-radius: 4px; font-size: 11.5px; font-weight: 600; color: #fff; }
</style>
@endpush

@section('content')
@php
  $campana = \App\Models\Campana::activa();
  $inicio  = $campana?->fecha_inicio ?? \Carbon\Carbon::parse('2026-09-01');
  $fin     = $campana?->fecha_fin ?? \Carbon\Carbon::parse('2027-02-28');
  $dias    = (int) now()->startOfDay()->diffInDays($fin, false);
@endphp

<div class="page-head">
  <div>
    <h2>Lineamientos de campaña</h2>
    <p>«Sin atrasos, que atrasan» · Departamento de Recuperaciones</p>
  </div>
  <div class="page-head-actions">
    <span class="badge badge-gray"><i class="bi bi-calendar3"></i> {{ $inicio->format('d/m/Y') }} al {{ $fin->format('d/m/Y') }}</span>
    <span class="badge {{ $dias <= 30 ? 'badge-danger' : 'badge-ok' }} dot">{{ $dias > 0 ? $dias.' días restantes' : 'Campaña vencida' }}</span>
  </div>
</div>

<nav class="lin-nav" aria-label="Secciones">
  <a href="#sec-objetivos">Objetivos</a>
  <a href="#sec-elegibilidad">Elegibilidad</a>
  <a href="#sec-calculadora">Calculadora</a>
  <a href="#sec-autonomia">Autonomía</a>
  <a href="#sec-documentos">Documentos</a>
  <a href="#sec-exclusiones">Exclusiones</a>
  <a href="#sec-reglas">Reglas críticas</a>
  <a href="#sec-flujo">Flujo operativo</a>
</nav>

<div class="card lin-section" id="sec-objetivos">
  <div class="card-head">
    <div class="card-title"><i class="bi bi-flag"></i> Objetivos de la campaña</div>
    <span class="badge badge-gray">Norma supletoria: Manual de Gestión de Recuperaciones</span>
  </div>
  <div class="grid2">
    <div class="lin-box"><h4>Objetivo 1</h4><p>Disminuir el gasto de provisiones reduciendo la cartera en mora mediante beneficios a clientes en cartera vencida, refinanciada, extrajudicial y judicial.</p></div>
    <div class="lin-box"><h4>Objetivo 2</h4><p>Incrementar los ingresos financieros percibidos mediante la recuperación de créditos en morosidad, cartera castigada y cartera FOCMAC.</p></div>
  </div>
</div>

<div class="card lin-section" id="sec-elegibilidad">
  <div class="card-title"><span class="lin-num">1</span> Criterios de elegibilidad</div>
  <div class="grid2 mb-3">
    <div class="lin-box"><h4>Inclusión en base</h4><p>El cliente debe figurar en la base mensual remitida por Recuperaciones. Inclusiones extraordinarias requieren visto bueno de Jefatura.</p></div>
    <div class="lin-box"><h4>Límite de uso</h4><p>Máximo <strong>4 veces por cada 12 meses</strong> (incluye campañas anteriores). Aplica para calificaciones Normal, CPP, Deficiente, Dudoso y Pérdida.</p></div>
    <div class="lin-box"><h4>Calificaciones RCC permitidas</h4>
      <div class="d-flex gap-2 flex-wrap mt-2">
        <span class="calif" style="background:#3c6e47">NORMAL</span>
        <span class="calif" style="background:#1f4e8c">CPP</span>
        <span class="calif" style="background:#a15c07">DEFICIENTE</span>
        <span class="calif" style="background:#9a3412">DUDOSO</span>
        <span class="calif" style="background:#7d1221">PÉRDIDA</span>
      </div>
    </div>
    <div class="lin-box"><h4>Múltiples créditos en mora</h4><p>Si el cliente posee más de un crédito en mora, se gestionará la negociación conjunta de <strong>todos los créditos</strong> afectados.</p></div>
  </div>
  <div class="notice-warning m-0"><i class="bi bi-exclamation-triangle"></i>
    <span><strong>Excepción al límite de 4 veces:</strong> la calificación CPP/Deficiente requiere aprobación del Jefe de Recuperaciones. Dudoso/Pérdida requiere autorización del Coordinador Zonal por correo con copia a Jefatura.</span></div>
</div>

<div class="card lin-section" id="sec-calculadora">
  <div class="card-title"><span class="lin-num">2</span> Calculadora de beneficio máximo</div>
  <p class="card-sub">Seleccione los parámetros de la operación para conocer el porcentaje máximo de exoneración aplicable.</p>
  <div class="grid3">
    <div class="field"><label for="calc_cartera">Tipo de cartera</label>
      <select id="calc_cartera" onchange="calcularBeneficio()">
        <option value="">Seleccionar</option>
        <option value="vigente_vencida">Vigente / Vencida / Reprogramada</option>
        <option value="refinanciada">Refinanciada</option>
        <option value="judicial">Judicial / Extrajudicial</option>
        <option value="castigada">Castigada</option>
        <option value="focmac">FOCMAC</option>
      </select></div>
    <div class="field"><label for="calc_calificacion">Calificación RCC</label>
      <select id="calc_calificacion" onchange="calcularBeneficio()">
        <option value="">Seleccionar</option>
        <option value="normal">Normal</option><option value="cpp">CPP</option><option value="deficiente">Deficiente</option>
        <option value="dudoso">Dudoso</option><option value="perdida">Pérdida</option>
      </select></div>
    <div class="field"><label for="calc_tipo_credito">Tipo de crédito</label>
      <select id="calc_tipo_credito" onchange="calcularBeneficio()">
        <option value="">Seleccionar</option>
        <option value="general">General (excepto Pequeña Empresa)</option>
        <option value="pequena_empresa">Pequeña Empresa</option>
        <option value="hipotecario">Hipotecario</option>
      </select></div>
    <div class="field"><label for="calc_dias">Días de atraso</label>
      <input type="number" id="calc_dias" placeholder="Ej. 45" min="0" oninput="calcularBeneficio()"></div>
    <div class="field"><label for="calc_cuotas">Cuotas a pagar</label>
      <input type="number" id="calc_cuotas" placeholder="Ej. 2" min="1" oninput="calcularBeneficio()"></div>
    <div class="field"><label for="calc_condicion">Condición de pago</label>
      <select id="calc_condicion" onchange="calcularBeneficio()">
        <option value="">Seleccionar</option>
        <option value="cuotas">Pago de cuotas</option>
        <option value="cancelacion">Cancelación total</option>
      </select></div>
  </div>

  <div id="calc_resultado" class="card-plain form-section" hidden style="margin:16px 0 0">
    <div class="quote-label">Resultado</div>
    <div class="grid3">
      <div><div class="detail-label">Intereses a exonerar</div><div class="detail-value lg" id="res_intereses">—</div></div>
      <div><div class="detail-label">Saldo capital a exonerar</div><div class="detail-value lg" id="res_capital">—</div></div>
      <div><div class="detail-label">Interés moratorio</div><div class="detail-value lg">100%</div></div>
    </div>
    <div class="fs-13 mt-3"><span class="text-gris">Autoriza:</span> <strong id="res_funcionario">—</strong></div>
  </div>
  <div id="calc_vacio" class="empty-state" style="padding:20px"><i class="bi bi-calculator"></i><span>Ingrese los parámetros para ver el beneficio máximo.</span></div>
</div>

<div class="card card-flush lin-section" id="sec-autonomia">
  <div class="card-head"><div class="card-title"><span class="lin-num">3</span> Niveles de autonomía y autorizaciones</div></div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Caso u operación</th><th>Condición</th><th>Funcionario que autoriza</th></tr></thead>
      <tbody>
        <tr><td>Pago de cuotas — Tramo temprano</td><td class="text-gris">Cualquier calificación</td><td><span class="badge badge-gray">Coordinador Zonal de Recuperaciones</span></td></tr>
        <tr><td>Cancelación total · CPP</td><td class="text-gris">Todos los tipos</td><td><span class="badge badge-gray">Jefe Zonal de Tiendas</span></td></tr>
        <tr><td>Cancelación total · Deficiente / Dudoso / Pérdida</td><td>Saldo capital &lt; 3 UIT</td><td><span class="badge badge-gray">Coordinador Zonal de Recuperaciones</span></td></tr>
        <tr><td>Cancelación total · Deficiente / Dudoso / Pérdida</td><td>Saldo capital &gt; 3 UIT</td><td><span class="badge badge-rojo">Jefe de Recuperaciones</span></td></tr>
        <tr><td>Cartera judicial / extrajudicial</td><td>Saldo capital &gt; 3 UIT</td><td><span class="badge badge-rojo">Jefe de Recuperaciones</span></td></tr>
        <tr><td>Cartera castigada / FOCMAC</td><td>Cancelación total</td><td><span class="badge badge-gray">Coordinador de Normalización de Cartera</span></td></tr>
      </tbody>
    </table>
  </div>
</div>

<div class="card lin-section" id="sec-documentos">
  <div class="card-title"><span class="lin-num">4</span> Documentos requeridos por negociación</div>
  @php
    $docs_info = [
      ['titulo' => 'Pago de cuotas', 'req' => ['Acuerdo de Pago', 'Nota de Abono', 'FUD (Formato de Exoneración)', 'Comprobante de pago'], 'no' => ['Informe-Propuesta']],
      ['titulo' => 'Cancelación total (SK ≤ 3 UIT)', 'req' => ['Acuerdo de Pago', 'Nota de Abono', 'FUD', 'Comprobante de pago'], 'no' => ['Informe-Propuesta']],
      ['titulo' => 'Cancelación total (SK > 3 UIT)', 'req' => ['Acuerdo de Pago', 'Nota de Abono', 'FUD', 'Comprobante de pago', 'Informe-Propuesta', 'Búsquedas SUNARP (menos de 6 meses)'], 'no' => []],
      ['titulo' => 'Cartera castigada / FOCMAC', 'req' => ['Nota de Abono', 'FUD', 'Comprobante de pago'], 'no' => ['Acuerdo de Pago']],
    ];
  @endphp
  <div class="grid2">
    @foreach($docs_info as $di)
      <div class="lin-box">
        <h4>{{ $di['titulo'] }}</h4>
        <ul class="check-list">
          @foreach($di['req'] as $r)<li><i class="bi bi-check2"></i><span>{{ $r }}</span></li>@endforeach
          @foreach($di['no'] as $n)<li><i class="bi bi-dash text-gris"></i><span class="no">{{ $n }}</span></li>@endforeach
        </ul>
      </div>
    @endforeach
  </div>
</div>

<div class="card lin-section" id="sec-exclusiones">
  <div class="card-title"><span class="lin-num">5</span> Créditos excluidos de la campaña</div>
  <p class="card-sub">Las siguientes operaciones no participan bajo ninguna modalidad de descuento o exoneración.</p>
  <div class="grid2" style="gap:8px 16px">
    @foreach([
      'Créditos pignoraticios', 'Operaciones de leasing', 'Créditos con garantía de depósito en efectivo', 'Cartera Multigas',
      'Créditos administrativos (ex trabajadores requieren revisión)', 'Carteras adquiridas de terceros',
      'Créditos registrados por fraude (excepto rechazados por la aseguradora)', 'Créditos en proceso masivo de venta',
      'Créditos con respaldo de programas de Gobierno vigentes',
    ] as $exc)
      <div class="d-flex gap-2 fs-13"><i class="bi bi-slash-circle text-rojo mt-1"></i><span>{{ $exc }}</span></div>
    @endforeach
  </div>
</div>

<div class="card lin-section" id="sec-reglas">
  <div class="card-title"><span class="lin-num">6</span> Reglas críticas operativas</div>
  <div class="grid2">
    @foreach([
      ['t' => 'Base de cálculo', 'd' => 'La conciliación se calcula estrictamente sobre interés corriente + compensatorio. El interés de gracia no participa.'],
      ['t' => 'No exoneración de seguros', 'd' => 'Bajo ninguna modalidad se exoneran primas de seguros (desgravamen, desempleo, multirriesgo).'],
      ['t' => 'Cruce de créditos', 'd' => 'Verificar que el cliente no mantenga mora contable en otra operación institucional al cierre del mes.'],
      ['t' => 'Búsquedas SUNARP', 'd' => 'Obligatorio para cancelaciones con saldo capital mayor a 3 UIT, con vigencia menor a 6 meses.'],
      ['t' => 'Proporcionalidad', 'd' => 'Todo beneficio debe ser proporcional a la situación económica demostrable del deudor y del garante.'],
      ['t' => 'Plazo de fraccionamiento', 'd' => 'El fraccionamiento autorizado tiene un límite improrrogable de 90 días desde la primera amortización.'],
    ] as $reg)
      <div class="lin-box"><h4>{{ $reg['t'] }}</h4><p>{{ $reg['d'] }}</p></div>
    @endforeach
  </div>
</div>

<div class="card lin-section" id="sec-flujo">
  <div class="card-title"><span class="lin-num">7</span> Flujo completo de la operación</div>
  <p class="card-sub">Secuencia operativa y validaciones requeridas para procesar la campaña de recuperación.</p>
  <div class="timeline">
    @foreach([
      ['fase' => 'Identificación y contacto', 'resp' => 'Asesor / Gestor / Ejecutivo de Cobranza', 'desc' => 'Identificación del cliente en la base mensual y primer contacto domiciliario o telefónico para evaluar la voluntad de pago.'],
      ['fase' => 'Evaluación de elegibilidad', 'resp' => 'Asesor / Gestor / Ejecutivo de Cobranza / Administrador de Tienda', 'desc' => 'Validación de calificaciones RCC permitidas, límites de uso de campaña (máximo 4 veces al año) y revisión de múltiples créditos en mora.'],
      ['fase' => 'Cálculo y simulación', 'resp' => 'RCT', 'desc' => 'Uso de la calculadora de beneficio máximo para determinar el porcentaje de exoneración de intereses corrientes, compensatorios, moratorios y saldo capital.'],
      ['fase' => 'Solicitud de autorización (FUD)', 'resp' => 'Asesor / Gestor / Ejecutivo de Cobranza / Administrador de Tienda', 'desc' => 'Registro del Formato Único de Descuento (FUD), adjuntando evidencia de la negociación, acuerdo de pago y reporte de búsquedas SUNARP si el saldo capital supera las 3 UIT.'],
      ['fase' => 'Aprobación por nivel de autonomía', 'resp' => 'CZR / Jefe Zonal / Jefe de Recuperaciones / Gerencia', 'desc' => 'Revisión y aprobación formal según la matriz de autonomías de la campaña.'],
      ['fase' => 'Ejecución y pago en ventanilla', 'resp' => 'Operaciones', 'desc' => 'Aplicación del descuento autorizado en el sistema, atención al cliente en ventanilla, emisión de sustentos y suscripción de firmas.'],
      ['fase' => 'Cierre, reporte y control', 'resp' => 'Administrador / Operaciones / Recuperaciones', 'desc' => 'Carga diaria de sustentos y envío del reporte mensual consolidado dentro de los 2 primeros días hábiles del mes siguiente.'],
    ] as $i => $f)
      <div class="timeline-item">
        <div class="timeline-title">{{ $i + 1 }}. {{ $f['fase'] }}</div>
        <div class="timeline-meta">Responsable: {{ $f['resp'] }}</div>
        <div class="timeline-body text-gris">{{ $f['desc'] }}</div>
      </div>
    @endforeach
  </div>
</div>
@endsection

@push('scripts')
<script>
function calcularBeneficio() {
  const cartera      = document.getElementById('calc_cartera').value;
  const calificacion = document.getElementById('calc_calificacion').value;
  const dias         = parseInt(document.getElementById('calc_dias').value) || 0;
  const condicion    = document.getElementById('calc_condicion').value;
  const resultado    = document.getElementById('calc_resultado');
  const vacio        = document.getElementById('calc_vacio');

  if (!cartera || !calificacion || !condicion) { resultado.hidden = true; vacio.hidden = false; return; }

  let intereses = '—', capital = 'No aplica', funcionario = '—';

  if (cartera === 'focmac') {
    intereses = '100%'; capital = 'Hasta 90%'; funcionario = 'Coordinador de Normalización de Cartera';
  } else if (cartera === 'castigada') {
    intereses = '100%'; capital = dias <= 1095 ? 'Hasta 70%' : 'Hasta 80%'; funcionario = 'Coordinador de Normalización de Cartera';
  } else if (cartera === 'judicial') {
    intereses = '100%'; capital = 'Hasta 70%'; funcionario = 'SK < 3 UIT: CZR · SK > 3 UIT: Jefe de Recuperaciones';
  } else if (condicion === 'cancelacion') {
    if (calificacion === 'cpp') {
      intereses = 'Hasta 50% intereses + 100% moratorio'; funcionario = 'Jefe Zonal de Tiendas';
    } else {
      intereses = 'Hasta 100% intereses + 100% moratorio'; capital = dias >= 61 ? 'Hasta 50% SK' : 'No aplica'; funcionario = 'CZR / Jefe de Recuperaciones';
    }
  } else {
    intereses = 'Hasta 40% – 80% intereses + 100% moratorio'; funcionario = 'Coordinador Zonal de Recuperaciones';
  }

  document.getElementById('res_intereses').textContent = intereses;
  document.getElementById('res_capital').textContent = capital;
  document.getElementById('res_funcionario').textContent = funcionario;
  vacio.hidden = true; resultado.hidden = false;
}
</script>
@endpush
