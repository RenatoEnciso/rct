<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\{PropuestaFud, Sustento, Zona, Tienda, Campana, ExpedienteGobierno};

class ReportesController extends Controller
{
    // Documentos de sustento que se controlan en el tablero
    private const DOCS_SUSTENTO = [
        'Acuerdo de Pago', 'Nota de Abono', 'Comprobante de Pago',
        'Hoja de Convenio', 'Informe-Propuesta SK>3UIT',
    ];

    public function dashboard(Request $request)
    {
        // ── Filtros ──────────────────────────────────────────
        $zonaId   = $request->zona_id;
        $tiendaId = $request->tienda_id;
        $estado   = $request->estado;

        // Base: expedientes vigentes (última versión de cada FUD) con los filtros aplicados
        $base = fn() => PropuestaFud::vigentes()
            ->when($zonaId,   fn($q) => $q->whereHas('cliente', fn($c) => $c->where('zona_id',   $zonaId)))
            ->when($tiendaId, fn($q) => $q->whereHas('cliente', fn($c) => $c->where('tienda_id', $tiendaId)));
        $qBase = fn() => $base()->when($estado, fn($q) => $q->where('estado_fud', $estado));

        // ── KPIs de FUD ──────────────────────────────────────
        $porEstadoRaw = $base()->selectRaw('estado_fud, count(*) as total')
            ->groupBy('estado_fud')->pluck('total', 'estado_fud');
        $aprobados  = (int) ($porEstadoRaw['Aprobado']    ?? 0);
        $enRevision = (int) ($porEstadoRaw['En revisión'] ?? 0);
        $observados = (int) ($porEstadoRaw['Observado']   ?? 0);
        $rechazados = (int) ($porEstadoRaw['Rechazado']   ?? 0);
        $totalFuds  = $aprobados + $enRevision + $observados + $rechazados;
        $cumplimientoPct = $totalFuds > 0 ? round($aprobados / $totalFuds * 100, 1) : 0;

        // ── KPIs de sustentos (de los FUD filtrados) ─────────
        $sustBase = fn() => Sustento::whereIn('propuesta_fud_id', $qBase()->select('id'));
        $sustPorEstado = $sustBase()->selectRaw('estado, count(*) as total')
            ->groupBy('estado')->pluck('total', 'estado');
        $totalSustentos      = (int) $sustPorEstado->sum();
        $sustentosConformes  = (int) ($sustPorEstado['Aprobado'] ?? 0);
        $sustentosEnRevision = (int) ($sustPorEstado['En revisión'] ?? 0);
        $sustentosPendientes = (int) (($sustPorEstado['Pendiente'] ?? 0) + ($sustPorEstado['Observado'] ?? 0));
        $cumplimientoSustentos = $totalSustentos > 0
            ? round($sustentosConformes / $totalSustentos * 100, 1) : 0;

        // ── Montos (saldo capital de FUD aprobados) ──────────
        $montoAprobados = fn() => $qBase()->aprobados()
            ->join('clientes_cartera', 'clientes_cartera.id', '=', 'propuestas_fud.cliente_cartera_id');

        // Normalizado: sustento conforme. En riesgo: aprobado pero con sustento sin validar.
        $montoNormalizado = (float) $montoAprobados()
            ->whereHas('sustentos', fn($q) => $q->where('estado', 'Aprobado'))
            ->sum('clientes_cartera.saldo_capital');
        $montoEnRiesgo = (float) $montoAprobados()
            ->whereHas('sustentos', fn($q) => $q->where('estado', '!=', 'Aprobado'))
            ->sum('clientes_cartera.saldo_capital');

        // ── Cumplimiento por zona (con variación real vs mes anterior) ──
        $zonas = Zona::orderBy('nombre')->get();
        $mesActual   = now()->startOfMonth();
        $mesAnterior = now()->subMonthNoOverflow()->startOfMonth();

        $porZona = $this->agrupadoPor('clientes_cartera.zona_id', $qBase);
        $porZonaMesAct = $this->agrupadoPor('clientes_cartera.zona_id', $qBase, $mesActual, now());
        $porZonaMesAnt = $this->agrupadoPor('clientes_cartera.zona_id', $qBase, $mesAnterior, $mesActual->copy()->subSecond());

        $cumplimientoPorZona = $zonas->map(function ($zona) use ($porZona, $porZonaMesAct, $porZonaMesAnt) {
            $f   = $porZona[$zona->id] ?? null;
            $tot = (int) ($f->total ?? 0);
            $apr = (int) ($f->aprobados ?? 0);
            return [
                'zona'       => $zona->nombre,
                'total'      => $tot,
                'aprobados'  => $apr,
                'pendientes' => $tot - $apr,
                'pct'        => $tot > 0 ? round($apr / $tot * 100, 1) : 0,
                // Puntos porcentuales: tasa de aprobación de FUD enviados este mes vs. el mes anterior
                'variacion'  => round($this->pct($porZonaMesAct[$zona->id] ?? null)
                                    - $this->pct($porZonaMesAnt[$zona->id] ?? null), 1),
            ];
        })->filter(fn($z) => $z['total'] > 0)->sortBy('pct')->values();

        // ── Tiendas con menor cumplimiento ──────────────────
        $porTienda = $this->agrupadoPor('clientes_cartera.tienda_id', $qBase);
        $topTiendasBajo = Tienda::with('zona')->get()
            ->map(function ($tienda) use ($porTienda) {
                $f   = $porTienda[$tienda->id] ?? null;
                $tot = (int) ($f->total ?? 0);
                $apr = (int) ($f->aprobados ?? 0);
                return [
                    'tienda'    => $tienda->nombre,
                    'zona'      => $tienda->zona->nombre ?? '—',
                    'total'     => $tot,
                    'aprobados' => $apr,
                    'pct'       => $tot > 0 ? round($apr / $tot * 100, 1) : 0,
                ];
            })
            ->filter(fn($t) => $t['total'] > 0)
            ->sortBy('pct')
            ->take(10)
            ->values();

        // ── Documentos faltantes: sustentos por cargar o corregir sin ese documento conforme ──
        $documentosFaltantes = collect(self::DOCS_SUSTENTO)->map(fn($tipo) => [
            'documento' => $tipo,
            'faltantes' => $sustBase()->whereIn('estado', ['Pendiente', 'Observado'])
                ->whereDoesntHave('documentos', fn($q) =>
                    $q->where('tipo_doc', $tipo)->where('estado_revision', '!=', 'observado'))
                ->count(),
        ])->all();

        // ── Monto en riesgo por zona ─────────────────────────
        $riesgoZona = $montoAprobados()
            ->whereHas('sustentos', fn($q) => $q->where('estado', '!=', 'Aprobado'))
            ->groupBy('clientes_cartera.zona_id')
            ->selectRaw('clientes_cartera.zona_id, sum(clientes_cartera.saldo_capital) as monto')
            ->pluck('monto', 'zona_id');
        $montoPorZona = $zonas->map(fn($z) => [
            'zona'  => $z->nombre,
            'monto' => (float) ($riesgoZona[$z->id] ?? 0),
        ])->filter(fn($z) => $z['monto'] > 0)->sortByDesc('monto')->values();

        // ── Distribución por tipo de negociación ─────────────
        $porTipoNeg = $qBase()
            ->selectRaw("tipo_negociacion, count(*) as total, sum(case when estado_fud = 'Aprobado' then 1 else 0 end) as aprobados")
            ->groupBy('tipo_negociacion')
            ->orderByDesc('total')
            ->get();

        // ── FUDs por estado ──────────────────────────────────
        $porEstado = [
            'Aprobado'    => $aprobados,
            'En revisión' => $enRevision,
            'Observado'   => $observados,
            'Rechazado'   => $rechazados,
        ];

        // ── Actividad reciente ───────────────────────────────
        $actividadReciente = $qBase()->with('cliente', 'enviador', 'aprobadoPor')
            ->whereIn('estado_fud', ['Aprobado', 'Observado', 'Rechazado'])
            ->orderByDesc('fecha_decision')->orderByDesc('id')
            ->take(8)
            ->get();

        // ── Zonas críticas (< 50% cumplimiento) ─────────────
        $zonasCriticas = $cumplimientoPorZona->filter(fn($z) => $z['pct'] < 50)->count();

        // ── Programas de gobierno ────────────────────────────
        $gobierno = $this->resumenGobierno($zonaId, $tiendaId);

        $campana = Campana::activa();
        $tiendas = Tienda::when($zonaId, fn($q) => $q->where('zona_id', $zonaId))->orderBy('nombre')->get();

        return view('reportes.dashboard', compact(
            'totalFuds', 'aprobados', 'enRevision', 'observados', 'rechazados',
            'cumplimientoPct', 'cumplimientoSustentos',
            'totalSustentos', 'sustentosConformes', 'sustentosEnRevision', 'sustentosPendientes',
            'montoEnRiesgo', 'montoNormalizado',
            'cumplimientoPorZona', 'topTiendasBajo',
            'documentosFaltantes', 'montoPorZona', 'porTipoNeg',
            'porEstado', 'actividadReciente', 'zonasCriticas',
            'gobierno', 'campana',
            'zonas', 'tiendas',
            'zonaId', 'tiendaId', 'estado'
        ));
    }

    /** Total y aprobados de los FUD vigentes agrupados por una columna de la cartera. */
    private function agrupadoPor(string $columna, \Closure $qBase, $desde = null, $hasta = null)
    {
        return $qBase()
            ->join('clientes_cartera', 'clientes_cartera.id', '=', 'propuestas_fud.cliente_cartera_id')
            ->when($desde, fn($q) => $q->whereBetween('propuestas_fud.fecha_envio', [$desde, $hasta]))
            ->groupBy($columna)
            ->selectRaw("$columna as grupo, count(*) as total, sum(case when estado_fud = 'Aprobado' then 1 else 0 end) as aprobados, sum(case when estado_fud <> 'En revisión' then 1 else 0 end) as decididos")
            ->get()
            ->keyBy('grupo');
    }

    // Tasa de aprobación sobre lo ya decidido (lo que sigue en revisión no penaliza el mes en curso)
    private function pct($fila): float
    {
        return ($fila && $fila->decididos > 0) ? $fila->aprobados / $fila->decididos * 100 : 0;
    }

    private function resumenGobierno($zonaId, $tiendaId): array
    {
        $exp = ExpedienteGobierno::with('lote')
            ->when($zonaId,   fn($q) => $q->where('zona_id', $zonaId))
            ->when($tiendaId, fn($q) => $q->where('tienda_id', $tiendaId))
            ->get();

        // Completo = cuenta con los documentos exigidos por COFIDE (incluye los ya revisados)
        $completos = ['Completo', 'Revisado por zona', 'Conforme'];
        $catalogo  = \App\Models\GobTipoDocumento::catalogo();
        $exigidos  = fn($e) => max(1, $catalogo->filter(fn($t) => $t->exigidoPara($e->tipo_persona ?? 'natural'))->count());

        $total = $exp->count();
        return [
            'total'       => $total,
            'completos'   => $exp->whereIn('estado', $completos)->count(),
            'incompletos' => $exp->whereNotIn('estado', $completos)->count(),
            'avance'      => $total ? round($exp->avg(fn($e) => max(0, 1 - $e->faltantes / $exigidos($e)) * 100), 1) : 0,
            'porPrograma' => $exp->groupBy(fn($e) => $e->lote->programa ?? $e->programa ?? 'Sin programa')->map(fn($g) => [
                'total'     => $g->count(),
                'completos' => $g->whereIn('estado', $completos)->count(),
            ])->sortByDesc('total')->all(),
        ];
    }
}
