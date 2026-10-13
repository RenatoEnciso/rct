@extends('layouts.app')
@section('title', 'Candidatos a honramiento')

@use('App\Support\Estilo')

@section('content')
@include('gobierno._modulo')
<div class="cc">
  <div class="page-head cc-head">
    <div>
      <div class="page-eyebrow">Impulso MyPerú · Etapa 1 · Solicitud</div>
      <h2>Candidatos a honramiento</h2>
      <p>Créditos del programa que llegaron a {{ $dias }} o más días de atraso según el core y aún no están en un lote.</p>
    </div>
  </div>

  <div class="cc-kpis pr-kpis">
    <div class="cc-kpi cc-aparece" style="--i:0">
      <div class="cc-kpi-ico rojo"><i class="bi bi-hourglass-split"></i></div>
      <div class="cc-kpi-label">Candidatos a solicitar</div>
      <div class="cc-kpi-valor" data-contar="{{ $candidatos->count() }}" data-formato="num">{{ $candidatos->count() }}</div>
      <div class="cc-kpi-sub">Con {{ $dias }} o más días de atraso</div>
    </div>
    <div class="cc-kpi cc-aparece" style="--i:1">
      <div class="cc-kpi-ico azul"><i class="bi bi-cash-stack"></i></div>
      <div class="cc-kpi-label">Saldo insoluto de los candidatos</div>
      <div class="cc-kpi-valor">{{ Estilo::solesPe(\App\Services\Impulso\Calculos::sumar($candidatos->pluck('saldo_capital'))) }}</div>
      <div class="cc-kpi-sub">Según el core</div>
    </div>
    <div class="cc-kpi cc-aparece" style="--i:2">
      <div class="cc-kpi-ico ambar"><i class="bi bi-calendar-event"></i></div>
      <div class="cc-kpi-label">Llegan a {{ $dias }} días en las próximas 2 semanas</div>
      <div class="cc-kpi-valor" data-contar="{{ $proximos->count() }}" data-formato="num">{{ $proximos->count() }}</div>
      <div class="cc-kpi-sub">Se sumarán como candidatos</div>
    </div>
    <div class="cc-kpi cc-aparece" style="--i:3">
      <div class="cc-kpi-ico verde"><i class="bi bi-building"></i></div>
      <div class="cc-kpi-label">Personas jurídicas</div>
      <div class="cc-kpi-valor">{{ $candidatos->where('tipo_persona', 'juridica')->count() }}</div>
      <div class="cc-kpi-sub">Requieren 7 documentos del Anexo 13</div>
    </div>
  </div>

  <form method="POST" action="{{ route('impulso.lotes.armar') }}" class="card cc-card card-flush cc-aparece" style="--i:4;margin-bottom:18px" id="form-lote">
    @csrf
    <div class="card-head">
      <div class="card-title"><i class="bi bi-list-check"></i> Créditos candidatos</div>
      <div class="d-flex gap-2 align-items-center">
        <span class="cell-sub" id="sel-resumen">0 seleccionados</span>
        <button class="btn btn-primary" id="btn-armar" disabled><i class="bi bi-collection"></i> Armar lote con los seleccionados</button>
      </div>
    </div>
    <div class="table-wrap">
      <table>
        <thead><tr>
          <th style="width:36px"><input type="checkbox" id="sel-todos" aria-label="Seleccionar todos" @disabled($candidatos->isEmpty())></th>
          <th>Cliente</th><th>Documento</th><th>N° crédito</th><th>Tienda</th><th class="num">Días de atraso</th>
          <th class="num">Saldo capital</th><th class="num">Interés devengado</th><th class="num">Cobertura</th>
        </tr></thead>
        <tbody>
          @forelse($candidatos as $c)
            <tr>
              <td><input type="checkbox" name="creditos[]" value="{{ $c->credito_sicmact }}" data-saldo="{{ $c->saldo_capital }}" class="sel-credito" aria-label="Seleccionar {{ $c->cliente }}"></td>
              <td><div class="cell-main">{{ $c->cliente }}</div><div class="cell-sub">{{ $c->tipo_persona === 'juridica' ? 'Persona jurídica' : 'Persona natural' }}</div></td>
              <td class="nowrap">{{ $c->tipo_documento }} {{ $c->nro_documento }}</td>
              <td class="nowrap">{{ $c->credito_sicmact }}</td>
              <td>{{ $c->tienda->nombre ?? '—' }}<div class="cell-sub">{{ $c->zona->nombre ?? '' }}</div></td>
              <td class="num"><span class="badge {{ $c->dias_atraso >= $dias + 15 ? 'badge-danger' : 'badge-pending' }}">{{ $c->dias_atraso }} días</span></td>
              <td class="num">{{ Estilo::solesPe($c->saldo_capital) }}</td>
              <td class="num">{{ Estilo::solesPe($c->interes_devengado) }}</td>
              <td class="num">{{ number_format($c->factor_cobertura * 100, 2, ',', '') }}%</td>
            </tr>
          @empty
            <tr><td colspan="9"><div class="empty-state"><i class="bi bi-check2-circle"></i><strong>Sin candidatos pendientes</strong><span>Todos los créditos con {{ $dias }} días ya están en un lote.</span></div></td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
    <div style="height:8px"></div>
  </form>

  @if($proximos->isNotEmpty())
    <div class="card cc-card card-flush cc-aparece" style="--i:5">
      <div class="card-head"><div class="card-title"><i class="bi bi-calendar-week"></i> Próximos a {{ $dias }} días</div><span class="cell-sub">Aún no son candidatos</span></div>
      <div class="table-wrap">
        <table>
          <thead><tr><th>Cliente</th><th>N° crédito</th><th>Tienda</th><th class="num">Días de atraso</th><th class="num">Llega a {{ $dias }} días</th><th class="num">Saldo capital</th></tr></thead>
          <tbody>
            @foreach($proximos as $c)
              <tr>
                <td class="cell-main">{{ $c->cliente }}</td><td>{{ $c->credito_sicmact }}</td><td>{{ $c->tienda->nombre ?? '—' }}</td>
                <td class="num">{{ $c->dias_atraso }}</td>
                <td class="num">{{ now()->addDays($dias - $c->dias_atraso)->format('d/m/Y') }}</td>
                <td class="num">{{ Estilo::solesPe($c->saldo_capital) }}</td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
      <div style="height:8px"></div>
    </div>
  @endif
</div>
@endsection

@push('scripts')
<script src="{{ asset('assets/js/rct-analitica.js') }}?v={{ filemtime(public_path('assets/js/rct-analitica.js')) }}"></script>
<script>
RCTAnalitica.contadores();
(function () {
  var checks = [].slice.call(document.querySelectorAll('.sel-credito')), todos = document.getElementById('sel-todos');
  function actualizar() {
    var sel = checks.filter(function (c) { return c.checked; });
    var saldo = sel.reduce(function (s, c) { return s + parseFloat(c.dataset.saldo); }, 0);
    document.getElementById('sel-resumen').textContent = sel.length + ' seleccionados · saldo insoluto ' + RCTAnalitica.formato.pe(saldo);
    document.getElementById('btn-armar').disabled = !sel.length;
    if (todos) todos.checked = sel.length && sel.length === checks.length;
  }
  checks.forEach(function (c) { c.addEventListener('change', actualizar); });
  todos && todos.addEventListener('change', function () { checks.forEach(function (c) { c.checked = todos.checked; }); actualizar(); });
})();
</script>
@endpush
