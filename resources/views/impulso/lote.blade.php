@extends('layouts.app')
@section('title', 'Lote '.$lote->codigo)

@use('App\Support\Estilo')
@use('App\Services\Impulso\Calculos')

@section('content')
@include('gobierno._modulo')
@php
  $pasos = ['En preparación', 'Pendiente de firma', 'Firmado', 'Cargado en COFIDE', 'Honrado'];
  $actual = match ($lote->estado) { 'Observado' => 3, 'Honrado parcial' => 4, default => max(0, array_search($lote->estado, $pasos)) };
  $firmas = collect($lote->firmas ?? [])->keyBy('nombre');
  $montoHonrar = Calculos::sumar($expedientes->pluck('monto_honrado'));
  $badge = ['Solicitado' => 'badge-info', 'Honrado' => 'badge-ok', 'No honrado' => 'badge-danger'];
  $nj = $expedientes->where('tipo_persona', 'juridica')->count();
@endphp
<div class="cc">
  <div class="page-head cc-head">
    <div>
      <div class="page-eyebrow"><a href="{{ route('impulso.lotes') }}">Lotes de honramiento</a> / {{ $lote->codigo }}</div>
      <h2>{{ $lote->codigo }} · {{ $lote->programa }}</h2>
      <p>Corte del {{ $lote->fecha_corte?->format('d/m/Y') ?? '—' }}{{ $lote->creador ? ' · Armado por '.$lote->creador->name : '' }}</p>
    </div>
  </div>

  {{-- Avance del lote --}}
  <div class="lt-pasos cc-aparece" style="--i:0">
    @foreach($pasos as $i => $p)
      @php $estadoPaso = $i < $actual ? 'hecho' : ($i === $actual ? ($lote->estado === 'Observado' ? 'error' : 'actual') : ''); @endphp
      <div class="lt-paso {{ $estadoPaso }}">
        <span class="lt-paso-n">@if($estadoPaso === 'hecho')<i class="bi bi-check-lg"></i>@elseif($estadoPaso === 'error')<i class="bi bi-exclamation-lg"></i>@else{{ $i + 1 }}@endif</span>
        <span class="lt-paso-t">{{ $i === 3 && $lote->estado === 'Observado' ? 'Observado por COFIDE' : ($i === 4 && $lote->estado === 'Honrado parcial' ? 'Honrado parcial' : $p) }}</span>
      </div>
    @endforeach
  </div>

  <div class="cc-kpis pr-kpis">
    <div class="cc-kpi cc-aparece" style="--i:1"><div class="cc-kpi-ico azul"><i class="bi bi-list-ol"></i></div>
      <div class="cc-kpi-label">Registros (RCT)</div><div class="cc-kpi-valor">{{ $lote->registros_rct }}</div>
      <div class="cc-kpi-sub">{{ $nj }} {{ $nj === 1 ? 'persona jurídica' : 'personas jurídicas' }}</div></div>
    <div class="cc-kpi cc-aparece" style="--i:2"><div class="cc-kpi-ico rojo"><i class="bi bi-cash-stack"></i></div>
      <div class="cc-kpi-label">Saldo insoluto total (RCT)</div><div class="cc-kpi-valor">{{ Estilo::solesPe($lote->saldo_rct) }}</div>
      <div class="cc-kpi-sub">Σ saldo insoluto de los créditos</div></div>
    <div class="cc-kpi cc-aparece" style="--i:3"><div class="cc-kpi-ico verde"><i class="bi bi-shield-check"></i></div>
      <div class="cc-kpi-label">{{ $lote->esHonrado() ? 'Monto honrado' : 'Monto a honrar (estimado)' }}</div><div class="cc-kpi-valor">{{ Estilo::solesPe($montoHonrar) }}</div>
      <div class="cc-kpi-sub">{{ $lote->esHonrado() ? 'Según el reporte de COFIDE' : '(saldo + interés) × cobertura' }}</div></div>
    <div class="cc-kpi cc-aparece" style="--i:4"><div class="cc-kpi-ico ambar"><i class="bi bi-calendar-check"></i></div>
      <div class="cc-kpi-label">Plazo de documentación</div>
      <div class="cc-kpi-valor">{{ $lote->fechaLimite()?->format('d/m/Y') ?? '—' }}</div>
      <div class="cc-kpi-sub">{{ $lote->fechaLimite() ? ($lote->diasRestantes() >= 0 ? $lote->diasRestantes().' días restantes' : 'Vencido hace '.abs($lote->diasRestantes()).' días') : 'Inicia con la conformidad de COFIDE' }}</div></div>
  </div>

  <div class="lt-etapas">
    {{-- 1. Documentos --}}
    <div class="card cc-card cc-aparece" style="--i:5">
      <div class="card-title"><span class="lt-num">1</span> Notificación y plantilla de honramiento</div>
      <p class="card-sub">Notificación a COFIDE (Word) y plantilla con el formato de carga (Excel).</p>
      @if($lote->documentos_generados_en)
        <div class="lt-ok"><i class="bi bi-check-circle"></i> Generados el {{ $lote->documentos_generados_en->format('d/m/Y H:i') }}</div>
        <div class="d-flex gap-2 flex-wrap" style="margin-top:12px">
          <a href="{{ route('impulso.lotes.descargar', [$lote, 'word']) }}" class="btn btn-secondary btn-sm"><i class="bi bi-file-earmark-word"></i> Notificación (.docx)</a>
          <a href="{{ route('impulso.lotes.descargar', [$lote, 'excel']) }}" class="btn btn-secondary btn-sm"><i class="bi bi-file-earmark-excel"></i> Plantilla (.xlsx)</a>
        </div>
      @else
        <form method="POST" action="{{ route('impulso.lotes.documentos', $lote) }}">@csrf
          <button class="btn btn-primary"><i class="bi bi-file-earmark-plus"></i> Generar notificación y plantilla</button>
        </form>
      @endif
    </div>

    {{-- 2. Firma digital --}}
    <div class="card cc-card cc-aparece" style="--i:6">
      <div class="card-title"><span class="lt-num">2</span> Firma digital de los representantes</div>
      <p class="card-sub">Autorización de los representantes ante COFIDE para usar su firma digital.</p>
      <div class="lt-firmas">
        @foreach(config('rct.impulso.representantes') as $i => $r)
          @php $f = $firmas->get($r['nombre']); @endphp
          <div class="lt-firma {{ $f ? 'ok' : '' }}">
            <i class="bi {{ $f ? 'bi-patch-check-fill' : 'bi-pen' }}"></i>
            <div class="lt-firma-body"><strong>{{ $r['nombre'] }}</strong><span>{{ $r['cargo'] }}</span>
              @if($f)<span class="text-verde">Autorizó el {{ \Illuminate\Support\Carbon::parse($f['autorizado_en'])->format('d/m/Y H:i') }}</span>@endif</div>
            @unless($f)
              <form method="POST" action="{{ route('impulso.lotes.firma', $lote) }}">@csrf
                <input type="hidden" name="representante" value="{{ $i }}">
                <button class="btn btn-secondary btn-sm" @disabled(!$lote->documentos_generados_en)>Registrar autorización</button>
              </form>
            @endunless
          </div>
        @endforeach
      </div>
    </div>

    {{-- 3. Confirmación de COFIDE --}}
    <div class="card cc-card cc-aparece" style="--i:7">
      <div class="card-title"><span class="lt-num">3</span> Confirmación de COFIDE</div>
      <p class="card-sub">N° de registros y saldo insoluto total que confirma la plataforma de COFIDE.</p>
      @if($lote->registros_cofide !== null)
        <div class="lt-comparacion {{ $lote->tieneDiferencia() ? 'error' : 'ok' }}">
          <div><span>Registros</span><strong>{{ $lote->registros_cofide }}</strong><em>RCT: {{ $lote->registros_rct }}</em></div>
          <div><span>Saldo insoluto</span><strong>{{ Estilo::solesPe($lote->saldo_cofide) }}</strong><em>RCT: {{ Estilo::solesPe($lote->saldo_rct) }}</em></div>
          <div class="lt-comparacion-res">
            @if($lote->tieneDiferencia())
              <i class="bi bi-x-circle-fill"></i> Diferencia: {{ (int) $lote->registros_cofide - (int) $lote->registros_rct }} registros · {{ Estilo::solesPe($lote->diferenciaSaldo()) }}
            @else
              <i class="bi bi-check-circle-fill"></i> Coincide con RCT · cargado el {{ $lote->fecha_carga?->format('d/m/Y') }}{{ $lote->constancia_cofide ? ' · Constancia '.$lote->constancia_cofide : '' }}
            @endif
          </div>
        </div>
      @endif
      @if(!$lote->esHonrado() && (in_array($lote->estado, ['Firmado', 'Observado'])))
        <form method="POST" action="{{ route('impulso.lotes.confirmar', $lote) }}" class="lt-form">@csrf
          <div class="field"><label for="registros_cofide">N° de registros</label><input id="registros_cofide" name="registros_cofide" type="number" min="0" required value="{{ old('registros_cofide') }}"></div>
          <div class="field"><label for="saldo_cofide">Saldo insoluto total (S/)</label><input id="saldo_cofide" name="saldo_cofide" required inputmode="decimal" placeholder="Ej. 1 234 567,89" value="{{ old('saldo_cofide') }}"></div>
          <div class="field"><label for="fecha_carga">Fecha de carga</label><input id="fecha_carga" name="fecha_carga" type="date" required value="{{ old('fecha_carga', now()->toDateString()) }}" max="{{ now()->toDateString() }}"></div>
          <div class="field"><label for="constancia">N° de constancia (opcional)</label><input id="constancia" name="constancia" value="{{ old('constancia') }}"></div>
          <button class="btn btn-primary"><i class="bi bi-check2-square"></i> Registrar confirmación</button>
        </form>
      @elseif($lote->registros_cofide === null)
        <div class="text-gris fs-13"><i class="bi bi-lock"></i> Disponible cuando el lote esté firmado.</div>
      @endif
    </div>
  </div>

  @includeIf('impulso._honramiento')

  <div class="card cc-card card-flush cc-aparece" style="--i:9;margin-top:18px">
    <div class="card-head"><div class="card-title"><i class="bi bi-list-ul"></i> Créditos del lote</div><span class="cell-sub">{{ $expedientes->count() }} créditos</span></div>
    <div class="table-wrap">
      <table data-rct-table data-page-size="15" data-search-placeholder="Buscar cliente, documento o crédito">
        <thead><tr><th>Cliente</th><th>Documento</th><th>N° crédito</th><th>Tienda</th><th class="num">Días (core)</th>
          <th class="num">Saldo insoluto</th><th class="num">Interés</th><th class="num">Cobertura</th><th class="num">{{ $lote->esHonrado() ? 'Monto honrado' : 'A honrar' }}</th><th>Honramiento</th></tr></thead>
        <tbody>
          @foreach($expedientes as $e)
            <tr>
              <td><a href="{{ route('gobierno.show', $e) }}" class="cell-main">{{ $e->cliente }}</a><div class="cell-sub">{{ $e->tipo_persona === 'juridica' ? 'Jurídica' : 'Natural' }} · Documentos: {{ $e->estado }}</div></td>
              <td class="nowrap">{{ $e->dni }}</td><td class="nowrap">{{ $e->credito_sicmact }}</td>
              <td>{{ $e->tienda->nombre ?? '—' }}<div class="cell-sub">{{ $e->zona->nombre ?? '' }}</div></td>
              <td class="num">{{ $core[$e->credito_sicmact]->dias_atraso ?? '—' }}</td>
              <td class="num">{{ Estilo::solesPe($e->saldo_insoluto) }}</td>
              <td class="num">{{ Estilo::solesPe($e->interes_devengado) }}</td>
              <td class="num">{{ $e->factor_cobertura !== null ? number_format($e->factor_cobertura * 100, 2, ',', '').'%' : '—' }}</td>
              <td class="num fw-600">{{ Estilo::solesPe($e->monto_honrado) }}</td>
              <td><span class="badge {{ $badge[$e->estado_honramiento] ?? 'badge-gray' }}">{{ $e->estado_honramiento ?? '—' }}</span>
                @if($e->fecha_honramiento)<div class="cell-sub">{{ $e->fecha_honramiento->format('d/m/Y') }}</div>@endif</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
    <div style="height:12px"></div>
  </div>
</div>
@endsection
