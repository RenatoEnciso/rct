<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Trazabilidad FUD-{{ str_pad($vigente->id, 5, '0', STR_PAD_LEFT) }}</title>
@use('App\Support\Estilo')
@php
  $num = fn($v) => rtrim(rtrim((string) $v, '0'), '.');
  $color = fn($e) => match(mb_strtolower((string) $e)) {
    'aprobado','conforme','completo','ok' => '#1f7a4d',
    'observado','warn' => '#a15c07',
    'rechazado','danger' => '#b42318',
    default => '#1f4e8c',
  };
@endphp
<style>
  @page { margin: 26mm 14mm 18mm 14mm; }
  * { font-family: 'DejaVu Sans', sans-serif; }
  body { font-size: 8.6pt; color: #1c2430; margin: 0; }
  header { position: fixed; top: -20mm; left: 0; right: 0; height: 16mm; border-bottom: 1.5pt solid #a3182c; }
  footer { position: fixed; bottom: -12mm; left: 0; right: 0; height: 8mm; font-size: 7pt; color: #8a94a3; border-top: .5pt solid #e3e6ea; padding-top: 2mm; }
  .marca { display: inline-block; width: 9mm; height: 9mm; background: #a3182c; color: #fff; font-weight: bold; text-align: center; line-height: 9mm; font-size: 9pt; border-radius: 1.5mm; vertical-align: middle; }
  .titulo-doc { display: inline-block; vertical-align: middle; margin-left: 3mm; }
  .titulo-doc b { font-size: 10pt; letter-spacing: .5pt; }
  .titulo-doc span { display: block; font-size: 7.5pt; color: #5f6b7a; }
  .codigo { float: right; text-align: right; font-size: 7.5pt; color: #5f6b7a; margin-top: 1.5mm; }
  .codigo b { display: block; font-size: 11pt; color: #1c2430; }
  h1 { font-size: 13pt; margin: 0 0 1mm; }
  .sub { color: #5f6b7a; margin: 0 0 4mm; }
  h2 { font-size: 9.5pt; margin: 6mm 0 2mm; padding-bottom: 1mm; border-bottom: .6pt solid #cfd4da; text-transform: uppercase; letter-spacing: .4pt; color: #3d4756; }
  table { width: 100%; border-collapse: collapse; }
  h2 { page-break-after: avoid; }
  thead { display: table-header-group; }
  tr { page-break-inside: avoid; }
  .firma { page-break-inside: avoid; }
  th { background: #f4f5f7; color: #5f6b7a; font-size: 7.4pt; font-weight: bold; text-align: left; padding: 1.6mm 2mm; border-bottom: .6pt solid #cfd4da; }
  td { padding: 1.6mm 2mm; border-bottom: .4pt solid #e3e6ea; vertical-align: top; }
  .kv td { border: 0; padding: 1mm 2mm 1mm 0; }
  .kv .k { color: #5f6b7a; width: 23%; }
  .kv .v { font-weight: bold; width: 27%; }
  .estado { border: .8pt solid #cfd4da; background: #f8f9fa; padding: 2.5mm 3mm; margin-bottom: 2mm; }
  .estado b { font-size: 9.5pt; }
  .pill { display: inline-block; padding: .4mm 2mm; border-radius: 2mm; font-size: 7.2pt; font-weight: bold; color: #fff; }
  .muted { color: #5f6b7a; }
  .small { font-size: 7.4pt; }
  .dot { display: inline-block; width: 2.2mm; height: 2.2mm; border-radius: 1.1mm; margin-right: 1.5mm; }
  .firma { margin-top: 12mm; width: 100%; }
  .firma td { border: 0; text-align: center; width: 50%; padding-top: 12mm; }
  .firma .linea { border-top: .6pt solid #5f6b7a; margin: 0 10mm; padding-top: 1.5mm; }
</style>
</head>
<body>
<header>
  <span class="marca">CT</span>
  <span class="titulo-doc"><b>CAJA TRUJILLO</b><span>Departamento de Recuperaciones · Sistema RCT</span></span>
  <div class="codigo">Reporte de trazabilidad<b>FUD-{{ str_pad($vigente->id, 5, '0', STR_PAD_LEFT) }}</b></div>
</header>
<footer>
  Generado el {{ $generado->format('d/m/Y H:i') }} por {{ $usuario->name }} ({{ $usuario->rol->nombre }}) · Documento de uso interno · Caja Municipal de Ahorro y Crédito de Trujillo
</footer>

<h1>{{ $cliente->nombre_cliente }}</h1>
<p class="sub">Operación {{ $cliente->operacion }} · Cuenta {{ $cliente->cuenta }} · {{ $vigente->tipo_negociacion_desc }}</p>

<div class="estado">
  <b>Estado actual:</b> {{ $resumen['etapa'] }}<br>
  <span class="small muted">Propuesta: {{ $resumen['estado_fud'] }}@if($resumen['estado_sustento']) · Sustento: {{ $resumen['estado_sustento'] === 'Aprobado' ? 'Conforme' : $resumen['estado_sustento'] }}@endif</span>
</div>

<h2>Datos de la operación</h2>
<table class="kv">
  <tr><td class="k">Documento</td><td class="v">{{ $cliente->tipo_documento }} {{ $cliente->nro_documento }}</td><td class="k">Tienda / zona</td><td class="v">{{ $cliente->tienda->nombre ?? '—' }} / {{ $cliente->zona->nombre ?? '—' }}</td></tr>
  <tr><td class="k">Saldo capital</td><td class="v">{{ $cliente->saldo_formateado }}</td><td class="k">Días de atraso</td><td class="v">{{ $cliente->dias_atraso }}</td></tr>
  <tr><td class="k">Calificación RCC</td><td class="v">{{ $cliente->calificacion_rcc }}</td><td class="k">Situación de cartera</td><td class="v">{{ $cliente->estado_cartera }}</td></tr>
  <tr><td class="k">Ejecutivo</td><td class="v">{{ $vigente->enviador->name }}</td><td class="k">Aprobador</td><td class="v">{{ $vigente->aprobadoPor->name ?? $vigente->aprobador->name ?? '—' }} (nivel {{ $vigente->nivel_autonomia_req }})</td></tr>
  @if($detalle && $vigente->tipo_negociacion === 'cuotas')
    <tr><td class="k">Cuotas</td><td class="v">{{ $detalle->cuota_desde }} a {{ $detalle->cuota_hasta }}</td><td class="k">Desc. intereses / moratorio</td><td class="v">{{ $num($detalle->desc_interes) }}% / {{ $num($detalle->desc_moratorio) }}%</td></tr>
  @elseif($detalle && $vigente->tipo_negociacion === 'cancelacion')
    <tr><td class="k">Monto a pagar</td><td class="v">{{ Estilo::soles($detalle->monto_a_pagar) }}</td><td class="k">Desc. intereses / capital</td><td class="v">{{ $num($detalle->desc_interes) }}% / {{ $num($detalle->desc_capital) }}%</td></tr>
  @elseif($detalle)
    <tr><td class="k">Beneficio sobre SK</td><td class="v">{{ $num($detalle->beneficio_pct_sk) }}%</td><td class="k">Etapa procesal</td><td class="v">{{ $detalle->etapa_procesal ?? '—' }}</td></tr>
  @endif
</table>

<h2>Línea de tiempo</h2>
<table>
  <thead><tr><th style="width:17%">Fecha y hora</th><th style="width:27%">Evento</th><th style="width:24%">Responsable</th><th>Detalle</th></tr></thead>
  <tbody>
    @foreach($eventos as $e)
      <tr>
        <td>{{ \Carbon\Carbon::parse($e['fecha'])->format('d/m/Y H:i') }}</td>
        <td><span class="dot" style="background:{{ $color($e['estado']) }}"></span><b>{{ $e['titulo'] }}</b></td>
        <td>{{ $e['actor'] }}</td>
        <td class="muted">{{ $e['detalle'] }}</td>
      </tr>
    @endforeach
  </tbody>
</table>

<h2>Versiones de la propuesta</h2>
<table>
  <thead><tr><th style="width:16%">Versión</th><th style="width:22%">Enviada</th><th style="width:26%">Decisión</th><th>Comentario del aprobador</th></tr></thead>
  <tbody>
    @foreach($versiones as $i => $v)
      @php $dec = $v->versiones()->exists() ? 'Observado' : ($v->fecha_decision ? $v->estado_fud : null); @endphp
      <tr>
        <td><b>{{ $i === 0 ? 'Envío inicial' : 'Corrección '.$i }}</b></td>
        <td>{{ $v->created_at->format('d/m/Y H:i') }}<br><span class="muted small">{{ $v->enviador->name }}</span></td>
        <td>
          @if($dec)
            <span class="pill" style="background:{{ $color($dec) }}">{{ $dec }}</span><br>
            <span class="muted small">{{ ($v->decidido_en ?? $v->fecha_decision)->format('d/m/Y H:i') }} · {{ $v->aprobadoPor->name ?? '—' }}</span>
          @else
            <span class="pill" style="background:#1f4e8c">En revisión</span>
          @endif
        </td>
        <td class="muted">{{ $v->comentario_aprobador ?? '—' }}</td>
      </tr>
    @endforeach
  </tbody>
</table>

<h2>Archivos adjuntos a la propuesta</h2>
<table>
  <thead><tr><th>Archivo</th><th style="width:20%">Tipo</th><th style="width:16%">Versión</th><th style="width:24%">Cargado</th></tr></thead>
  <tbody>
    @forelse($adjuntos as $a)
      <tr><td>{{ $a['nombre'] }}</td><td>{{ $a['tipo'] }}</td><td>{{ $a['version'] }}</td><td>{{ $a['fecha']?->format('d/m/Y H:i') }} · {{ $a['por'] }}</td></tr>
    @empty
      <tr><td colspan="4" class="muted">Sin archivos adjuntos.</td></tr>
    @endforelse
  </tbody>
</table>

<h2>Documentos de sustento</h2>
@if($sustento?->revisador)
  <p class="small muted" style="margin:0 0 2mm">Revisión: {{ $sustento->revisador->name }} ({{ $sustento->revisador->rol->nombre ?? '' }}) el {{ $sustento->fecha_revision?->format('d/m/Y H:i') }}.@if($sustento->comentario_revision) Comentario: «{{ $sustento->comentario_revision }}»@endif</p>
@endif
<table>
  <thead><tr><th style="width:17%">Documento</th><th style="width:22%">Archivo</th><th style="width:13%">Cargado</th><th style="width:23%">Validación automática</th><th>Resultado de la revisión</th></tr></thead>
  <tbody>
    @forelse($documentos as $d)
      <tr>
        <td><b>{{ $d['tipo'] }}</b>@if(!$d['vigente'])<br><span class="small muted">(versión anterior)</span>@endif</td>
        <td>{{ $d['nombre'] }}<br><span class="small muted">{{ $d['tamano'] }}</span></td>
        <td>{{ $d['cargado_en']?->format('d/m/Y H:i') ?? '—' }}</td>
        <td>
          <span class="pill" style="background:{{ $color($d['auto_estado']) }}">{{ $d['automatica'] }}</span>
          @foreach($d['hallazgos'] as $h)<br><span class="small muted">{{ $h }}</span>@endforeach
        </td>
        <td>{{ $d['resultado'] }}@if($d['comentario'])<br><span class="small muted">«{{ $d['comentario'] }}»</span>@endif</td>
      </tr>
    @empty
      <tr><td colspan="5" class="muted">Sin documentos de sustento cargados.</td></tr>
    @endforelse
  </tbody>
</table>

<table class="firma">
  <tr>
    <td><div class="linea">{{ $vigente->enviador->name }}<br><span class="small muted">Ejecutivo responsable</span></div></td>
    <td><div class="linea">{{ $vigente->aprobadoPor->name ?? $vigente->aprobador->name ?? '—' }}<br><span class="small muted">Nivel de autonomía</span></div></td>
  </tr>
</table>
</body>
</html>
