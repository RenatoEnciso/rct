@extends('layouts.app')
@section('title', 'Devolución a COFIDE · '.$dev->nombrePeriodo())

@use('App\Support\Estilo')

@section('content')
@include('gobierno._modulo')
@php
  $cruzado = (bool) $dev->txt_importado_en;
  $cuadra = $dev->cuadra();
  $semaforo = [
    'ok'         => ['ok', 'bi-check-circle-fill', 'Cuadra'],
    'diferencia' => ['error', 'bi-x-circle-fill', 'Diferencia'],
    'solo_rct'   => ['error', 'bi-x-circle-fill', 'No figura en el TXT'],
    'solo_txt'   => ['error', 'bi-x-circle-fill', 'Solo en el TXT'],
    'pendiente'  => ['pend', 'bi-hourglass', 'Por cruzar'],
  ];
  $esJefe = auth()->user()->rol?->nombre === 'Jefe de Recuperaciones';
@endphp
<div class="cc dv">
  <div class="page-head cc-head">
    <div>
      <div class="page-eyebrow"><a href="{{ route('impulso.devoluciones') }}">Devoluciones a COFIDE</a> / {{ $dev->nombrePeriodo() }}</div>
      <h2>Devolución a COFIDE · {{ $dev->nombrePeriodo() }}</h2>
      <p>Reversiones agregadas de {{ mb_strtolower($dev->nombrePeriodo()) }} cruzadas crédito por crédito con la notificación de recuperación extrajudicial de COFIDE.
        Fecha límite: <strong>{{ $dev->fecha_limite->format('d/m/Y') }}</strong>.</p>
    </div>
    <div class="page-head-actions">
      @if($cruzado)<a href="{{ route('impulso.devoluciones.show', [$dev, 'animar' => 1]) }}" class="btn btn-secondary"><i class="bi bi-play-circle"></i> Ver cruce</a>@endif
      <button type="button" class="btn btn-secondary" data-modo-presentacion><i class="bi bi-arrows-fullscreen"></i> Modo presentación</button>
    </div>
  </div>

  {{-- Resultado del cruce --}}
  <div class="dv-hero" id="dv-hero" data-animar="{{ $animar && $cruzado ? 1 : 0 }}">
    <div class="dv-cifra cc-aparece" style="--i:0">
      <span class="dv-cifra-lbl"><i class="bi bi-calculator"></i> Calculado por RCT</span>
      <span class="dv-cifra-val">{{ Estilo::solesPe($dev->monto_calculado) }}</span>
      <span class="dv-cifra-sub">{{ $dev->creditos }} créditos · {{ $dev->pagos }} pagos · capital × cobertura</span>
    </div>
    <div class="dv-igual" aria-hidden="true"><i class="bi bi-arrow-left-right"></i></div>
    <div class="dv-cifra cc-aparece" style="--i:1">
      <span class="dv-cifra-lbl"><i class="bi bi-file-earmark-text"></i> Notificado por COFIDE (TXT)</span>
      <span class="dv-cifra-val">{{ $cruzado ? Estilo::solesPe($dev->monto_notificado) : '—' }}</span>
      <span class="dv-cifra-sub">{{ $cruzado ? $dev->txt_archivo.' · '.$dev->txt_importado_en->format('d/m/Y H:i') : 'Importe la notificación TXT para cruzar' }}</span>
    </div>
    <div class="dv-diferencia {{ $cruzado ? ($cuadra ? 'cuadra' : 'error') : 'pendiente' }} cc-aparece" style="--i:2" id="dv-diferencia">
      <span class="dv-dif-lbl">Diferencia</span>
      <span class="dv-dif-val" id="dv-dif-val" data-final="{{ $cruzado ? (string) $dev->diferencia : '' }}" data-inicio="{{ (string) $dev->monto_calculado }}">
        {{ $cruzado ? Estilo::solesPe($dev->diferencia) : '—' }}</span>
      <span class="dv-dif-estado" id="dv-dif-estado">
        @if(!$cruzado)<i class="bi bi-hourglass"></i> Pendiente de cruce
        @elseif($cuadra)<i class="bi bi-check-circle-fill"></i> Cuadra al 100 % con COFIDE
        @else<i class="bi bi-x-circle-fill"></i> {{ $dev->n_diferencias }} {{ $dev->n_diferencias === 1 ? 'crédito' : 'créditos' }} con diferencia
        @endif
      </span>
    </div>
  </div>
  <div class="dv-progreso" id="dv-progreso" hidden><div class="dv-progreso-barra"><span></span></div><span class="dv-progreso-txt">Cruzando…</span></div>

  <div class="cc-fila dv-cuerpo">
    {{-- Cruce crédito por crédito --}}
    <div class="card cc-card card-flush cc-aparece" style="--i:3">
      <div class="card-head">
        <div class="card-title"><i class="bi bi-list-check"></i> Cruce crédito por crédito</div>
        <span class="cell-sub">{{ $detalles->count() }} créditos · {{ $detalles->where('resultado', 'ok')->count() }} cuadran</span>
      </div>
      <div class="table-wrap dv-tabla-wrap">
        <table class="dv-tabla">
          <thead><tr><th style="width:150px">Resultado</th><th>Cliente</th><th>N° crédito</th><th>Lote</th><th class="num">Pagos</th><th class="num">Capital</th>
            <th class="num">Cobertura</th><th class="num">RCT</th><th class="num">COFIDE (TXT)</th><th class="num">Diferencia</th></tr></thead>
          <tbody>
            @foreach($detalles as $d)
              @php [$cls, $ico, $txt] = $semaforo[$d->resultado] ?? $semaforo['pendiente']; @endphp
              <tr class="dv-fila {{ $cls }}" data-clase="{{ $cls }}">
                <td><span class="dv-sem {{ $cls }}"><i class="bi {{ $ico }}"></i> {{ $txt }}</span></td>
                <td class="cell-main">{{ $d->cliente ?? '—' }}<div class="cell-sub">{{ $d->expediente?->tienda?->nombre }}</div></td>
                <td class="nowrap">{{ $d->credito_sicmact }}</td>
                <td class="nowrap">{{ $d->lote ? 'Lote '.preg_replace('/\D/', '', $d->lote) : '—' }}</td>
                <td class="num">{{ $d->pagos ?: '—' }}</td>
                <td class="num">{{ Estilo::solesPe($d->capital) }}</td>
                <td class="num">{{ $d->factor_cobertura ? number_format($d->factor_cobertura * 100, 2, ',', '').'%' : '—' }}</td>
                <td class="num fw-600">{{ Estilo::solesPe($d->monto_rct) }}</td>
                <td class="num">{{ $d->monto_txt !== null ? Estilo::solesPe($d->monto_txt) : '—' }}</td>
                <td class="num fw-600 dv-dif-celda">{{ $d->diferencia !== null ? Estilo::solesPe($d->diferencia) : '—' }}</td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
      <div style="height:8px"></div>
    </div>

    {{-- Acciones --}}
    <div class="dv-lado">
      @if($dev->cerrada())
        <div class="card cc-card dv-cerrada cc-aparece" style="--i:4">
          <i class="bi bi-patch-check-fill"></i>
          <div><strong>Devuelta a COFIDE</strong>
            <span>{{ $dev->cerrado_en->format('d/m/Y H:i') }} · {{ $dev->cerrador->name ?? '' }}</span>
            @if($dev->justificacion)<span class="dv-justif">Cerrada con justificación: {{ $dev->justificacion }}</span>@endif</div>
        </div>
      @else
        <div class="card cc-card cc-aparece" style="--i:4">
          <div class="card-title"><span class="lt-num">1</span> Notificación TXT de COFIDE</div>
          <form method="POST" action="{{ route('impulso.devoluciones.cruzar', $dev) }}" enctype="multipart/form-data" class="dv-form">@csrf
            <input type="file" name="txt" accept=".txt" required aria-label="Archivo TXT de COFIDE">
            <button class="btn btn-primary"><i class="bi bi-arrow-left-right"></i> {{ $cruzado ? 'Volver a cruzar' : 'Importar y cruzar' }}</button>
          </form>
          <div class="dv-links">
            <a href="{{ route('impulso.devoluciones.txt-ejemplo', $dev) }}"><i class="bi bi-download"></i> TXT de ejemplo (cuadrado)</a>
            @if($cruzado)<a href="{{ route('impulso.devoluciones.txt', $dev) }}"><i class="bi bi-file-earmark-text"></i> TXT importado</a>@endif
          </div>
        </div>
        <div class="card cc-card cc-aparece" style="--i:5">
          <div class="card-title"><span class="lt-num">2</span> Cerrar la devolución</div>
          @if($cuadra)
            <p class="card-sub">Lo calculado por RCT cuadra al 100 % con COFIDE.</p>
            <form method="POST" action="{{ route('impulso.devoluciones.cerrar', $dev) }}">@csrf
              <button class="btn btn-primary" style="width:100%"><i class="bi bi-check2-all"></i> Cerrar devolución por {{ Estilo::solesPe($dev->monto_calculado) }}</button>
            </form>
          @elseif($cruzado && $esJefe)
            <p class="card-sub">Hay diferencias. Solo se cierra con diferencia S/ 0,00 o con la justificación del Jefe de Recuperaciones.</p>
            <form method="POST" action="{{ route('impulso.devoluciones.cerrar', $dev) }}" class="dv-form">@csrf
              <textarea name="justificacion" rows="3" minlength="20" required placeholder="Justificación (mínimo 20 caracteres)">{{ old('justificacion') }}</textarea>
              <button class="btn btn-secondary"><i class="bi bi-shield-exclamation"></i> Cerrar con justificación</button>
            </form>
          @else
            <p class="card-sub">{{ $cruzado ? 'Resuelva las diferencias (o pida al Jefe de Recuperaciones que cierre con justificación).' : 'Disponible después del cruce con el TXT.' }}</p>
          @endif
        </div>
      @endif
      <div class="card cc-card dv-regla cc-aparece" style="--i:6">
        <div class="card-title"><i class="bi bi-info-circle"></i> Cómo se calcula</div>
        <ul>
          <li>Reversión por pago = capital × factor de cobertura, solo si el crédito estaba honrado a la fecha del pago.</li>
          <li>Devolución del mes = Σ reversiones agregadas del mes no devueltas antes.</li>
          <li>Diferencia = RCT − COFIDE (TXT), por crédito y en total.</li>
        </ul>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('assets/js/rct-analitica.js') }}?v={{ filemtime(public_path('assets/js/rct-analitica.js')) }}"></script>
<script>RCTAnalitica.cruce();</script>
@endpush
