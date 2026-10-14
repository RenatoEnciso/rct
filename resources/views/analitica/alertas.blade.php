@extends('layouts.app')
@section('title', 'Alertas de control')

@use('App\Models\AlertaControl')

@section('content')
@php
  $f = fn(array $c) => route('alertas.index', array_filter(array_merge(request()->only('estado', 'severidad', 'tipo', 'zona_id'), $c), fn($v) => $v !== null && $v !== ''));
  $sevActual = request('severidad');
  $iconoSev = ['Alta' => 'bi-exclamation-octagon', 'Media' => 'bi-exclamation-triangle', 'Baja' => 'bi-info-circle'];
  $estados = ['abiertas' => 'Abiertas', 'Nueva' => 'Nuevas', 'En revisión' => 'En revisión', 'Confirmada' => 'Confirmadas', 'Descartada' => 'Descartadas', 'todas' => 'Todas'];
  $nEstado = fn($e) => match ($e) {
    'abiertas' => ($porEstado['Nueva'] ?? 0) + ($porEstado['En revisión'] ?? 0),
    'todas'    => $porEstado->sum(),
    default    => $porEstado[$e] ?? 0,
  };
  $claseEstado = ['Nueva' => 'badge-rojo', 'En revisión' => 'badge-pending', 'Confirmada' => 'badge-danger', 'Descartada' => 'badge-gray'];
@endphp
<div class="cc">
  <div class="page-head cc-head">
    <div>
      <h2>Alertas de control</h2>
      <p>Patrones inusuales en propuestas, aprobaciones y sustentos, comparados con el promedio de su zona.</p>
    </div>
    <div class="page-head-actions">
      <select class="input-rct cc-select" aria-label="Tipo de alerta" onchange="location.href=this.value">
        <option value="{{ $f(['tipo' => null]) }}">Todos los tipos</option>
        @foreach(AlertaControl::TIPOS as $t => [$nombre])<option value="{{ $f(['tipo' => $t]) }}" @selected(request('tipo') === $t)>{{ $nombre }}</option>@endforeach
      </select>
      <select class="input-rct cc-select" aria-label="Zona" onchange="location.href=this.value">
        <option value="{{ $f(['zona_id' => null]) }}">Todas las zonas</option>
        @foreach($zonas as $z)<option value="{{ $f(['zona_id' => $z->id]) }}" @selected(request('zona_id') == $z->id)>Zona {{ $z->nombre }}</option>@endforeach
      </select>
    </div>
  </div>

  {{-- Contador por severidad (filtra al hacer clic) --}}
  <div class="al-sev">
    @foreach(['Alta', 'Media', 'Baja'] as $i => $s)
      <a href="{{ $f(['severidad' => $sevActual === $s ? null : $s]) }}" class="al-sev-card {{ strtolower($s) }} {{ $sevActual === $s ? 'active' : '' }}">
        <span class="al-sev-ico"><i class="bi {{ $iconoSev[$s] }}"></i></span>
        <span><span class="al-sev-n" data-contar="{{ $porSev[$s] ?? 0 }}" data-formato="num">{{ $porSev[$s] ?? 0 }}</span><span class="al-sev-label">Severidad {{ strtolower($s) }}</span></span>
      </a>
    @endforeach
  </div>

  <div class="card cc-card card-flush">
    <div class="card-head">
      <div class="cc-segmento al-estados" role="group" aria-label="Estado">
        @foreach($estados as $e => $lbl)
          <a href="{{ $f(['estado' => $e === 'abiertas' ? null : $e]) }}" class="{{ $estado === $e ? 'active' : '' }}">{{ $lbl }} <span class="cell-sub">{{ $nEstado($e) }}</span></a>
        @endforeach
      </div>
      <span class="cell-sub">{{ $alertas->count() }} {{ $alertas->count() === 1 ? 'alerta' : 'alertas' }}{{ $sevActual ? ' de severidad '.strtolower($sevActual) : '' }}</span>
    </div>

    <div class="al-lista">
      @forelse($alertas as $a)
        <a href="{{ route('alertas.show', $a) }}" class="al-item {{ strtolower($a->severidad) }}">
          <span class="al-item-ico"><i class="bi {{ $a->iconoTipo() }}"></i></span>
          <span class="al-item-main">
            <span class="al-item-tipo">{{ $a->nombreTipo() }} · Severidad {{ strtolower($a->severidad) }}</span>
            <span class="al-item-titulo">{{ $a->titulo }}</span>
            <span class="al-item-sub"><i class="bi {{ ['asesor' => 'bi-person', 'aprobador' => 'bi-person-check', 'tienda' => 'bi-shop', 'cliente' => 'bi-person-vcard'][$a->entidad_tipo] ?? 'bi-dot' }}"></i>
              {{ $a->entidad_nombre }}{{ $a->zona ? ' · Zona '.$a->zona->nombre : '' }} · Detectada el {{ $a->detectada_en->format('d/m/Y') }}</span>
          </span>
          <span class="al-item-metrica">
            <strong>{{ $a->formato((float) $a->valor_observado) }}</strong>
            <span>vs. {{ $a->formato((float) $a->valor_referencia) }} {{ $a->entidad_tipo === 'cliente' ? 'habitual' : 'promedio' }}</span>
          </span>
          <span class="badge {{ $claseEstado[$a->estado] ?? 'badge-gray' }} al-item-estado">{{ $a->estado }}</span>
          <i class="bi bi-chevron-right text-gris"></i>
        </a>
      @empty
        <div class="empty-state"><i class="bi bi-shield-check"></i><strong>Sin alertas</strong><span>No hay alertas con los filtros aplicados.</span></div>
      @endforelse
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('assets/js/rct-analitica.js') }}?v={{ filemtime(public_path('assets/js/rct-analitica.js')) }}"></script>
<script>RCTAnalitica.contadores();</script>
@endpush
