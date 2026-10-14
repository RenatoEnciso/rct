@extends('layouts.app')
@section('title', 'Carga de sustentos')

@section('content')
<div class="page-head">
  <div>
    <h2>Carga de sustentos</h2>
    <p>Operaciones aprobadas que requieren documentación de sustento o corrección.</p>
  </div>
</div>

<div class="notice">
  <i class="bi bi-info-circle"></i>
  <span>Documentos obligatorios por operación: <strong>Acuerdo de Pago</strong> (no aplica a cartera castigada),
  <strong>Nota de Abono</strong>, <strong>Comprobante de Pago</strong> y <strong>Hoja de Convenio</strong> (no aplica a la transacción 300/140).
  En cancelaciones con saldo capital mayor a 3 UIT se adjunta además el <strong>Informe-Propuesta</strong>.
  Cada documento se valida automáticamente al cargarlo.</span>
</div>

{{-- Panel de carga --}}
<div class="card" id="panel-carga" hidden>
  <div class="card-head">
    <div>
      <div class="card-title" id="carga-titulo"><i class="bi bi-upload"></i> Carga de documentos</div>
      <div class="cell-sub" id="carga-sub"></div>
    </div>
    <button type="button" class="btn btn-ghost btn-sm" onclick="cerrarCarga()"><i class="bi bi-x-lg"></i> Cerrar</button>
  </div>

  <div id="carga-observacion" class="notice-warning" hidden><i class="bi bi-chat-left-text"></i><span></span></div>

  <form id="form-sustentos" method="POST" enctype="multipart/form-data"
        data-validar-url="{{ route('validacion.documento') }}" data-contexto="sustento">
    @csrf
    <div class="doc-grid" id="carga-docs"></div>

    <div class="form-section">
      <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
        <strong class="fs-13" id="carga-resumen">0 de 0 documentos cargados</strong>
        <span class="cell-sub" id="carga-detalle"></span>
      </div>
      <div class="progress-bar"><div class="progress-fill" id="carga-progreso" style="width:0%"></div></div>
    </div>

    <div class="actions">
      <button type="button" class="btn btn-secondary" onclick="cerrarCarga()">Cancelar</button>
      <button type="submit" class="btn btn-primary" id="btn-registrar" disabled>
        <i class="bi bi-send"></i> Registrar sustentos
      </button>
    </div>
  </form>
</div>

<div class="card card-flush tabla-patio">
  <div class="card-head">
    <div class="card-title"><i class="bi bi-list-check"></i> Operaciones por documentar</div>
  </div>
  <div class="table-wrap">
    <table data-rct-table data-search-placeholder="Buscar cliente, cuenta u operación">
      <thead>
        <tr><th>Cliente</th><th>Cuenta</th><th>Operación</th><th>Aprobado por</th><th>Estado</th><th class="num">Acción</th></tr>
      </thead>
      <tbody>
        @forelse($sustentos as $s)
          @php
            $cli = $s->propuesta->cliente;
            $requeridos = [];
            if ($cli->estado_cartera !== 'CASTIGADO') $requeridos[] = 'Acuerdo de Pago';
            $requeridos[] = 'Nota de Abono';
            $requeridos[] = 'Comprobante de Pago';
            if ($cli->transaccion !== '300/140') $requeridos[] = 'Hoja de Convenio';
            if ($cli->esMayor3UIT() && $s->propuesta->tipo_negociacion === 'cancelacion') $requeridos[] = 'Informe-Propuesta SK>3UIT';
            $observados = $s->documentos->where('estado_revision', 'observado');
            $config = [
              'id'        => $s->id,
              'cliente'   => $cli->nombre_cliente,
              'operacion' => (string) $cli->operacion,
              'accion'    => $s->estaObservado() ? route('sustentos.recargar', $s) : route('sustentos.cargar', $s),
              'correccion'=> $s->estaObservado(),
              'tipos'     => $s->estaObservado() && $observados->count() ? $observados->pluck('tipo_doc')->values() : $requeridos,
              'comentarios' => $observados->mapWithKeys(fn($d) => [$d->tipo_doc => $d->comentario_revisor])->filter(),
              'revisor'   => $s->comentario_revision,
            ];
          @endphp
          <tr>
            <td><div class="cell-main">{{ $cli->nombre_cliente }}</div><div class="cell-sub">{{ $s->propuesta->tipo_negociacion_desc }}</div></td>
            <td class="nowrap">{{ $cli->cuenta }}</td>
            <td class="nowrap">{{ $cli->operacion }}</td>
            <td>{{ $s->propuesta->aprobadoPor->name ?? '—' }}</td>
            <td>
              @if($s->estaObservado())
                <span class="badge badge-pending">Observado</span>
              @elseif($s->estado === 'En revisión')
                <span class="badge badge-info">En revisión</span>
              @else
                <span class="badge badge-gray">Pendiente de carga</span>
              @endif
            </td>
            <td class="num">
              @if($s->estado === 'En revisión')
                <span class="cell-sub">Enviado el {{ $s->fecha_carga?->format('d/m/Y') }}</span>
              @else
                <button type="button" class="btn {{ $s->estaObservado() ? 'btn-warning' : 'btn-primary' }} btn-sm"
                        onclick='abrirCarga(@json($config))'>
                  <i class="bi {{ $s->estaObservado() ? 'bi-arrow-repeat' : 'bi-upload' }}"></i>
                  {{ $s->estaObservado() ? 'Corregir documentos' : 'Cargar sustentos' }}
                </button>
              @endif
            </td>
          </tr>
        @empty
          <tr data-empty><td colspan="6" class="empty-state">
            <i class="bi bi-check2-all"></i><strong>Sin pendientes</strong>
            <span>No tiene operaciones aprobadas pendientes de sustento.</span>
          </td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

@endsection

@push('scripts')
<script>
(function () {
  var form  = document.getElementById('form-sustentos');
  var panel = document.getElementById('panel-carga');
  var docs  = document.getElementById('carga-docs');

  window.abrirCarga = function (c) {
    form.action = c.accion;
    form.setAttribute('data-sustento-id', c.id);
    document.getElementById('carga-titulo').innerHTML = '<i class="bi ' + (c.correccion ? 'bi-arrow-repeat' : 'bi-upload') + '"></i> ' +
      (c.correccion ? 'Corrección de documentos' : 'Carga de documentos');
    document.getElementById('carga-sub').textContent = c.cliente + ' · Operación ' + c.operacion;

    var obs = document.getElementById('carga-observacion');
    obs.hidden = !(c.correccion && c.revisor);
    obs.querySelector('span').innerHTML = c.revisor ? '<strong>Observación del revisor:</strong> ' + rctEsc(c.revisor) : '';

    docs.innerHTML = c.tipos.map(function (tipo, i) {
      var nota = c.comentarios && c.comentarios[tipo]
        ? '<div class="field-hint" style="margin:-4px 0 8px;color:var(--ambar)"><i class="bi bi-chat-left-text"></i> ' + rctEsc(c.comentarios[tipo]) + '</div>' : '';
      return '<div class="doc-slot" data-tipo="' + rctEsc(tipo) + '">' +
        '<div class="doc-slot-head"><span class="doc-slot-title"><i class="bi bi-file-earmark-text"></i>' + rctEsc(tipo === 'Informe-Propuesta SK>3UIT' ? 'Informe-Propuesta (SK > 3 UIT)' : tipo) + '</span>' +
        '<span class="badge doc-badge badge-gray">Pendiente</span></div>' + nota +
        '<input type="hidden" name="documentos[' + i + '][tipo]" value="' + rctEsc(tipo) + '">' +
        '<label class="doc-drop"><i class="bi bi-cloud-arrow-up"></i>' +
        '<span class="doc-file-name">Seleccionar archivo</span>' +
        '<input type="file" name="documentos[' + i + '][archivo]" accept=".pdf,.jpg,.jpeg,.png"></label>' +
        '<div class="field-hint mt-2">PDF, JPG o PNG · máximo 10 MB</div>' +
        '<div class="val-result"></div></div>';
    }).join('');

    actualizar();
    panel.hidden = false;
    panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
  };

  window.cerrarCarga = function () { panel.hidden = true; docs.innerHTML = ''; };

  function actualizar() {
    var r = rctResumenCarga(form);
    document.getElementById('carga-resumen').textContent = r.cargados + ' de ' + r.total + ' documentos cargados';
    var partes = [];
    if (r.conformes) partes.push(r.conformes + ' conforme(s)');
    if (r.observados) partes.push(r.observados + ' observado(s)');
    if (r.rechazados) partes.push(r.rechazados + ' rechazado(s)');
    if (r.analizando) partes.push('analizando ' + r.analizando);
    document.getElementById('carga-detalle').textContent = partes.join(' · ');
    document.getElementById('carga-progreso').style.width = (r.total ? Math.round(r.cargados / r.total * 100) : 0) + '%';
    document.getElementById('btn-registrar').disabled = r.total === 0 || r.cargados < r.total || r.analizando > 0;
  }
  form.addEventListener('rct:validando', actualizar);
  form.addEventListener('rct:validado', actualizar);

  form.addEventListener('submit', function (e) {
    var r = rctResumenCarga(form);
    if (r.observados + r.rechazados > 0 &&
        !confirm('La validación automática encontró observaciones en ' + (r.observados + r.rechazados) +
                 ' documento(s). Se registrarán con su estado para la revisión de Recuperaciones.\n\n¿Desea continuar?')) {
      e.preventDefault(); return;
    }
    var b = document.getElementById('btn-registrar');
    b.disabled = true; b.innerHTML = '<span class="spinner"></span> Registrando…';
  });
})();
</script>
@endpush
