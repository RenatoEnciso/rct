@extends('layouts.app')
@section('title', 'Exclusiones')

@section('content')

<div class="page-head">
  <div class="page-head-text">
    <h2>🚫 Exclusiones y Programas de Gobierno</h2>
    <p>Créditos que no aplican a la campaña y gestión de expedientes de programas del gobierno.</p>
  </div>
</div>

<div class="grid2">
  {{-- Lista de exclusiones --}}
  <div class="card">
    <div class="card-title">Créditos excluidos de la campaña</div>
    @foreach([
      'Pignoraticios'             => 'Créditos con garantía prendaria de depósitos.',
      'Leasing'                   => 'Contratos de arrendamiento financiero.',
      'Garantía de depósito'      => 'Créditos respaldados 100% en efectivo.',
      'Cartera Multigas'          => 'Cartera adquirida de Multigas.',
      'Créditos administrativos'  => 'Ex trabajadores de Caja Trujillo (requieren revisión de Jefatura).',
      'Carteras adquiridas'       => 'Portfolios comprados a terceros.',
      'Fraude confirmado'         => 'Excepto los no reportados o rechazados por Cía. de Seguros.',
      'En proceso de venta'       => 'Créditos ya en proceso de transferencia.',
      'Programas de Gobierno'     => 'Excepto los rechazados para cobertura de garantía o con pérdida de eficacia.',
    ] as $titulo => $desc)
      <div style="border:1px solid #f0d0d4;background:#fff9f9;border-left:4px solid var(--rojo);
                  border-radius:8px;padding:12px 14px;font-size:13px;color:#555;margin-bottom:8px">
        <strong style="color:var(--rojo)">{{ $titulo }}</strong> — {{ $desc }}
      </div>
    @endforeach
  </div>

  {{-- Formulario Programa de Gobierno --}}
  <div class="card">
    <div class="card-title">Expedientes de Programas de Gobierno</div>
    <div class="notice">
      Solo aplican créditos con <strong>garantía rechazada</strong> o
      <strong>pérdida de eficacia</strong>. El Dpto. de Recuperaciones remite el listado.
    </div>

    <form method="POST" action="{{ route('cartera.gobierno') }}" enctype="multipart/form-data">
      @csrf
      <div class="grid2" style="margin-bottom:16px;margin-top:16px">
        <div class="field">
          <label>Programa</label>
          <select name="programa" required>
            @foreach(['IMPULSA PERÚ','FAE-MYPE','Reactiva Perú','Otro'] as $p)
              <option>{{ $p }}</option>
            @endforeach
          </select>
        </div>
        <div class="field">
          <label>Motivo de habilitación</label>
          <select name="motivo_habilitacion" required>
            <option>Garantía rechazada por Cía. Seguros</option>
            <option>Pérdida de eficacia de la garantía</option>
          </select>
        </div>
        <div class="field">
          <label>Cuenta u operación del crédito</label>
          <input name="cliente_ref" value="{{ old('cliente_ref') }}" placeholder="Ej: 200012345 o 100045678" required>
        </div>
        <div class="field">
          <label>Responsable</label>
          <input name="responsable" placeholder="Nombre del responsable" required>
        </div>
        <div class="field">
          <label>Confirmación Dpto. Recuperaciones</label>
          <select name="confirmacion_dpto">
            <option value="1">Sí – listado recibido</option>
            <option value="0">No – pendiente de listado</option>
          </select>
        </div>
      </div>

      <div class="card-title" style="margin-top:4px">Documentos obligatorios (10)</div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
        @php
          $docsGob = [
            'DNI / Reporte tributario con Ficha RUC',
            'Hoja Resumen',
            'Formulario Conocimiento del Cliente',
            'DJ del Programa – Anexo 02',
            'DJ Patrimonial',
            'Cargo del Contrato de Crédito',
            'Cronograma de Pagos',
            'Pagaré',
            'Clasificación Regulatoria (RCC, EXPERIAN)',
            'Evidencia de Ingresos',
          ];
        @endphp
        @foreach($docsGob as $i => $doc)
          <div style="border:1px solid var(--borde);border-left:4px solid var(--rojo);border-radius:8px;padding:12px">
            <div style="font-weight:600;font-size:12px;margin-bottom:6px">{{ $doc }}</div>
            <input type="file" name="documentos[]" style="width:100%;font-size:12px" required>
          </div>
        @endforeach
      </div>

      <div class="actions" style="margin-top:20px">
        <button type="submit" class="btn btn-primary">Registrar expediente</button>
      </div>
    </form>
  </div>
</div>

{{-- Expedientes registrados --}}
<div class="card" style="margin-top:20px">
  <div class="card-title">Expedientes de programas de gobierno registrados</div>
  <div style="overflow-x:auto">
    <table>
      <thead>
        <tr><th>Fecha</th><th>Cliente</th><th>Tienda</th><th>Programa</th><th>Motivo</th><th style="min-width:160px">Avance documentario</th><th>Estado</th></tr>
      </thead>
      <tbody>
        @forelse($expedientes as $e)
          @php $pctE = round(min($e->documentos_count, 10) / 10 * 100); @endphp
          <tr>
            <td style="font-size:12px">{{ $e->fecha_registro?->format('d/m/Y') }}</td>
            <td><strong style="font-size:12px">{{ $e->cliente->nombre_cliente ?? '—' }}</strong><br>
                <span style="font-size:11px;color:#999">Op. {{ $e->cliente->operacion ?? '—' }}</span></td>
            <td style="font-size:12px">{{ $e->cliente->tienda->nombre ?? '—' }}</td>
            <td style="font-size:12px">{{ $e->programa }}</td>
            <td style="font-size:11px;color:#666">{{ $e->motivo_habilitacion }}</td>
            <td>
              <div style="display:flex;align-items:center;gap:8px">
                <div class="progress-bar" style="flex:1;margin:0"><div class="progress-fill" style="width:{{ $pctE }}%"></div></div>
                <span style="font-size:11px;font-weight:700">{{ min($e->documentos_count, 10) }}/10</span>
              </div>
            </td>
            <td><span class="badge {{ $e->estado === 'Completo' ? 'badge-ok' : 'badge-pending' }}">{{ $e->estado }}</span></td>
          </tr>
        @empty
          <tr><td colspan="7" style="text-align:center;color:#999;padding:24px">Aún no hay expedientes registrados.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
  <div style="margin-top:12px">{{ $expedientes->links() }}</div>
</div>

@endsection