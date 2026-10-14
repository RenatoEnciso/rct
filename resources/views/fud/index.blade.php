@extends('layouts.app')
@section('title', 'Mis propuestas')

@use('App\Support\Estilo')

@section('content')
<div class="page-head">
  <div>
    <h2>Mis propuestas</h2>
    <p>Propuestas de conciliación (FUD) enviadas al nivel de autonomía.</p>
  </div>
  <div class="page-head-actions">
    <a href="{{ route('fud.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Nueva propuesta</a>
  </div>
</div>

<div class="card card-flush">
  <form method="GET" class="grid4 filtros-patio">
    <div class="field span-2">
      <label for="q">Cliente u operación</label>
      <input id="q" type="search" name="q" value="{{ request('q') }}" placeholder="Buscar por nombre o número de operación">
    </div>
    <div class="field">
      <label for="estado">Estado</label>
      <select id="estado" name="estado" onchange="this.form.submit()">
        <option value="">Todos</option>
        @foreach(['En revisión','Aprobado','Observado','Rechazado'] as $e)
          <option value="{{ $e }}" @selected(request('estado') === $e)>{{ $e }}</option>
        @endforeach
      </select>
    </div>
    <div class="d-flex gap-2">
      <button class="btn btn-primary"><i class="bi bi-search"></i> Buscar</button>
      <a href="{{ route('fud.index') }}" class="btn btn-ghost">Limpiar</a>
    </div>
  </form>

  <div class="table-wrap">
    <table>
      <thead>
        <tr><th>Enviado</th><th>Cliente</th><th>Tipo de negociación</th><th class="num">Saldo capital</th><th>Aprobador</th><th>Estado</th><th></th></tr>
      </thead>
      <tbody>
        @forelse($propuestas as $p)
          @php $vig = $p->ultimaVersion(); @endphp
          <tr>
            <td class="nowrap">{{ $p->fecha_envio->format('d/m/Y') }}</td>
            <td><div class="cell-main">{{ $p->cliente->nombre_cliente }}</div><div class="cell-sub">Op. {{ $p->cliente->operacion }}</div></td>
            <td>{{ $p->tipo_negociacion_desc ?? '—' }}</td>
            <td class="num">{{ $p->cliente->saldo_formateado }}</td>
            <td>{{ $vig->aprobador->name ?? '—' }}</td>
            <td>
              <div class="estado-celda">
                <x-friso-expediente :estado-fud="$vig->estado_fud" :estado-sustento="$vig->sustentos->first()?->estado" :etiquetas="false" />
                <span class="badge {{ Estilo::badge($vig->estado_fud) }}">{{ $vig->estado_fud }}</span>
              </div>
              @if($vig->veces_observado > 0)<div class="cell-sub">Corrección {{ $vig->veces_observado }}</div>@endif
            </td>
            <td class="num nowrap">
              @if($vig->estaObservado())
                <a href="{{ route('fud.edit', $vig) }}" class="btn btn-warning btn-sm"><i class="bi bi-pencil-square"></i> Corregir</a>
              @endif
              <a href="{{ route('fud.show', $vig) }}" class="btn btn-secondary btn-sm">Ver detalle</a>
            </td>
          </tr>
        @empty
          <tr><td colspan="7" class="empty-state">
            <i class="bi bi-folder2-open"></i><strong>Sin propuestas</strong>
            <span>No hay propuestas que coincidan con la búsqueda.</span>
          </td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
  {{ $propuestas->withQueryString()->links() }}
</div>
@endsection
