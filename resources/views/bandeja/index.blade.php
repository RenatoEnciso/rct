@extends('layouts.app')
@section('title', 'Bandeja de aprobación')

@use('App\Support\Estilo')

@section('content')
@php
  $alcance = [
    1 => 'Pago de cuotas; cancelaciones totales Deficiente, Dudoso o Pérdida; conciliación de capital con SK menor a 3 UIT.',
    2 => 'Además: SK mayor a 3 UIT, excepciones a topes de descuento y exceso del límite de 4 conciliaciones.',
    3 => 'Casos escalados desde la Jefatura de Recuperaciones.',
  ];
  $num = fn($v) => rtrim(rtrim((string) $v, '0'), '.');
@endphp

<div class="page-head">
  <div>
    <h2>Bandeja de aprobación</h2>
    <p>Propuestas asignadas a su nivel de autonomía, pendientes de decisión.</p>
  </div>
  <div class="page-head-actions">
    <span class="badge badge-gray"><i class="bi bi-person-badge"></i> Nivel {{ auth()->user()->nivel() }} · {{ auth()->user()->rol->nombre }}</span>
    <span class="badge {{ $pendientes->count() ? 'badge-rojo' : 'badge-ok' }}">{{ $pendientes->count() }} pendiente(s)</span>
  </div>
</div>

<div class="notice"><i class="bi bi-info-circle"></i><span><strong>Alcance de su autonomía:</strong> {{ $alcance[auth()->user()->nivel()] ?? '' }}</span></div>

<div class="card card-flush">
  <div class="card-head" style="padding-bottom:4px">
    <div class="card-title"><i class="bi bi-inbox"></i> Propuestas pendientes</div>
  </div>
  <div style="padding:0 20px">
    <div class="table-wrap">
      <table data-rct-table data-search-placeholder="Buscar cliente, operación o ejecutivo">
        <thead>
          <tr><th>Enviado</th><th>Ejecutivo</th><th>Cliente</th><th class="num">Saldo capital</th><th>Calificación</th><th>Tipo</th><th>Envío</th><th></th></tr>
        </thead>
        <tbody>
          @forelse($pendientes as $p)
            <tr>
              <td class="nowrap">{{ $p->fecha_envio->format('d/m/Y') }}<div class="cell-sub">{{ $p->fecha_envio->diffForHumans() }}</div></td>
              <td>{{ $p->enviador->name }}<div class="cell-sub">{{ $p->cliente->tienda->nombre ?? '' }}</div></td>
              <td><div class="cell-main">{{ $p->cliente->nombre_cliente }}</div><div class="cell-sub">Op. {{ $p->cliente->operacion }}</div></td>
              <td class="num">{{ $p->cliente->saldo_formateado }}</td>
              <td><span class="badge badge-gray">{{ $p->cliente->calificacion_rcc }}</span></td>
              <td>{{ $p->tipo_negociacion_desc }}</td>
              <td>
                @if($p->veces_observado > 0)
                  <span class="badge badge-pending">Corrección {{ $p->veces_observado }}</span>
                @else
                  <span class="badge badge-gray">Primer envío</span>
                @endif
              </td>
              <td class="num"><button type="button" class="btn btn-primary btn-sm" data-modal-open="modal-{{ $p->id }}">Revisar</button></td>
            </tr>
          @empty
            <tr data-empty><td colspan="8" class="empty-state">
              <i class="bi bi-check2-all"></i><strong>Bandeja al día</strong><span>No tiene propuestas pendientes de decisión.</span>
            </td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
  <div style="height:16px"></div>
</div>

@foreach($pendientes as $p)
  @php $cli = $p->cliente; $det = $p->getDetalle(); @endphp
  <div class="rct-modal" id="modal-{{ $p->id }}" role="dialog" aria-labelledby="titulo-{{ $p->id }}">
    <div class="rct-modal-box">
      <div class="rct-modal-head">
        <div>
          <h3 class="rct-modal-title" id="titulo-{{ $p->id }}">{{ $cli->nombre_cliente }}</h3>
          <div class="cell-sub">FUD-{{ str_pad($p->id, 5, '0', STR_PAD_LEFT) }} · {{ $p->tipo_negociacion_desc }} · enviado por {{ $p->enviador->name }} el {{ $p->fecha_envio->format('d/m/Y') }}</div>
        </div>
        <button type="button" class="rct-modal-close" data-modal-close aria-label="Cerrar"><i class="bi bi-x-lg"></i></button>
      </div>

      <div class="rct-modal-body">
        @if($p->veces_observado > 0 && $p->padre)
          <div class="notice-warning"><i class="bi bi-arrow-repeat"></i>
            <span><strong>Corrección {{ $p->veces_observado }}.</strong> Observación anterior: {{ $p->padre->comentario_aprobador }}</span></div>
        @endif

        <div class="detail-grid" style="margin-bottom:16px">
          <div><div class="detail-label">Operación</div><div class="detail-value">{{ $cli->operacion }}</div></div>
          <div><div class="detail-label">Saldo capital</div><div class="detail-value">{{ $cli->saldo_formateado }}</div></div>
          <div><div class="detail-label">Días de atraso</div><div class="detail-value">{{ $cli->dias_atraso }}</div></div>
          <div><div class="detail-label">Calificación</div><div class="detail-value">{{ $cli->calificacion_rcc }}</div></div>
          <div><div class="detail-label">Situación de cartera</div><div class="detail-value">{{ $cli->estado_cartera }}</div></div>
          <div><div class="detail-label">Conciliaciones (12 meses)</div><div class="detail-value">{{ $cli->veces_conciliado }} de 4</div></div>
        </div>

        @if($det)
          <div class="card-plain" style="padding:14px;margin-bottom:16px">
            <div class="quote-label">Condiciones propuestas</div>
            <div class="detail-grid">
              @if($p->tipo_negociacion === 'cuotas')
                <div><div class="detail-label">Cuotas</div><div class="detail-value">{{ $det->cuota_desde }} a {{ $det->cuota_hasta }}</div></div>
                <div><div class="detail-label">Intereses</div><div class="detail-value">{{ $num($det->desc_interes) }}%</div></div>
                <div><div class="detail-label">Moratorio</div><div class="detail-value">{{ $num($det->desc_moratorio) }}%</div></div>
                <div><div class="detail-label">Pone al día</div><div class="detail-value">{{ $det->pone_al_dia ? 'Sí' : 'No' }}</div></div>
              @elseif($p->tipo_negociacion === 'cancelacion')
                <div><div class="detail-label">Monto a pagar</div><div class="detail-value">{{ Estilo::soles($det->monto_a_pagar) }}</div></div>
                <div><div class="detail-label">Intereses</div><div class="detail-value">{{ $num($det->desc_interes) }}%</div></div>
                <div><div class="detail-label">Capital</div><div class="detail-value">{{ $num($det->desc_capital) }}%</div></div>
                <div><div class="detail-label">Fecha límite</div><div class="detail-value">{{ $det->fecha_limite?->format('d/m/Y') ?? '—' }}</div></div>
              @else
                <div><div class="detail-label">Beneficio sobre SK</div><div class="detail-value">{{ $num($det->beneficio_pct_sk) }}%</div></div>
                <div><div class="detail-label">Etapa procesal</div><div class="detail-value">{{ $det->etapa_procesal ?? '—' }}</div></div>
                <div><div class="detail-label">Gravamen / embargo</div><div class="detail-value">{{ $det->monto_gravamen ? Estilo::soles($det->monto_gravamen) : '—' }}</div></div>
              @endif
            </div>
          </div>
        @endif

        <div class="quote-label">Sustento del ejecutivo</div>
        <div class="quote" style="margin-bottom:16px">{{ $p->motivo }}</div>

        @if($p->adjuntos->count())
          <div class="quote-label">Archivos adjuntos</div>
          <div class="file-list" style="margin-bottom:16px">
            @foreach($p->adjuntos as $adj)
              <a href="{{ route('fud.download', $adj) }}" class="file-chip"><i class="bi bi-file-earmark-arrow-down"></i><span>{{ $adj->nombre_archivo }}</span></a>
            @endforeach
          </div>
        @endif

        <form method="POST" action="{{ route('bandeja.decidir', $p) }}" enctype="multipart/form-data" class="form-section">
          @csrf
          <div class="field">
            <label for="comentario-{{ $p->id }}">Comentario <span class="field-hint">(obligatorio al observar o rechazar)</span></label>
            <textarea id="comentario-{{ $p->id }}" name="comentario" rows="3" placeholder="Condiciones, observaciones o documentos faltantes"></textarea>
          </div>
          <div class="field" style="margin-top:12px">
            <label for="adj-{{ $p->id }}">Adjuntar archivos (opcional)</label>
            <input id="adj-{{ $p->id }}" type="file" name="adjuntos_aprobador[]" multiple accept=".pdf,.jpg,.jpeg,.png,.xlsx,.docx" class="input-rct">
          </div>
          <div class="actions">
            <button type="submit" name="decision" value="Rechazado" class="btn btn-danger" data-confirm="El rechazo es definitivo. ¿Desea rechazar esta propuesta?"><i class="bi bi-x-circle"></i> Rechazar</button>
            <button type="submit" name="decision" value="Observado" class="btn btn-warning"><i class="bi bi-arrow-return-left"></i> Observar</button>
            <button type="submit" name="decision" value="Aprobado" class="btn btn-success" data-confirm="¿Confirma la aprobación de esta propuesta?"><i class="bi bi-check2-circle"></i> Aprobar</button>
          </div>
        </form>
      </div>
    </div>
  </div>
@endforeach
@endsection
