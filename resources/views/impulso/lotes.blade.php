@extends('layouts.app')
@section('title', 'Lotes de honramiento')

@use('App\Support\Estilo')

@section('content')
@include('gobierno._modulo')
@php
  $badge = ['En preparación' => 'badge-gray', 'Pendiente de firma' => 'badge-pending', 'Firmado' => 'badge-info',
            'Cargado en COFIDE' => 'badge-info', 'Observado' => 'badge-danger', 'Honrado parcial' => 'badge-ok', 'Honrado' => 'badge-ok'];
@endphp
<div class="cc">
  <div class="page-head cc-head">
    <div>
      <h2>Lotes de honramiento</h2>
      <p>Cada carga en la plataforma de COFIDE es un lote: corte, documentos, firma digital, confirmación y honramiento.</p>
    </div>
    <div class="page-head-actions">
      <a href="{{ route('impulso.candidatos') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Armar lote <span class="badge badge-lg" style="background:rgba(255,255,255,.2);color:#fff;margin-left:4px">{{ $candidatos }} candidatos</span></a>
    </div>
  </div>

  <div class="card cc-card card-flush">
    <div class="table-wrap">
      <table>
        <thead><tr>
          <th>Lote</th><th>Fecha de corte</th><th class="num">Registros</th><th class="num">Saldo insoluto (RCT)</th>
          <th>Confirmación COFIDE</th><th>Firma digital</th><th>Honramiento</th><th>Estado</th><th></th>
        </tr></thead>
        <tbody>
          @forelse($lotes as $l)
            <tr>
              <td><a href="{{ route('impulso.lotes.show', $l) }}" class="cell-main">{{ $l->codigo }}</a><div class="cell-sub">{{ $l->programa }}</div></td>
              <td>{{ $l->fecha_corte?->format('d/m/Y') ?? '—' }}</td>
              <td class="num">{{ $l->registros_rct ?: $l->expedientes_count }}</td>
              <td class="num">{{ Estilo::solesPe($l->saldo_rct) }}</td>
              <td>
                @if($l->registros_cofide === null) <span class="text-gris">Pendiente</span>
                @elseif($l->tieneDiferencia()) <span class="text-rojo fw-600"><i class="bi bi-x-circle"></i> Con diferencia</span>
                @else <span class="text-verde fw-600"><i class="bi bi-check-circle"></i> Coincide</span>
                  <div class="cell-sub">{{ $l->fecha_carga?->format('d/m/Y') }}</div>
                @endif
              </td>
              <td>
                @if($l->firmasCompletas()) <span class="text-verde"><i class="bi bi-pen"></i> Firmado</span>
                @elseif($l->documentos_generados_en) <span class="text-ambar">{{ count($l->firmas ?? []) }} de {{ count(config('rct.impulso.representantes')) }} autorizaciones</span>
                @else <span class="text-gris">—</span> @endif
              </td>
              <td>{{ $l->fecha_honramiento?->format('d/m/Y') ?? '—' }}</td>
              <td><span class="badge {{ $badge[$l->estado] ?? 'badge-gray' }}">{{ $l->estado }}</span></td>
              <td class="num"><a href="{{ route('impulso.lotes.show', $l) }}" class="btn btn-ghost btn-sm">Ver <i class="bi bi-chevron-right"></i></a></td>
            </tr>
          @empty
            <tr><td colspan="9"><div class="empty-state"><i class="bi bi-collection"></i><strong>Sin lotes</strong><span>Arme el primer lote desde los candidatos a honramiento.</span></div></td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection
