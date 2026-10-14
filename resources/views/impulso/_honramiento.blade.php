{{-- Etapa 2 · Honramiento: importación del reporte de COFIDE y aviso a agencias por zona (ficha del lote) --}}
@use('App\Support\Estilo')
@if(in_array($lote->estado, ['Cargado en COFIDE', 'Honrado', 'Honrado parcial']))
  @php $zonas = app(\App\Services\Impulso\Avisos::class)->resumenZonas($lote); @endphp
  <div class="card cc-card mt-3">
    <div class="card-head">
      <div>
        <div class="card-title"><span class="lt-num">4</span> Honramiento de COFIDE</div>
        <p class="card-sub m-0">Reporte detallado por crédito: saldo insoluto, interés devengado, factor de cobertura y monto a honrar.</p>
      </div>
      @if($lote->esHonrado())
        <span class="lt-ok"><i class="bi bi-check-circle"></i> Reporte importado el {{ $lote->reporte_importado_en?->format('d/m/Y H:i') }} · {{ $lote->reporte_archivo }}</span>
      @endif
    </div>

    @if(!$lote->esHonrado())
      <form method="POST" action="{{ route('impulso.honramiento.previsualizar', $lote) }}" enctype="multipart/form-data" class="lt-import">
        @csrf
        <div class="field flex-grow-1">
          <label for="archivo">Reporte de honramiento de COFIDE (Excel)</label>
          <input id="archivo" name="archivo" type="file" accept=".xlsx,.xls" required>
          <span class="field-hint">Al confirmar, cada crédito pasa a Honrado con su fecha y se avisa a las agencias de cada zona.</span>
        </div>
        <button class="btn btn-primary"><i class="bi bi-upload"></i> Revisar reporte</button>
        <a href="{{ route('impulso.honramiento.ejemplo', $lote) }}" class="btn btn-ghost"><i class="bi bi-download"></i> Reporte de ejemplo</a>
      </form>
    @else
      <div class="card-title mt-1"><i class="bi bi-megaphone"></i> Aviso a las agencias por zona</div>
      <p class="card-sub">
        {{ $lote->aviso_agencias_en ? 'Enviado el '.$lote->aviso_agencias_en->format('d/m/Y H:i').' (campana y correo).' : 'Aún no se envía.' }}
        Formato de nombre de archivo: <code>{{ \App\Services\Impulso\Avisos::formatoArchivo() }}</code> · PDF, un documento por archivo.
      </p>
      <div class="table-wrap">
        <table>
          <thead><tr><th>Zona</th><th>Tiendas</th><th class="num">Créditos honrados</th><th class="num">Pendientes de documentación</th><th class="num">Monto honrado</th></tr></thead>
          <tbody>
            @foreach($zonas as $z)
              <tr>
                <td class="cell-main">{{ $z['zona'] }}</td>
                <td class="fs-13">{{ collect($z['por_tienda'])->map(fn($t) => $t['tienda'].' ('.$t['honrados'].')')->implode(', ') }}</td>
                <td class="num">{{ $z['honrados'] }}</td>
                <td class="num fw-600 {{ $z['pendientes'] ? 'text-ambar' : 'text-verde' }}">{{ $z['pendientes'] }}</td>
                <td class="num">{{ Estilo::solesPe($z['monto']) }}</td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
      <form method="POST" action="{{ route('impulso.honramiento.avisar', $lote) }}" style="margin-top:12px">@csrf
        <button class="btn btn-secondary btn-sm"><i class="bi bi-send"></i> Reenviar aviso a las agencias</button>
      </form>
    @endif
  </div>
@endif
