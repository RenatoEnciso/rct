@extends('layouts.app')
@section('title', 'Alerta de control')

@use('App\Models\AlertaControl')

@section('content')
@php
  $serie = $a->serie ?? [];
  $tipoSerie = $serie['tipo'] ?? null;
  $tituloGrafico = match ($a->entidad_tipo) {
    'asesor'    => 'Esta persona vs. promedio de la zona',
    'aprobador' => $serie['titulo'] ?? 'Comparación con el resto de asesores',
    'tienda'    => 'Esta tienda vs. las demás tiendas de la zona',
    default     => 'Beneficios del cliente',
  };
  $claseEstado = ['Nueva' => 'badge-rojo', 'En revisión' => 'badge-pending', 'Confirmada' => 'badge-danger', 'Descartada' => 'badge-gray'];
  $max = max((float) $a->valor_observado, (float) $a->valor_referencia) ?: 1;
  $etiquetaRef = ['asesor' => 'Promedio de la zona', 'aprobador' => 'Con el resto de asesores', 'tienda' => 'Resto de tiendas', 'cliente' => 'Lo habitual en la cartera'][$a->entidad_tipo] ?? 'Referencia';
  $etiquetaObs = ['asesor' => 'Esta persona', 'aprobador' => 'Con este asesor', 'tienda' => 'Esta tienda', 'cliente' => 'Este cliente'][$a->entidad_tipo] ?? 'Observado';
@endphp
<div class="cc">
  <div class="page-head cc-head">
    <div>
      <div class="page-eyebrow"><a href="{{ route('alertas.index') }}">Alertas de control</a> / {{ $a->nombreTipo() }}</div>
      <h2>{{ $a->titulo }}</h2>
      <p>
        <span class="badge al-badge-sev {{ strtolower($a->severidad) }}">Severidad {{ strtolower($a->severidad) }}</span>
        <span class="badge {{ $claseEstado[$a->estado] ?? 'badge-gray' }}">{{ $a->estado }}</span>
        <span class="text-gris" style="margin-left:6px">{{ $a->entidad_nombre }}{{ $a->zona ? ' · Zona '.$a->zona->nombre : '' }} · Detectada el {{ $a->detectada_en->format('d/m/Y') }}</span>
      </p>
    </div>
    <div class="page-head-actions">
      <a href="{{ route('alertas.index') }}" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Volver a la bandeja</a>
    </div>
  </div>

  <div class="cc-fila cc-fila-2-1">
    <div class="card cc-card cc-aparece" style="--i:0">
      <div class="card-title"><i class="bi {{ $a->iconoTipo() }}"></i> Qué se detectó</div>
      <p class="al-desc">{{ $a->descripcion }}</p>

      <div class="al-comparacion">
        <div class="al-comp-item">
          <span class="al-comp-label">{{ $etiquetaObs }}</span>
          <span class="al-comp-valor rojo">{{ $a->formato((float) $a->valor_observado) }}</span>
          <div class="cc-barra"><div class="cc-barra-fill" style="background:var(--rojo);--w:{{ round($a->valor_observado / $max * 100, 1) }}%"></div></div>
        </div>
        <div class="al-comp-item">
          <span class="al-comp-label">{{ $etiquetaRef }}</span>
          <span class="al-comp-valor">{{ $a->formato((float) $a->valor_referencia) }}</span>
          <div class="cc-barra"><div class="cc-barra-fill" style="background:#8a94a3;--w:{{ round($a->valor_referencia / $max * 100, 1) }}%"></div></div>
        </div>
        @if($a->comparacion())
          <div class="al-comp-lectura"><i class="bi bi-arrow-up-right-circle"></i> {{ ucfirst($a->comparacion()) }} · {{ $a->metrica }}</div>
        @endif
      </div>

      <div class="al-grafico">
        <div class="card-title" style="margin-top:6px"><i class="bi bi-bar-chart"></i> {{ $tituloGrafico }}</div>
        @if(in_array($tipoSerie, ['mensual', 'barras']))
          <div class="cc-chart" style="height:{{ $tipoSerie === 'barras' ? max(180, count($serie['items'] ?? []) * 44 + 40) : 280 }}px">
            <canvas id="chart-alerta" role="img" aria-label="{{ $tituloGrafico }}"></canvas>
          </div>
        @elseif($tipoSerie === 'linea_tiempo')
          <div class="timeline al-timeline">
            @foreach($serie['items'] ?? [] as $i => $it)
              <div class="timeline-item {{ $i === count($serie['items']) - 1 ? 'current' : 'warn' }}">
                <div class="timeline-title">{{ $it['detalle'] }} · aprobado</div>
                <div class="timeline-meta">Enviado el {{ $it['fecha'] }} ·
                  <a href="{{ route('aprobaciones.show', $it['fud']) }}">Ver trazabilidad del expediente #{{ $it['fud'] }}</a></div>
              </div>
            @endforeach
          </div>
        @endif
      </div>
    </div>

    {{-- Gestión --}}
    <div class="card cc-card cc-aparece" style="--i:1">
      <div class="card-title"><i class="bi bi-clipboard-check"></i> Gestión de la alerta</div>
      <form method="POST" action="{{ route('alertas.estado', $a) }}" class="al-form">
        @csrf
        <div class="field">
          <label>Nuevo estado</label>
          <div class="al-opciones" role="radiogroup">
            @foreach(['En revisión' => 'bi-search', 'Confirmada' => 'bi-check2-circle', 'Descartada' => 'bi-x-circle', 'Nueva' => 'bi-arrow-counterclockwise'] as $e => $ico)
              <label class="al-opcion">
                <input type="radio" name="estado" value="{{ $e }}" @checked(old('estado', $a->estado === 'Nueva' ? 'En revisión' : $a->estado) === $e)>
                <span><i class="bi {{ $ico }}"></i> {{ $e === 'Nueva' ? 'Reabrir' : $e }}</span>
              </label>
            @endforeach
          </div>
        </div>
        <div class="field">
          <label for="comentario">Comentario <span class="req">*</span></label>
          <textarea id="comentario" name="comentario" rows="4" required minlength="10" placeholder="Acción realizada o sustento de la decisión">{{ old('comentario') }}</textarea>
          <span class="field-hint">Queda registrado en el historial de la alerta y en la bitácora.</span>
        </div>
        <button type="submit" class="btn btn-primary" style="width:100%"><i class="bi bi-check2"></i> Guardar estado</button>
      </form>

      <div class="card-title" style="margin-top:24px"><i class="bi bi-clock-history"></i> Historial</div>
      <div class="timeline">
        @foreach($a->historial as $h)
          <div class="timeline-item {{ ['Confirmada' => 'danger', 'Descartada' => 'ok', 'En revisión' => 'warn'][$h->estado_nuevo] ?? '' }}">
            <div class="timeline-title">{{ $h->estado_anterior }} → {{ $h->estado_nuevo }}</div>
            <div class="timeline-meta">{{ $h->usuario->name ?? '—' }} · {{ \Illuminate\Support\Carbon::parse($h->created_at)->format('d/m/Y H:i') }}</div>
            @if($h->comentario)<div class="timeline-body">{{ $h->comentario }}</div>@endif
          </div>
        @endforeach
        <div class="timeline-item">
          <div class="timeline-title">Alerta detectada</div>
          <div class="timeline-meta">Alertas de control · {{ $a->detectada_en->format('d/m/Y H:i') }}</div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
@if(in_array($tipoSerie, ['mensual', 'barras']))
  <script src="{{ asset('vendor/chartjs/chart.umd.min.js') }}"></script>
@endif
<script src="{{ asset('assets/js/rct-analitica.js') }}?v={{ filemtime(public_path('assets/js/rct-analitica.js')) }}"></script>
<script>RCTAnalitica.alerta({ serie: {{ Js::from($serie) }}, unidad: {{ Js::from($a->unidad) }} });</script>
@endpush
