@extends('layouts.app')
@section('title', 'Panel Principal')

@section('content')
@php $user = auth()->user(); @endphp

{{-- CABECERA --}}
<div class="page-head">
  <div class="page-head-text">
    <h2>Bienvenido, {{ strtoupper($user->name) }}</h2>
    @if($campana)
      <p>Campaña <strong>"{{ $campana->nombre }}"</strong>
         · {{ $campana->fecha_inicio->format('d/m/Y') }} – {{ $campana->fecha_fin->format('d/m/Y') }}</p>
    @else
      <p>No hay una campaña activa configurada.</p>
    @endif
  </div>
  <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
    @if($campana && now()->between($campana->fecha_inicio, $campana->fecha_fin->copy()->endOfDay()))
      <span class="badge badge-ok">Campaña vigente · quedan {{ (int) now()->diffInDays($campana->fecha_fin) }} días</span>
    @endif
    <span class="badge badge-info">{{ $user->rol->nombre }}</span>
  </div>
</div>

{{-- KPIs --}}
<div class="grid4" style="margin-bottom:24px">
  @if($user->esGrupoFud())
    <div class="kpi-card ">
      <div class="kpi-label">Mis expedientes</div>
      <div class="kpi-value">{{ $kpis['total'] }}</div>
      <div class="kpi-sub">{{ $kpis['sustentos'] }} con sustentos por cargar</div>
    </div>
    <div class="kpi-card mostaza">
      <div class="kpi-label">En revisión</div>
      <div class="kpi-value">{{ $kpis['revision'] }}</div>
      <div class="kpi-sub">Esperando aprobación</div>
    </div>
    <div class="kpi-card verde">
      <div class="kpi-label">Aprobadas</div>
      <div class="kpi-value">{{ $kpis['aprobadas'] }}</div>
      <div class="kpi-sub">{{ $kpis['total'] ? round($kpis['aprobadas'] / $kpis['total'] * 100) : 0 }}% de mis expedientes</div>
    </div>
    <div class="kpi-card danger">
      <div class="kpi-label">Observadas / Rechazadas</div>
      <div class="kpi-value">{{ $kpis['observadas'] }}</div>
      <div class="kpi-sub">Requieren atención</div>
    </div>
  @else
    <div class="kpi-card">
      <div class="kpi-label">En tu bandeja</div>
      <div class="kpi-value">{{ $kpis['pendientes'] }}</div>
      <div class="kpi-sub">FUDs pendientes de decisión</div>
    </div>
    <div class="kpi-card verde">
      <div class="kpi-label">Aprobados por mí</div>
      <div class="kpi-value">{{ $kpis['aprobadas'] }}</div>
      <div class="kpi-sub">{{ $kpis['mes'] }} decisiones este mes</div>
    </div>
    <div class="kpi-card mostaza">
      <div class="kpi-label">Observados por mí</div>
      <div class="kpi-value">{{ $kpis['observadas'] }}</div>
      <div class="kpi-sub">Esperando corrección del ejecutivo</div>
    </div>
    <div class="kpi-card info">
      <div class="kpi-label">Nivel de autonomía</div>
      <div class="kpi-value">{{ $kpis['nivel'] }}</div>
      <div class="kpi-sub">{{ $user->rol->nombre }}</div>
    </div>
  @endif
</div>

{{-- ALERTA BANDEJA --}}
@if($user->esAprobador() && isset($pendientes) && $pendientes->count())
  <div class="notice" style="margin-bottom:20px">
    <strong>📬 Tienes {{ $pendientes->count() }} FUD(s) pendientes de revisión.</strong>
    <a href="{{ route('bandeja.index') }}" style="color:var(--rojo);font-weight:700;margin-left:10px">
      Ir a bandeja →
    </a>
  </div>
@endif

{{-- GRUPO FUD: flujo + tabla --}}
@if($user->esGrupoFud())
  <div class="card">
    <div class="card-title">📌 Flujo de la Campaña</div>
    <div class="steps">
      <div class="step done">1. Contacto cliente</div>
      <div class="step done">2. Verif. elegibilidad</div>
      <div class="step active">3. Envío FUD</div>
      <div class="step">4. Aprobación</div>
      <div class="step">5. Ejecución</div>
      <div class="step">6. Sustentos</div>
    </div>
    <div class="grid3" style="margin-top:16px">
      <div style="font-size:13px;padding:14px;background:#fafafa;border-radius:8px;border-left:3px solid var(--rojo)">
        <strong>Documentos obligatorios</strong><br>
        <span style="color:#666;font-size:12px">Acuerdo de Pago + Nota de Abono. Cancelación SK &gt; 3 UIT: adjuntar Informe-Propuesta.</span>
      </div>
      <div style="font-size:13px;padding:14px;background:#fafafa;border-radius:8px;border-left:3px solid var(--mostaza)">
        <strong>Límite de conciliaciones</strong><br>
        <span style="color:#666;font-size:12px">Máx. 4 veces en 12 meses por cliente. Excedentes requieren autorización.</span>
      </div>
      <div style="font-size:13px;padding:14px;background:#fafafa;border-radius:8px;border-left:3px solid var(--verde)">
        <strong>Exclusiones clave</strong><br>
        <span style="color:#666;font-size:12px">Pignoraticios, leasing, Multigas, programas de Gobierno (salvo rechazados).</span>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-title" style="display:flex;justify-content:space-between;align-items:center">
      <span>📋 Mis propuestas recientes</span>
      <a href="{{ route('fud.create') }}" class="btn btn-primary" style="font-size:12px;padding:8px 14px">
        + Nueva propuesta
      </a>
    </div>
    <table>
      <thead>
        <tr>
          <th>Cliente</th><th>Operación</th><th>Tipo</th>
          <th>Aprobador</th><th>Estado</th><th>Fecha</th><th></th>
        </tr>
      </thead>
      <tbody>
        @forelse($propuestas as $p)
          <tr>
            <td><strong>{{ $p->cliente->nombre_cliente }}</strong></td>
            <td>{{ $p->cliente->operacion }}</td>
            <td style="font-size:12px">{{ $p->tipo_negociacion_desc ?? '—' }}</td>
            <td style="font-size:12px;color:#666">{{ $p->aprobador->name ?? '—' }}</td>
            <td>
              @php
                $badge = match($p->estado_fud) {
                  'Aprobado'    => 'badge-ok',
                  'Observado',
                  'Rechazado'   => 'badge-danger',
                  default       => 'badge-pending',
                };
              @endphp
              <span class="badge {{ $badge }}">{{ $p->estado_fud }}</span>
            </td>
            <td>{{ $p->fecha_envio->format('d/m/Y') }}</td>
            <td>
              <a href="{{ route('fud.show', $p) }}"
                 style="font-size:12px;color:var(--rojo);font-weight:600">Ver →</a>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="7" style="text-align:center;color:#999;padding:28px">
              No hay propuestas aún.
              <a href="{{ route('fud.create') }}" style="color:var(--rojo);font-weight:700">
                Crear primera propuesta →
              </a>
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

{{-- GRUPO APROBADOR --}}
@else
  <div class="card">
    <div class="card-title">📊 Resumen de gestión – campaña activa</div>
    <div class="grid2">
      <div>
        <p style="font-size:13px;color:#555;margin:0 0 12px">
          Como <strong>{{ $user->rol->nombre }}</strong> tienes autonomía para revisar
          y aprobar FUDs según los lineamientos de la campaña.
        </p>
        @php
          $autonomias = [
            1 => 'Puedes aprobar: conciliaciones de intereses (cartera vencida no hipotecaria), cancelaciones totales Deficiente/Dudoso/Pérdida con SK < 3 UITs, cartera judicial/extrajudicial con SK < 3 UITs.',
            2 => 'Puedes aprobar todo lo del nivel 1 más: cancelaciones con SK > 3 UITs, excedentes del límite de 4 conciliaciones, excepciones de descuento mayor al estándar.',
            3 => 'Tienes visibilidad completa de la campaña y autorización para casos escalados desde Jefatura de Recuperaciones.',
          ];
        @endphp
        <div style="background:#fafafa;border-radius:8px;padding:14px;font-size:13px;border-left:3px solid var(--rojo)">
          {{ $autonomias[$user->nivel()] ?? '' }}
        </div>
      </div>
      <div>
        <div class="card-title" style="font-size:13px;margin-bottom:12px">📅 Recordatorios clave</div>
        <div style="font-size:12px;color:#555;display:flex;flex-direction:column;gap:8px">
          <div style="padding:8px 12px;background:#fff8e7;border-radius:6px;border-left:3px solid var(--mostaza)">
            Reportes de conciliaciones: <strong>primeros 2 días hábiles del mes</strong>
          </div>
          <div style="padding:8px 12px;background:#f0faf5;border-radius:6px;border-left:3px solid var(--verde)">
            Sustentos: cargar diariamente en link del Área de Operaciones
          </div>
          <div style="padding:8px 12px;background:#fdf0f0;border-radius:6px;border-left:3px solid var(--rojo)">
            Pago fraccionado cancelación total: <strong>máx. 90 días</strong> desde 1ra amortización
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-title">✅ FUDs pendientes de decisión</div>
    <table>
      <thead>
        <tr>
          <th>Fecha</th><th>Enviado por</th><th>Cliente</th><th>Operación</th>
          <th>Saldo Capital</th><th>Calificación</th><th>Tipo</th><th>Acción</th>
        </tr>
      </thead>
      <tbody>
        @forelse($pendientes ?? [] as $p)
          <tr>
            <td>{{ $p->fecha_envio->format('d/m/Y') }}</td>
            <td style="font-size:12px">{{ $p->enviador->name }}</td>
            <td><strong>{{ $p->cliente->nombre_cliente }}</strong></td>
            <td>{{ $p->cliente->operacion }}</td>
            <td><strong>{{ $p->cliente->saldo_formateado }}</strong></td>
            <td><span class="badge badge-info">{{ $p->cliente->calificacion_rcc }}</span></td>
            <td style="font-size:12px">{{ $p->tipo_negociacion_desc }}</td>
            <td>
              <a href="{{ route('bandeja.index') }}"
                 class="btn btn-primary" style="font-size:12px;padding:6px 12px">
                Revisar
              </a>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="8" style="text-align:center;color:#999;padding:28px">
              No hay FUDs pendientes en tu bandeja. ✓
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
@endif

@endsection