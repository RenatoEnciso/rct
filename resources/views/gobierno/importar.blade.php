@extends('layouts.app')
@section('title', 'Importar expedientes de honramiento')

@section('content')
<div class="page-head">
  <div>
    <div class="page-eyebrow"><a href="{{ route('gobierno.index') }}">Programas de gobierno</a> / Importar</div>
    <h2>Importar Excel de revisión</h2>
    <p>Carga el Excel de seguimiento de expedientes de honramiento. Antes de guardar verás una vista previa fila por fila.</p>
  </div>
  <div class="page-head-actions">
    <a href="{{ route('gobierno.importar.plantilla') }}" class="btn btn-secondary"><i class="bi bi-file-earmark-excel"></i> Descargar plantilla</a>
  </div>
</div>

<div class="grid2" style="grid-template-columns:1.2fr 1fr;margin-bottom:20px">
  <div class="card">
    <div class="card-title"><i class="bi bi-upload"></i> Archivo</div>
    <form method="POST" action="{{ route('gobierno.importar.previsualizar') }}" enctype="multipart/form-data" id="form-importar">
      @csrf
      <label class="doc-drop" style="padding:22px 16px">
        <i class="bi bi-file-earmark-spreadsheet" style="font-size:26px"></i>
        <span><span class="doc-file-name" id="nombre-archivo">Seleccionar archivo Excel (.xlsx, .xls o .csv)</span><br>
          <span class="field-hint">Máximo 20 MB · formato de la plantilla</span></span>
        <input type="file" name="archivo" accept=".xlsx,.xls,.csv" required
               onchange="document.getElementById('nombre-archivo').textContent = this.files[0]?.name || 'Seleccionar archivo'">
      </label>
      <div class="actions">
        <button type="submit" class="btn btn-primary"><i class="bi bi-eye"></i> Ver vista previa</button>
      </div>
    </form>
  </div>

  <div class="card">
    <div class="card-title"><i class="bi bi-info-circle"></i> Cómo se interpreta el archivo</div>
    <div class="metric-list fs-13">
      <div class="d-flex gap-2"><i class="bi bi-key text-rojo"></i><span>El <strong>ID</strong> identifica el expediente: si existe se actualiza, si no se crea.</span></div>
      <div class="d-flex gap-2"><i class="bi bi-calculator text-rojo"></i><span><strong>Estado</strong> y <strong>N° faltantes</strong> los calcula RCT con los documentos que exige COFIDE: 6 para persona natural y 7 para persona jurídica (RUC 20).</span></div>
      <div class="d-flex gap-2"><i class="bi bi-1-circle text-rojo"></i><span>Un <strong>1</strong> se registra como documento presentado (archivo en el SFTP); un <strong>0</strong>, como faltante. Los archivos ya cargados en RCT se conservan.</span></div>
      <div class="d-flex gap-2"><i class="bi bi-calendar-check text-rojo"></i><span>Las fechas de revisión pasan al <strong>historial de revisiones</strong> (zona y central).</span></div>
      <div class="d-flex gap-2"><i class="bi bi-diagram-3 text-rojo"></i><span>Zonas, tiendas, lotes y gestiones nuevas se crean y se informan en la vista previa.</span></div>
    </div>
  </div>
</div>

<div class="card card-flush">
  <div class="card-head"><div class="card-title"><i class="bi bi-clock-history"></i> Últimas importaciones</div></div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Fecha</th><th>Archivo</th><th>Realizada por</th><th class="num">Filas</th><th class="num">Creados</th><th class="num">Actualizados</th><th class="num">Con diferencias</th><th class="num">Con error</th></tr></thead>
      <tbody>
        @forelse($importaciones as $i)
          <tr>
            <td class="nowrap">{{ $i->created_at->format('d/m/Y H:i') }}</td>
            <td class="cell-main">{{ $i->archivo }}</td>
            <td>{{ $i->usuario->name ?? '—' }}</td>
            <td class="num">{{ $i->filas }}</td>
            <td class="num">{{ $i->creados }}</td>
            <td class="num">{{ $i->actualizados }}</td>
            <td class="num">{{ $i->con_diferencias }}</td>
            <td class="num">@if($i->con_error)<span class="badge badge-danger">{{ $i->con_error }}</span>@else 0 @endif</td>
          </tr>
        @empty
          <tr><td colspan="8" class="empty-state"><i class="bi bi-inbox"></i><strong>Sin importaciones</strong><span>Aún no se ha importado ningún archivo.</span></td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>
@endsection

@push('scripts')
<script>
document.getElementById('form-importar').addEventListener('submit', function () {
  var b = this.querySelector('button[type=submit]'); b.disabled = true; b.innerHTML = '<span class="spinner"></span> Analizando archivo…';
});
</script>
@endpush
