@extends('layouts.app')
@section('title', 'Programas de gobierno')

@use('App\Support\Estilo')

@section('content')
<div class="page-head">
  <div>
    <h2>Programas de gobierno y exclusiones</h2>
    <p>Expedientes de créditos con garantía estatal habilitados para la campaña y cartera excluida.</p>
  </div>
  <div class="page-head-actions">
    <button type="button" class="btn btn-primary" data-modal-open="modal-registro"><i class="bi bi-plus-lg"></i> Registrar expediente</button>
  </div>
</div>

{{-- Expedientes --}}
<div class="card card-flush">
  <div class="card-head">
    <div class="card-title"><i class="bi bi-bank"></i> Expedientes de programas de gobierno</div>
  </div>
  <form method="GET" class="grid4" style="padding:0 20px 16px;align-items:end">
    <div class="field span-2">
      <label for="g-q">Cliente, cuenta u operación</label>
      <input id="g-q" type="search" name="q" value="{{ request('q') }}" placeholder="Buscar">
    </div>
    <div class="field">
      <label for="g-estado">Estado</label>
      <select id="g-estado" name="estado">
        <option value="">Todos</option>
        <option value="Completo" @selected(request('estado') === 'Completo')>Completo</option>
        <option value="Incompleto" @selected(request('estado') === 'Incompleto')>Incompleto</option>
      </select>
    </div>
    <div class="d-flex gap-2">
      <button class="btn btn-primary"><i class="bi bi-funnel"></i> Filtrar</button>
      <a href="{{ route('cartera.exclusiones') }}" class="btn btn-ghost">Limpiar</a>
    </div>
  </form>
  <div class="table-wrap">
    <table>
      <thead>
        <tr><th>Registro</th><th>Cliente</th><th>Tienda</th><th>Programa</th><th style="min-width:170px">Avance documentario</th><th>Estado</th><th></th></tr>
      </thead>
      <tbody>
        @forelse($expedientes as $e)
          @php $n = min($e->documentos_count, 10); @endphp
          <tr>
            <td class="nowrap">{{ $e->fecha_registro?->format('d/m/Y') }}</td>
            <td><div class="cell-main">{{ $e->cliente->nombre_cliente ?? '—' }}</div><div class="cell-sub">Op. {{ $e->cliente->operacion ?? '—' }}</div></td>
            <td>{{ $e->cliente->tienda->nombre ?? '—' }}</td>
            <td><div>{{ $e->programa }}</div><div class="cell-sub">{{ $e->motivo_habilitacion }}</div></td>
            <td>
              <div class="bar-row">
                <div class="bar-track"><div class="bar-fill {{ $n === 10 ? 'ok' : ($n >= 6 ? 'warn' : 'danger') }}" style="width:{{ $n * 10 }}%"></div></div>
                <span class="fs-12 fw-600 num">{{ $n }}/10</span>
              </div>
              @if($e->observados_count)
                <div class="cell-sub text-mostaza" style="margin-top:3px"><i class="bi bi-exclamation-circle"></i> {{ $e->observados_count }} con observaciones</div>
              @endif
            </td>
            <td><span class="badge {{ Estilo::badge($e->estado) }}">{{ $e->estado }}</span></td>
            <td class="num"><a href="{{ route('cartera.gobierno.show', $e) }}" class="btn btn-secondary btn-sm">Ver expediente</a></td>
          </tr>
        @empty
          <tr><td colspan="7" class="empty-state">
            <i class="bi bi-folder2-open"></i><strong>Sin expedientes</strong><span>No hay expedientes con los filtros aplicados.</span>
          </td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
  {{ $expedientes->links() }}
</div>

{{-- Exclusiones --}}
<div class="card">
  <div class="card-title"><i class="bi bi-slash-circle"></i> Créditos excluidos de la campaña</div>
  <div class="grid3">
    @foreach([
      'Pignoraticios'            => 'Créditos con garantía prendaria.',
      'Leasing'                  => 'Contratos de arrendamiento financiero.',
      'Garantía de depósito'     => 'Créditos respaldados con depósitos en efectivo.',
      'Cartera Multigas'         => 'Cartera adquirida de Multigas.',
      'Créditos administrativos' => 'Ex trabajadores de la Caja; requieren revisión previa de la Jefatura.',
      'Carteras adquiridas'      => 'Cartera comprada a terceros.',
      'Fraude'                   => 'Salvo los no reportados o rechazados por la aseguradora.',
      'En proceso de venta'      => 'Créditos en transferencia de cartera.',
      'Programas de gobierno'    => 'Salvo garantía rechazada o con pérdida de eficacia (expedientes de esta sección).',
    ] as $titulo => $desc)
      <div class="d-flex gap-2 fs-13">
        <i class="bi bi-dash-circle text-rojo" style="margin-top:2px"></i>
        <div><div class="fw-600">{{ $titulo }}</div><div class="cell-sub">{{ $desc }}</div></div>
      </div>
    @endforeach
  </div>
</div>

{{-- Registro de expediente --}}
<div class="rct-modal" id="modal-registro" aria-labelledby="titulo-registro" role="dialog">
  <div class="rct-modal-box" style="width:min(980px,100%)">
    <div class="rct-modal-head">
      <h3 class="rct-modal-title" id="titulo-registro">Registrar expediente de programa de gobierno</h3>
      <button type="button" class="rct-modal-close" data-modal-close aria-label="Cerrar"><i class="bi bi-x-lg"></i></button>
    </div>
    <form method="POST" action="{{ route('cartera.gobierno') }}" enctype="multipart/form-data" id="form-gobierno"
          data-validar-url="{{ route('validacion.documento') }}" data-contexto="gobierno">
      @csrf
      <div class="rct-modal-body">
        <div class="notice"><i class="bi bi-info-circle"></i>
          <span>Aplica solo a créditos con <strong>garantía rechazada</strong> o <strong>pérdida de eficacia</strong>, según el listado del Departamento de Recuperaciones.
          Puede registrar el expediente con los documentos disponibles y completarlo después.</span></div>

        <div class="grid3">
          <div class="field">
            <label for="cliente_ref">Cuenta u operación del crédito <span class="req">*</span></label>
            <input id="cliente_ref" name="cliente_ref" value="{{ old('cliente_ref') }}" placeholder="Ej. 300006213" required>
          </div>
          <div class="field">
            <label for="programa">Programa <span class="req">*</span></label>
            <select id="programa" name="programa" required>
              @foreach(['Reactiva Perú','FAE-MYPE','IMPULSA PERÚ','Otro'] as $p)
                <option @selected(old('programa') === $p)>{{ $p }}</option>
              @endforeach
            </select>
          </div>
          <div class="field">
            <label for="motivo_habilitacion">Motivo de habilitación <span class="req">*</span></label>
            <select id="motivo_habilitacion" name="motivo_habilitacion" required>
              <option>Garantía rechazada por Cía. Seguros</option>
              <option>Pérdida de eficacia de la garantía</option>
            </select>
          </div>
          <div class="field">
            <label for="responsable">Responsable <span class="req">*</span></label>
            <input id="responsable" name="responsable" value="{{ old('responsable', auth()->user()->name) }}" required>
          </div>
          <div class="field">
            <label for="confirmacion_dpto">Confirmación del Dpto. de Recuperaciones</label>
            <select id="confirmacion_dpto" name="confirmacion_dpto">
              <option value="1">Listado recibido</option>
              <option value="0">Pendiente de listado</option>
            </select>
          </div>
        </div>

        <div class="form-section">
          <div class="d-flex justify-content-between align-items-center flex-wrap gap-2" style="margin-bottom:12px">
            <div class="form-section-title" style="margin:0">Checklist documentario</div>
            <span class="cell-sub" id="gob-resumen">0 de {{ count($checklist) }} documentos cargados</span>
          </div>
          <div class="doc-grid">
            @foreach($checklist as $i => $tipo)
              <div class="doc-slot" data-tipo="{{ $tipo }}">
                <div class="doc-slot-head">
                  <span class="doc-slot-title"><i class="bi bi-file-earmark-text"></i>{{ $tipo }}</span>
                  <span class="badge doc-badge badge-gray">Pendiente</span>
                </div>
                <label class="doc-drop"><i class="bi bi-cloud-arrow-up"></i>
                  <span class="doc-file-name">Seleccionar archivo</span>
                  <input type="file" name="documentos[{{ $i }}]" accept=".pdf,.jpg,.jpeg,.png">
                </label>
                <div class="val-result"></div>
              </div>
            @endforeach
          </div>
        </div>
      </div>
      <div class="rct-modal-foot actions" style="margin-top:0">
        <button type="button" class="btn btn-secondary" data-modal-close>Cancelar</button>
        <button type="submit" class="btn btn-primary" id="btn-gobierno" disabled><i class="bi bi-save"></i> Registrar expediente</button>
      </div>
    </form>
  </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
  var form = document.getElementById('form-gobierno');
  function actualizar() {
    var r = rctResumenCarga(form);
    var txt = r.cargados + ' de ' + r.total + ' documentos cargados';
    if (r.observados + r.rechazados) txt += ' · ' + (r.observados + r.rechazados) + ' con observaciones';
    document.getElementById('gob-resumen').textContent = txt;
    document.getElementById('btn-gobierno').disabled = r.cargados === 0 || r.analizando > 0;
  }
  form.addEventListener('rct:validando', actualizar);
  form.addEventListener('rct:validado', actualizar);
  form.addEventListener('submit', function (e) {
    var r = rctResumenCarga(form);
    if (r.observados + r.rechazados && !confirm('Hay documentos con observaciones de la validación automática. Se registrarán con su estado. ¿Continuar?')) {
      e.preventDefault(); return;
    }
    var b = document.getElementById('btn-gobierno');
    b.disabled = true; b.innerHTML = '<span class="spinner"></span> Registrando…';
  });
  @if($errors->any() && old('cliente_ref') !== null)
    rctModal.abrir('modal-registro');
  @endif
})();
</script>
@endpush
