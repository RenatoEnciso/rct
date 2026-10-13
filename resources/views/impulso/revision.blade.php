@extends('layouts.app')
@section('title', 'Revisión de expedientes')

@section('content')
@include('gobierno._modulo')
@php
  $f = fn(array $c) => route('gobierno.revision', array_filter(array_merge(request()->only('zona_id', 'lote_id', 'honramiento'), $c), fn($v) => $v !== null && $v !== ''));
  $meta = [
    'PENDIENTE'             => ['gris',  'bi-hourglass',          'Sin documentos o sin revisar',                'Pendiente'],
    'INCOMPLETO'            => ['ambar', 'bi-exclamation-circle', 'Falta u observado algún obligatorio',         'Incompleto'],
    'INCOMPLETO DEFINITIVO' => ['rojo',  'bi-slash-circle',       'La tienda no cuenta con un obligatorio',      'Incompleto definitivo'],
    'COMPLETO'              => ['verde', 'bi-check2-circle',      'Todos los obligatorios del Anexo 13',         'Completo'],
  ];
@endphp
<div class="cc">
  <div class="page-head cc-head">
    <div>
      <div class="page-eyebrow">Impulso MyPerú · Etapa 3</div>
      <h2>Revisión de expedientes</h2>
      <p>Solo los documentos exigidos por COFIDE (Anexo 13) determinan el estado. Los demás se piden, pero no lo afectan.</p>
    </div>
    <div class="page-head-actions">
      <div class="cc-segmento" role="group" aria-label="Honramiento">
        @foreach(['Honrado' => 'Honrados', 'Solicitado' => 'En solicitud', 'todos' => 'Todos'] as $v => $lbl)
          <a href="{{ $f(['honramiento' => $v]) }}" class="{{ $honramiento === $v ? 'active' : '' }}">{{ $lbl }}</a>
        @endforeach
      </div>
      <select class="input-rct cc-select" aria-label="Lote" onchange="location.href=this.value">
        <option value="{{ $f(['lote_id' => null]) }}">Todos los lotes</option>
        @foreach($lotes as $l)<option value="{{ $f(['lote_id' => $l->id]) }}" @selected(request('lote_id') == $l->id)>{{ $l->nombre() }}</option>@endforeach
      </select>
      @unless($zonaFija)
        <select class="input-rct cc-select" aria-label="Zona" onchange="location.href=this.value">
          <option value="{{ $f(['zona_id' => null]) }}">Todas las zonas</option>
          @foreach($zonas as $z)<option value="{{ $f(['zona_id' => $z->id]) }}" @selected($zonaId == $z->id)>Zona {{ $z->nombre }}</option>@endforeach
        </select>
      @endunless
    </div>
  </div>

  <div class="rv-estados">
    @foreach($meta as $g => [$color, $ico, $desc, $estadoBandeja])
      <a href="{{ route('gobierno.index', array_filter(['estado' => $g === 'INCOMPLETO' ? null : $estadoBandeja, 'zona_id' => $zonaId, 'lote_id' => request('lote_id')])) }}" class="rv-estado {{ $color }} cc-aparece" style="--i:{{ $loop->index }}">
        <span class="rv-estado-ico"><i class="bi {{ $ico }}"></i></span>
        <span class="rv-estado-n" data-contar="{{ $grupos[$g] }}" data-formato="num">{{ $grupos[$g] }}</span>
        <span class="rv-estado-g">{{ $g }}</span>
        <span class="rv-estado-d">{{ $desc }}</span>
        <span class="rv-estado-pct">{{ $total ? number_format($grupos[$g] / $total * 100, 1) : '0.0' }}% de {{ $total }}</span>
      </a>
    @endforeach
  </div>

  <div class="cc-fila cc-fila-2-1">
    <div class="card cc-card cc-aparece" style="--i:4">
      <div class="card-title"><i class="bi bi-file-earmark-check"></i> Cumplimiento por documento obligatorio</div>
      <p class="card-sub">% de expedientes que presentan cada documento del Anexo 13, sobre los que lo exigen.</p>
      <div class="cc-metas">
        @forelse($documentos as $d)
          <div>
            <div class="cc-meta-head"><span class="fw-600">{{ $d['tipo']->nombre_corto }}</span>
              <span class="cc-meta-pct {{ $d['pct'] >= 80 ? 'ok' : ($d['pct'] >= 60 ? 'warn' : 'danger') }}">{{ number_format($d['pct'], 1) }}%</span></div>
            <div class="cc-barra"><div class="cc-barra-fill {{ $d['pct'] >= 80 ? 'ok' : ($d['pct'] >= 60 ? 'warn' : 'danger') }}" style="--w:{{ $d['pct'] }}%"></div></div>
            <div class="cc-meta-sub">{{ $d['presentado'] }} de {{ $d['exigido'] }} presentados{{ $d['observado'] ? ' · '.$d['observado'].' observados' : '' }}{{ $d['no_disponible'] ? ' · '.$d['no_disponible'].' no disponibles' : '' }}</div>
          </div>
        @empty
          <div class="empty-state"><span>Sin expedientes con este filtro.</span></div>
        @endforelse
      </div>
    </div>

    <div class="card cc-card cc-aparece" style="--i:5">
      <div class="card-title"><i class="bi bi-journal-check"></i> Regla del Anexo 13</div>
      <div class="rv-regla">
        <div><strong>Persona natural · {{ $natural->count() }} obligatorios</strong>
          <ul>@foreach($natural as $t)<li><i class="bi bi-check2"></i> {{ $t->nombre }}</li>@endforeach</ul></div>
        <div><strong>Persona jurídica · {{ $juridica->count() }} obligatorios</strong>
          <ul>@foreach($juridica as $t)<li><i class="bi bi-check2"></i> {{ $t->nombre }}</li>@endforeach</ul></div>
        <div><strong>Se piden, pero no afectan el estado</strong>
          <ul class="opc">@foreach($opcionales as $t)<li><i class="bi bi-dash"></i> {{ $t->nombre }}</li>@endforeach</ul></div>
      </div>
    </div>
  </div>

  <div class="cc-fila" style="grid-template-columns:minmax(0,1fr) minmax(0,1fr)">
    @foreach(['Por lote' => [$porLote, 'bi-collection'], 'Por zona' => [$porZona, 'bi-geo-alt']] as $titulo => [$filas, $ico])
      <div class="card cc-card card-flush cc-aparece" style="--i:6">
        <div class="card-head"><div class="card-title"><i class="bi {{ $ico }}"></i> {{ $titulo }}</div></div>
        <div class="table-wrap">
          <table>
            <thead><tr><th>{{ $titulo === 'Por lote' ? 'Lote' : 'Zona' }}</th><th class="num">Pend.</th><th class="num">Incompl.</th><th class="num">Def.</th><th class="num">Completo</th><th style="min-width:120px">% completo</th></tr></thead>
            <tbody>
              @foreach($filas as $nombre => $g)
                <tr>
                  <td class="cell-main">{{ $nombre }}</td>
                  <td class="num">{{ $g['PENDIENTE'] }}</td><td class="num">{{ $g['INCOMPLETO'] }}</td>
                  <td class="num">{{ $g['INCOMPLETO DEFINITIVO'] }}</td><td class="num fw-600">{{ $g['COMPLETO'] }}</td>
                  <td><div class="bar-row"><div class="cc-barra cc-barra-sm" style="flex:1"><div class="cc-barra-fill ok" style="--w:{{ $g['pct_completo'] }}%"></div></div>
                    <span class="fs-12 fw-600 num" style="min-width:44px;text-align:right">{{ number_format($g['pct_completo'], 1) }}%</span></div></td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
        <div style="height:8px"></div>
      </div>
    @endforeach
  </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('assets/js/rct-analitica.js') }}?v={{ filemtime(public_path('assets/js/rct-analitica.js')) }}"></script>
<script>RCTAnalitica.contadores(); requestAnimationFrame(function () { document.body.classList.add('cc-listo'); });</script>
@endpush
