@props(['estadoFud', 'estadoSustento' => null, 'etiquetas' => true, 'leyenda' => false])
{{-- Friso del expediente: cinco etapas fijas. Completa = tinta, actual = rojo,
     observada = ámbar, rechazada = peligro, pendiente = vacía. Solo presentación. --}}
@php
  $sus = $estadoSustento === 'Aprobado' ? 'Conforme' : $estadoSustento;
  $etapas = [
    ['Propuesta enviada', 'hecho'],
    ['Decisión', match ($estadoFud) {
        'Aprobado' => 'hecho', 'Observado', 'Corregido' => 'obs', 'Rechazado' => 'rech', default => 'actual' }],
    ['Sustento cargado', $estadoFud !== 'Aprobado' ? '' : match ($sus) {
        null, 'Pendiente' => 'actual', 'Observado' => 'obs', 'Rechazado' => 'rech', default => 'hecho' }],
    ['Revisión del sustento', $estadoFud !== 'Aprobado' ? '' : match ($sus) {
        'En revisión' => 'actual', 'Conforme' => 'hecho', 'Observado' => 'obs', default => '' }],
    ['Expediente cerrado', $sus === 'Conforme' ? 'hecho' : ''],
  ];
  $actual = collect($etapas)->first(fn ($e) => in_array($e[1], ['actual', 'obs', 'rech']))
         ?? ($sus === 'Conforme' ? ['Expediente cerrado', 'hecho'] : $etapas[0]);
  $textos = ['hecho' => 'completa', 'actual' => 'en curso', 'obs' => 'observada', 'rech' => 'rechazada', '' => 'pendiente'];
@endphp
<span {{ $attributes->merge(['class' => 'friso-exp']) }}>
  @if($leyenda)
    <span class="friso friso-rotulado" role="list" aria-label="Etapas del expediente">
      @foreach($etapas as [$nombre, $estado])
        <span class="friso-paso {{ $estado }}" role="listitem"><span class="friso-m {{ $estado === 'hecho' ? 'paso' : $estado }}"></span>{{ $nombre }}<span class="sr-only">: {{ $textos[$estado] }}</span></span>
      @endforeach
    </span>
  @else
    <span class="friso" role="img" aria-label="Etapas: {{ collect($etapas)->map(fn ($e) => $e[0].' '.$textos[$e[1]])->implode(', ') }}">
      @foreach($etapas as [$nombre, $estado])
        <span class="friso-m {{ $estado === 'hecho' ? 'paso' : $estado }}" title="{{ $nombre }}: {{ $textos[$estado] }}"></span>
      @endforeach
    </span>
  @endif
  @if($etiquetas)<span class="friso-etiqueta">{{ $actual[0] }}</span>@endif
</span>
