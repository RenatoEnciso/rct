@extends('layouts.app')
@section('title', 'Devoluciones a COFIDE')

@use('App\Support\Estilo')

@section('content')
@include('gobierno._modulo')
@php $badge = ['Calculada' => 'badge-gray', 'Con diferencias' => 'badge-danger', 'Cuadrada' => 'badge-info', 'Devuelta' => 'badge-ok']; @endphp
<div class="cc">
  <div class="page-head cc-head">
    <div>
      <h2>Devoluciones a COFIDE</h2>
      <p>Cada mes se devuelven las reversiones agregadas del mes anterior. Se cruzan con la notificación TXT de COFIDE: la diferencia debe ser S/ 0,00.</p>
    </div>
    @if($pendienteAnterior)
      <div class="page-head-actions">
        <form method="POST" action="{{ route('impulso.devoluciones.generar') }}">@csrf
          <input type="hidden" name="periodo" value="{{ $pendienteAnterior->format('Y-m') }}">
          <button class="btn btn-primary"><i class="bi bi-calculator"></i> Calcular devolución de {{ $pendienteAnterior->translatedFormat('F') }}</button>
        </form>
      </div>
    @endif
  </div>

  <div class="dv-lista">
    <div class="dv-item en-curso">
      <div class="dv-item-head"><span class="dv-mes">{{ ucfirst($enCurso['periodo']->translatedFormat('F Y')) }}</span><span class="badge badge-pending">En curso</span></div>
      <div class="dv-item-monto">{{ Estilo::solesPe($enCurso['monto']) }}</div>
      <div class="dv-item-sub">Reversiones agregadas hasta hoy · {{ $enCurso['creditos'] }} créditos, {{ $enCurso['pagos'] }} pagos</div>
      <div class="dv-item-pie"><i class="bi bi-calendar-event"></i> Se calcula al inicio de {{ $enCurso['periodo']->copy()->addMonth()->translatedFormat('F') }}</div>
    </div>
    @foreach($devoluciones as $i => $d)
      <a href="{{ route('impulso.devoluciones.show', $d) }}" class="dv-item {{ $d->cerrada() ? 'cerrada' : ($d->estado === 'Con diferencias' ? 'error' : '') }}">
        <div class="dv-item-head"><span class="dv-mes">{{ $d->nombrePeriodo() }}</span><span class="badge {{ $badge[$d->estado] ?? 'badge-gray' }}">{{ $d->estado }}</span></div>
        <div class="dv-item-monto">{{ Estilo::solesPe($d->monto_calculado) }}</div>
        <div class="dv-item-sub">{{ $d->creditos }} créditos · {{ $d->pagos }} pagos</div>
        <div class="dv-item-dif {{ $d->txt_importado_en ? ($d->cuadra() ? 'ok' : 'error') : '' }}">
          @if(!$d->txt_importado_en)<i class="bi bi-hourglass"></i> TXT de COFIDE pendiente
          @elseif($d->cuadra())<i class="bi bi-check-circle-fill"></i> Diferencia: {{ Estilo::solesPe(0) }}
          @else<i class="bi bi-x-circle-fill"></i> Diferencia: {{ Estilo::solesPe($d->diferencia) }} · {{ $d->n_diferencias }} créditos
          @endif
        </div>
        <div class="dv-item-pie">
          @if($d->cerrada())<i class="bi bi-check2-all"></i> Devuelta el {{ $d->cerrado_en->format('d/m/Y') }}
          @else<i class="bi bi-alarm"></i> Fecha límite {{ $d->fecha_limite->format('d/m/Y') }}
            <strong class="{{ $d->diasParaVencer() <= 5 ? 'text-rojo' : '' }}">· {{ $d->diasParaVencer() >= 0 ? 'vence en '.$d->diasParaVencer().' días' : 'vencida' }}</strong>
          @endif
        </div>
      </a>
    @endforeach
  </div>
</div>
@endsection
