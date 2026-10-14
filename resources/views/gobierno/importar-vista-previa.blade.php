@extends('layouts.app')
@section('title', 'Vista previa de la importación')

@section('content')
@php
  $icono = fn($v) => $v === 1 ? '<i class="bi bi-check-lg text-verde"></i>' : ($v === 0 ? '<i class="bi bi-x-lg text-rojo"></i>' : '<i class="bi bi-question-lg text-mostaza"></i>');
  $badgeAccion = ['crear' => ['badge-info', 'Nuevo'], 'actualizar' => ['badge-gray', 'Actualiza'], 'error' => ['badge-danger', 'Error']];
@endphp

<div class="page-head">
  <div>
    <div class="page-eyebrow"><a href="{{ route('gobierno.importar') }}">Importar</a> / Vista previa</div>
    <h2>Vista previa · {{ $archivo }}</h2>
    <p>Revise el resultado antes de guardar. Las filas con error no se importan.</p>
  </div>
  <div class="page-head-actions">
    <a href="{{ route('gobierno.importar') }}" class="btn btn-secondary">Cancelar</a>
    <form method="POST" action="{{ route('gobierno.importar.confirmar') }}"
          data-confirm="Se importarán {{ $resumen['crear'] + $resumen['actualizar'] }} expediente(s). ¿Continuar?">
      @csrf
      <input type="hidden" name="token" value="{{ $token }}">
      <button class="btn btn-primary" @disabled($resumen['crear'] + $resumen['actualizar'] === 0)>
        <i class="bi bi-check2-circle"></i> Importar {{ $resumen['crear'] + $resumen['actualizar'] }} expediente(s)
      </button>
    </form>
  </div>
</div>

<div class="kpi-grid">
  <div class="kpi-card"><div class="kpi-label"><i class="bi bi-list-ol"></i> Filas leídas</div><div class="kpi-value">{{ $resumen['total'] }}</div><div class="kpi-sub">Con datos</div></div>
  <div class="kpi-card info"><div class="kpi-label"><i class="bi bi-plus-circle"></i> Nuevos</div><div class="kpi-value">{{ $resumen['crear'] }}</div><div class="kpi-sub">Se crearán</div></div>
  <div class="kpi-card"><div class="kpi-label"><i class="bi bi-arrow-repeat"></i> Actualizaciones</div><div class="kpi-value">{{ $resumen['actualizar'] }}</div><div class="kpi-sub">ID ya registrado</div></div>
  <div class="kpi-card mostaza"><div class="kpi-label"><i class="bi bi-exclamation-triangle"></i> Con diferencias</div><div class="kpi-value">{{ $resumen['diferencias'] }}</div><div class="kpi-sub">Se importan con el valor calculado</div></div>
  <div class="kpi-card rojo"><div class="kpi-label"><i class="bi bi-x-octagon"></i> Con error</div><div class="kpi-value">{{ $resumen['error'] }}</div><div class="kpi-sub">No se importarán</div></div>
</div>

<div class="card card-flush">
  <div class="card-head">
    <div class="card-title"><i class="bi bi-table"></i> Detalle por fila</div>
    <div class="d-flex gap-2 flex-wrap" id="filtro-accion">
      @foreach(['' => 'Todas', 'error' => 'Con error', 'avisos' => 'Con diferencias', 'crear' => 'Nuevos', 'actualizar' => 'Actualizaciones'] as $k => $l)
        <button type="button" class="btn btn-sm {{ $k === '' ? 'btn-primary' : 'btn-secondary' }}" data-f="{{ $k }}">{{ $l }}</button>
      @endforeach
    </div>
  </div>
  <div class="tabla-patio">
    <div class="table-wrap">
      <table data-rct-table data-page-size="15" data-search-placeholder="Buscar ID, DNI o cliente" id="tabla-previa">
        <thead>
          <tr>
            <th>Fila</th><th>Resultado</th><th>ID</th><th>Cliente</th><th>Zona / tienda</th><th class="num">Monto honrado</th><th>Lote</th>
            @for($c = 1; $c <= 10; $c++)<th class="center" title="{{ \App\Services\Honramiento\FormatoExcel::DOCUMENTOS[$c] }}">D{{ $c }}</th>@endfor
            <th>Estado Excel</th><th>Estado RCT</th><th class="num">Faltantes Excel / RCT</th><th style="min-width:280px">Mensajes</th>
          </tr>
        </thead>
        <tbody>
          @foreach($filas as $f)
            <tr data-accion="{{ $f['accion'] }}" data-avisos="{{ $f['diferencias'] && $f['accion'] !== 'error' ? 1 : 0 }}">
              <td class="num">{{ $f['fila'] }}</td>
              <td><span class="badge {{ $badgeAccion[$f['accion']][0] }}">{{ $badgeAccion[$f['accion']][1] }}</span></td>
              <td class="nowrap">{{ $f['id_externo'] ?: '—' }}</td>
              <td><div class="cell-main">{{ $f['cliente'] ?: '—' }}</div>
                <div class="cell-sub">{{ $f['dni'] }} · {{ $f['tipo_persona'] === 'juridica' ? 'Persona jurídica' : 'Persona natural' }}</div></td>
              <td><div>{{ $f['zona'] }}</div><div class="cell-sub">{{ $f['tienda'] }}</div></td>
              <td class="num">{{ $f['monto_honrado'] !== null ? number_format($f['monto_honrado'], 2) : $f['monto_texto'] }}</td>
              <td class="nowrap">{{ $f['lote'] }}</td>
              @foreach($f['docs'] as $v)<td class="center">{!! $icono($v) !!}</td>@endforeach
              <td>{{ $f['estado_texto'] ?: '—' }}</td>
              <td>@isset($f['estado_calculado'])<span class="badge {{ \App\Support\Estilo::badgeHonramiento($f['estado_calculado']) }}">{{ $f['estado_calculado'] }}</span>@else — @endisset</td>
              <td class="num nowrap">{{ $f['faltantes_excel'] ?? '—' }} / {{ $f['faltantes_calculado'] ?? '—' }}</td>
              <td class="celda-texto">
                @foreach($f['errores'] as $m)<div class="fs-12 text-danger"><i class="bi bi-x-circle"></i> {{ $m }}</div>@endforeach
                @foreach($f['diferencias'] as $m)<div class="fs-12 text-mostaza"><i class="bi bi-exclamation-triangle"></i> {{ $m }}</div>@endforeach
                @foreach($f['avisos'] as $m)<div class="fs-12 text-gris"><i class="bi bi-info-circle"></i> {{ $m }}</div>@endforeach
                @if(!$f['errores'] && !$f['diferencias'] && !$f['avisos'])<span class="fs-12 text-verde"><i class="bi bi-check-circle"></i> Sin observaciones</span>@endif
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
</div>
<p class="cell-sub">D1 a D10: documentos del Excel en su orden (D1 = DNI o Reporte tributario con Ficha RUC · D10 = Evidencia de ingresos). Pase el cursor sobre cada encabezado para ver el nombre.</p>
@endsection

@push('scripts')
<script>
// Filtro por resultado (se combina con la búsqueda y paginación de la tabla)
document.querySelectorAll('#filtro-accion [data-f]').forEach(function (b) {
  b.addEventListener('click', function () {
    document.querySelectorAll('#filtro-accion [data-f]').forEach(function (x) { x.className = 'btn btn-sm btn-secondary'; });
    b.className = 'btn btn-sm btn-primary';
    var f = b.getAttribute('data-f');
    var input = document.querySelector('.table-search input');
    document.querySelectorAll('#tabla-previa tbody tr[data-accion]').forEach(function (tr) {
      var ok = !f || (f === 'avisos' ? tr.dataset.avisos === '1' : tr.dataset.accion === f);
      tr.toggleAttribute('data-oculta', !ok);
    });
    input.dispatchEvent(new Event('input'));
  });
});
</script>
@endpush
