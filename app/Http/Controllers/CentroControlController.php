<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use App\Models\{AlertaControl, Campana, ExpedienteGobierno, MetaZona, PriorizacionCartera, PropuestaFud, Recupero, SeguimientoBeneficio, Zona};

/** Centro de control: resumen ejecutivo de recuperaciones, programas y analítica. */
class CentroControlController extends Controller
{
    private const COMPLETOS = ['Completo', 'Revisado por zona', 'Conforme'];

    public function index(Request $request)
    {
        $u = $request->user();
        abort_unless($u->esAprobador(), 403);

        // El coordinador zonal ve su zona; Jefatura y Gerencia, todas (con filtro opcional)
        $zonaId = $u->nivel() === 1 ? $u->tienda?->zona_id : ($request->integer('zona_id') ?: null);
        $hoy = now()->startOfDay();
        $campana = Campana::where('activa', true)->first();
        $inicioCamp = Carbon::parse($campana->fecha_inicio ?? '2026-09-01')->startOfDay();
        $periodo = $request->input('periodo') === 'campana' ? 'campana' : 'anio';
        $desde = $periodo === 'campana' ? $inicioCamp : $hoy->copy()->startOfYear();

        $recuperos = Recupero::with('tienda.zona')->when($zonaId, fn($q) => $q->where('zona_id', $zonaId))->whereBetween('fecha_pago', [$desde, $hoy])->get();
        $todos = Recupero::when($zonaId, fn($q) => $q->where('zona_id', $zonaId))->where('fecha_pago', '<=', $hoy)->get();

        return view('analitica.centro-control', [
            'periodo'   => $periodo,
            'desde'     => $desde,
            'hoy'       => $hoy,
            'campana'   => $campana,
            'zonaId'    => $zonaId,
            'zonaFija'  => $u->nivel() === 1,
            'zonas'     => Zona::whereIn('id', Recupero::distinct()->pluck('zona_id'))->orderBy('nombre')->get(),
            'kpis'      => $this->kpis($recuperos, $todos, $inicioCamp, $hoy, $zonaId),
            'tendencia' => $this->tendencia($todos, $zonaId, $hoy),
            'metas'     => $this->avanceMetas($todos, $inicioCamp, $hoy, $zonaId),
            'tiendas'   => $this->topTiendas($recuperos),
            'analitica' => $this->analitica($zonaId, $inicioCamp),
            'simulador' => $this->simulador($zonaId, $inicioCamp),
        ]);
    }

    private function kpis(Collection $recs, Collection $todos, Carbon $inicioCamp, Carbon $hoy, ?int $zonaId): array
    {
        $recuperado = $recs->sum(fn($r) => $r->recuperado);
        $condonado  = $recs->sum(fn($r) => $r->condonado);

        // Promedio mensual en campaña frente al promedio mensual previo (mismo año)
        $mesesCamp = max(0.5, $inicioCamp->floatDiffInMonths($hoy->copy()->addDay()));
        $previo = $todos->filter(fn($r) => $r->fecha_pago->lt($inicioCamp) && $r->fecha_pago->gte($hoy->copy()->startOfYear()));
        $mesesPrev = max(1, $hoy->copy()->startOfYear()->floatDiffInMonths($inicioCamp));
        $promCamp = $todos->filter(fn($r) => $r->fecha_pago->gte($inicioCamp))->sum(fn($r) => $r->recuperado) / $mesesCamp;
        $promPrev = $previo->sum(fn($r) => $r->recuperado) / $mesesPrev;

        $gob = ExpedienteGobierno::when($zonaId, fn($q) => $q->where('zona_id', $zonaId))->get(['estado']);
        $normalizados = $recs->filter(fn($r) => $r->tipo_negociacion !== 'cuotas' || $r->provision_liberada > 0)->count();

        return [
            'recuperado'         => $recuperado,
            'recuperado_capital' => $recs->sum('recuperado_capital'),
            'variacion_campana'  => $promPrev > 0 ? round(($promCamp / $promPrev - 1) * 100, 1) : null,
            'provision'          => $recs->sum('provision_liberada'),
            'condonado'          => $condonado,
            'pct_condonado'      => ($recuperado + $condonado) > 0 ? round($condonado / ($recuperado + $condonado) * 100, 1) : 0,
            'ratio'              => $condonado > 0 ? round($recuperado / $condonado, 2) : 0,
            'beneficios'         => $recs->count(),
            'normalizados'       => $normalizados,
            'gob_total'          => $gob->count(),
            'gob_completos'      => $gob->whereIn('estado', self::COMPLETOS)->count(),
            // Reservado: devoluciones a COFIDE al día (se conecta con el módulo de devoluciones de Impulso MyPerú)
            'cofide'             => null,
        ];
    }

    /** Recuperado, condonado y meta por mes del año en curso. */
    private function tendencia(Collection $todos, ?int $zonaId, Carbon $hoy): array
    {
        $metas = MetaZona::when($zonaId, fn($q) => $q->where('zona_id', $zonaId))->get()
            ->groupBy(fn($m) => $m->mes->format('Y-m'))->map->sum('meta_recupero');
        $out = [];
        for ($m = $hoy->copy()->startOfYear(); $m->lte($hoy); $m->addMonth()) {
            $delMes = $todos->filter(fn($r) => $r->fecha_pago->isSameMonth($m));
            $out[] = [
                'mes'        => ucfirst($m->translatedFormat('M')),
                'recuperado' => round($delMes->sum(fn($r) => $r->recuperado)),
                'condonado'  => round($delMes->sum(fn($r) => $r->condonado)),
                'meta'       => round($metas[$m->format('Y-m')] ?? 0),
                'parcial'    => $m->isSameMonth($hoy),
            ];
        }
        return $out;
    }

    /** Avance de la campaña por zona: recuperado vs meta acumulada a la fecha (mes en curso prorrateado). */
    private function avanceMetas(Collection $todos, Carbon $inicioCamp, Carbon $hoy, ?int $zonaId): Collection
    {
        $fraccionMes = $hoy->day / $hoy->daysInMonth;
        $metas = MetaZona::with('zona')->when($zonaId, fn($q) => $q->where('zona_id', $zonaId))
            ->whereBetween('mes', [$inicioCamp->copy()->startOfMonth(), $hoy->copy()->startOfMonth()])->get();

        // Una sola zona visible: avance de cada mes de campaña
        if ($zonaId) {
            return $metas->sortBy('mes')->map(function ($m) use ($todos, $hoy, $fraccionMes) {
                $actual = $m->mes->isSameMonth($hoy);
                $meta = $m->meta_recupero * ($actual ? $fraccionMes : 1);
                $rec = $todos->filter(fn($r) => $r->fecha_pago->isSameMonth($m->mes))->sum(fn($r) => $r->recuperado);
                return ['zona' => ucfirst($m->mes->translatedFormat('F')).($actual ? ' (al '.$hoy->format('d/m').')' : ''),
                        'recuperado' => $rec, 'meta' => $meta, 'avance' => $meta > 0 ? round($rec / $meta * 100, 1) : 0];
            })->values();
        }

        return $metas->groupBy('zona_id')->map(function ($ms, $zid) use ($todos, $inicioCamp, $hoy, $fraccionMes) {
            $meta = $ms->sum(fn($m) => $m->mes->isSameMonth($hoy) ? $m->meta_recupero * $fraccionMes : $m->meta_recupero);
            $rec = $todos->where('zona_id', $zid)->filter(fn($r) => $r->fecha_pago->gte($inicioCamp))->sum(fn($r) => $r->recuperado);
            return ['zona' => $ms->first()->zona->nombre, 'recuperado' => $rec, 'meta' => $meta,
                    'avance' => $meta > 0 ? round($rec / $meta * 100, 1) : 0];
        })->sortByDesc('avance')->values();
    }

    private function topTiendas(Collection $recs): Collection
    {
        return $recs->groupBy('tienda_id')->map(fn($g) => [
            'tienda'     => $g->first()->tienda->nombre ?? '—',
            'zona'       => $g->first()->tienda->zona->nombre ?? '',
            'recuperado' => $g->sum(fn($r) => $r->recuperado),
            'beneficios' => $g->count(),
        ])->sortByDesc('recuperado')->take(5)->values();
    }

    /** Cartera prioritaria pendiente, recaída a 90 días y alertas abiertas. */
    private function analitica(?int $zonaId, Carbon $inicioCamp): array
    {
        $pendientes = $this->pendientes($zonaId, $inicioCamp)->whereIn('segmento', ['Alta', 'Media']);
        $seg = SeguimientoBeneficio::whereNotNull('estado_90')
            ->when($zonaId, fn($q) => $q->whereHas('cliente', fn($c) => $c->where('zona_id', $zonaId)))->get(['estado_90']);
        $alertas = AlertaControl::abiertas()->when($zonaId, fn($q) => $q->where('zona_id', $zonaId))->get(['severidad']);

        return [
            'prioritaria'      => $pendientes->count(),
            'prioritaria_alta' => $pendientes->where('segmento', 'Alta')->count(),
            'recuperable'      => $pendientes->sum(fn($p) => $p->recupero_esperado),
            'acciones'         => $pendientes->countBy('accion_sugerida')->sortDesc()->all(),
            'sostenida'        => $seg->count() ? round($seg->where('estado_90', '!=', 'Recayó')->count() / $seg->count() * 100, 1) : null,
            'seguimiento'      => $seg->count(),
            'alertas'          => ['Alta' => $alertas->where('severidad', 'Alta')->count(),
                                   'Media' => $alertas->where('severidad', 'Media')->count(),
                                   'Baja' => $alertas->where('severidad', 'Baja')->count()],
        ];
    }

    /**
     * Cartera pendiente (sin propuesta desde el inicio de la campaña) para el simulador:
     * recupero esperado y provisión esperada por crédito, en orden de prioridad y en el orden de la base de aptos (por número de crédito).
     */
    private function simulador(?int $zonaId, Carbon $inicioCamp): array
    {
        $pend = $this->pendientes($zonaId, $inicioCamp);
        $fila = fn($p) => [round($p->recupero_esperado), round((float) $p->provision_liberable * (float) $p->probabilidad_pago / 100)];
        return [
            'prioridad' => $pend->sortByDesc('score')->values()->map($fila)->all(),
            'normal'    => $pend->sortBy(fn($p) => $p->cliente->credito_sicmact)->values()->map($fila)->all(),
        ];
    }

    private function pendientes(?int $zonaId, Carbon $inicioCamp): Collection
    {
        $gestionados = PropuestaFud::where('fecha_envio', '>=', $inicioCamp)->distinct()->pluck('cliente_cartera_id');
        return PriorizacionCartera::with('cliente:id,credito_sicmact,zona_id')
            ->whereNotIn('cliente_cartera_id', $gestionados)
            ->when($zonaId, fn($q) => $q->whereHas('cliente', fn($c) => $c->where('zona_id', $zonaId)))
            ->get();
    }
}
