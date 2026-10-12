@extends('layouts.app')
@section('title', 'Detalle FUD')

@section('content')

{{-- CABECERA --}}
<div class="page-head">
  <div class="page-head-text">
    <h2>Detalle de Propuesta FUD</h2>
    <p>{{ $fud->cliente->nombre_cliente }} · Operación {{ $fud->cliente->operacion }}</p>
  </div>
  <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
    @if($fud->veces_observado > 0)
      <span class="badge badge-mostaza">
        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
        </svg>
        {{ $fud->veces_observado }}x observado
      </span>
    @endif
    <span class="badge {{ $fud->badgeClass() }}" style="font-size:13px;padding:6px 14px">
      {{ $fud->estadoVisible() }}
    </span>
  </div>
</div>

{{-- ALERTA: FUD OBSERVADO → botón corregir --}}
@if($fud->estaObservado() && $fud->enviado_por === auth()->id())
  <div class="notice-danger" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px">
    <div>
      <strong>⚠ Este FUD fue observado y requiere corrección.</strong><br>
      <span style="font-size:13px;margin-top:4px;display:block">
        <strong>Comentario del aprobador:</strong> {{ $fud->comentario_aprobador }}
      </span>
    </div>
    <a href="{{ route('fud.edit', $fud) }}" class="btn btn-danger">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/>
        <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
      </svg>
      Corregir y re-enviar
    </a>
  </div>
@endif

{{-- HILO DE VERSIONES --}}
@if($hilo->count() > 1)
  <div class="card" style="border-top-color: var(--mostaza)">
    <div class="card-title">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>
      </svg>
      Historial de versiones ({{ $hilo->count() }} envíos)
    </div>
    <div style="display:flex;flex-direction:column;gap:10px">
      @foreach($hilo as $version)
        <div style="display:flex;align-items:flex-start;gap:14px;padding:12px;
                    border-radius:9px;background:{{ $version->id === $fud->id ? 'var(--rojo-soft)' : '#fafafa' }};
                    border:1px solid {{ $version->id === $fud->id ? 'var(--rojo)' : 'var(--borde)' }}">
          {{-- Ícono línea de tiempo --}}
          <div style="width:32px;height:32px;border-radius:50%;flex-shrink:0;display:flex;
                      align-items:center;justify-content:center;font-weight:800;font-size:12px;
                      background:{{ $version->id === $fud->id ? 'var(--rojo)' : '#dee2e6' }};
                      color:{{ $version->id === $fud->id ? '#fff' : '#555' }}">
            v{{ $loop->iteration }}
          </div>
          <div style="flex:1;min-width:0">
            <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:6px">
              <span style="font-size:12px;font-weight:700;color:#333">
                {{ $version->id === $fud->id ? '⟵ Versión actual' : 'Versión ' . $loop->iteration }}
              </span>
              <div style="display:flex;gap:6px;align-items:center">
                <span class="badge {{ $version->badgeClass() }}">{{ $version->estadoVisible() }}</span>
                <span style="font-size:11px;color:#999">{{ $version->fecha_envio->format('d/m/Y') }}</span>
              </div>
            </div>
            <p style="font-size:12px;color:#666;margin:4px 0 0;
                      white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
              {{ Str::limit($version->motivo, 100) }}
            </p>
            @if($version->comentario_aprobador)
              <p style="font-size:11px;color:#c0392b;margin:4px 0 0">
                💬 {{ $version->comentario_aprobador }}
              </p>
            @endif
          </div>
        </div>
      @endforeach
    </div>
  </div>
@endif

{{-- DATOS PRINCIPALES --}}
<div class="grid2">
  <div class="card">
    <div class="card-title">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/>
      </svg>
      Datos del cliente
    </div>
    <div class="grid2" style="gap:12px;font-size:13px">
      @foreach([
        'Cliente'        => $fud->cliente->nombre_cliente,
        'Cuenta'         => $fud->cliente->cuenta,
        'Operación'      => $fud->cliente->operacion,
        'Saldo Capital'  => $fud->cliente->saldo_formateado,
        'Días de atraso' => $fud->cliente->dias_atraso . ' días',
        'Calificación'   => $fud->cliente->calificacion_rcc,
        'Tipo crédito'   => $fud->cliente->tipo_credito,
        'Estado cartera' => $fud->cliente->estado_cartera,
      ] as $label => $val)
        <div>
          <div class="detail-label">{{ $label }}</div>
          <div class="detail-value">{{ $val }}</div>
        </div>
      @endforeach
    </div>
  </div>

  <div class="card">
    <div class="card-title">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
        <polyline points="14 2 14 8 20 8"/>
      </svg>
      Datos de la propuesta
    </div>
    <div class="grid2" style="gap:12px;font-size:13px">
      @foreach([
        'Tipo negociación' => $fud->tipo_negociacion_desc,
        'Enviado por'      => $fud->enviador->name,
        'Aprobador req.'   => $fud->aprobador->name ?? '—',
        'Fecha envío'      => $fud->fecha_envio->format('d/m/Y'),
        'Nivel autonomía'  => 'Nivel ' . $fud->nivel_autonomia_req,
        'Tipo operación'   => $fud->tipo_operacion,
      ] as $label => $val)
        <div>
          <div class="detail-label">{{ $label }}</div>
          <div class="detail-value">{{ $val }}</div>
        </div>
      @endforeach
    </div>
  </div>
</div>

{{-- MOTIVO --}}
<div class="card">
  <div class="card-title">
    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
      <path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/>
    </svg>
    Motivo / Sustento de la propuesta
  </div>
  <p style="font-size:13px;color:#444;background:#fafafa;padding:14px;border-radius:8px;margin:0;line-height:1.6">
    {{ $fud->motivo }}
  </p>
</div>

{{-- COMENTARIO APROBADOR --}}
@if($fud->comentario_aprobador)
  <div class="card" style="border-top-color:{{ $fud->estaAprobado() ? 'var(--verde)' : '#c0392b' }}">
    <div class="card-title" style="color:{{ $fud->estaAprobado() ? 'var(--verde)' : '#c0392b' }}">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <circle cx="12" cy="12" r="10"/>
        @if($fud->estaAprobado())
          <polyline points="9 12 11 14 15 10"/>
        @else
          <line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>
        @endif
      </svg>
      Decisión del aprobador
    </div>
    <p style="font-size:13px;color:#444;margin:0 0 8px">{{ $fud->comentario_aprobador }}</p>
    <p style="font-size:12px;color:#888;margin:0">
      Decidido por <strong>{{ $fud->aprobadoPor->name ?? '—' }}</strong>
      el {{ $fud->fecha_decision?->format('d/m/Y') }}
    </p>
  </div>
@endif

{{-- ADJUNTOS --}}
<div class="card">
  <div class="card-title">
    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
      <path d="M21.44 11.05l-9.19 9.19a6 6 0 01-8.49-8.49l9.19-9.19a4 4 0 015.66 5.66l-9.2 9.19a2 2 0 01-2.83-2.83l8.49-8.48"/>
    </svg>
    Archivos adjuntos
    <span class="badge badge-gray" style="margin-left:6px">
      {{ $fud->adjuntos->count() }}
    </span>
  </div>

  @if($fud->adjuntos->count())
    <div style="overflow-x:auto">
      <table>
        <thead>
          <tr>
            <th>Archivo</th>
            <th>Tipo</th>
            <th>Subido por</th>
            <th>Fecha</th>
            <th>Tamaño</th>
            <th>Acciones</th>
          </tr>
        </thead>

        <tbody>
          @foreach($fud->adjuntos as $adj)
            <tr>
              <td>
                <div style="display:flex;align-items:center;gap:8px">
                  <svg width="14" height="14" viewBox="0 0 24 24"
                       fill="none" stroke="var(--rojo)" stroke-width="2">
                    <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
                    <polyline points="14 2 14 8 20 8"/>
                  </svg>

                  <span style="font-size:13px">
                    {{ $adj->nombre_archivo }}
                  </span>
                </div>
              </td>

              <td>
                <span class="badge badge-info">
                  {{ $adj->tipo_adjunto }}
                </span>
              </td>

              <td style="font-size:12px">
                {{ $adj->subidor->name }}
              </td>

              <td style="font-size:12px;color:#888">
                {{ \Carbon\Carbon::parse($adj->created_at)->format('d/m/Y H:i') }}
              </td>

              <td style="font-size:12px;color:#888">
                {{ $adj->size_formateado }}
              </td>

              <td>
                <div style="display:flex;align-items:center;gap:4px">

                  {{-- Descargar --}}
                  <a href="{{ route('fud.download', $adj) }}"
                     title="Descargar {{ $adj->nombre_archivo }}"
                     style="display:inline-flex;align-items:center;justify-content:center;
                            width:28px;height:28px;border-radius:6px;
                            background:#f0f4ff;color:#2563eb;
                            border:1px solid #c7d7fc;text-decoration:none;
                            transition:background .15s">

                    <svg width="13" height="13" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor" stroke-width="2">
                      <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/>
                      <polyline points="17 8 12 13 7 8"/>
                      <line x1="12" y1="13" x2="12" y2="3"/>
                    </svg>
                  </a>

                  {{-- Eliminar --}}
                  <button type="button"
                          disabled
                          style="background:none;border:none;cursor:not-allowed;
                                 color:#ccc;padding:4px 6px;border-radius:6px;
                                 display:flex;align-items:center"
                          title="Para eliminar, use Editar propuesta">

                    <svg width="13" height="13" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor" stroke-width="2">
                      <polyline points="3 6 5 6 21 6"/>
                      <path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/>
                      <path d="M10 11v6M14 11v6"/>
                    </svg>
                  </button>

                </div>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  @else
    <p style="color:#999;font-size:13px;margin:0">
      No hay archivos adjuntos.
    </p>
  @endif
</div>

<div class="actions">
  <a href="{{ route('fud.index') }}" class="btn btn-ghost">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
      <line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/>
    </svg>
    Volver a mis propuestas
  </a>

  @if($fud->enviado_por === auth()->id() && $fud->estaObservado())
    <a href="{{ route('fud.edit', $fud) }}" class="btn btn-secondary">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/>
        <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
      </svg>
      Editar propuesta
    </a>
  @endif
</div>

@endsection