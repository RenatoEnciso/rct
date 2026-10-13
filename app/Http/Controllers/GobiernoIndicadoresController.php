<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use App\Models\{ExpedienteGobierno, ExpedienteGobiernoRevision, Gestion, GobTipoDocumento, Lote, Zona};
use App\Services\Honramiento\Checklist;

/** Indicadores de los expedientes de honramiento (Programas de Gobierno). */
class GobiernoIndicadoresController extends Controller
{
    private const COMPLETOS = ['Completo', 'Revisado por zona', 'Conforme'];
    private const PENDIENTES = ['Pendiente', 'Incompleto', 'Observado'];

    public function index(Request $request, Checklist $checklist)
    {
        $u = $request->user();
        abort_unless($u->esAprobador(), 403);

        $exps = ExpedienteGobierno::visiblePara($u)->with('zona', 'tienda', 'lote', 'documentos')
            ->when($request->lote_id, fn($q, $v) => $q->where('lote_id', $v))
            ->when($request->zona_id, fn($q, $v) => $q->where('zona_id', $v))
            ->when($request->gestion_id, fn($q, $v) => $q->where('gestion_id', $v))
            ->get();

        $total = $exps->count();
        $monto = fn(Collection $c) => (float) $c->sum('monto_honrado');

        $kpis = [
            'total'         => $total,
            'completos'     => $exps->whereIn('estado', self::COMPLETOS)->count(),
            'conformes'     => $exps->where('estado', 'Conforme')->count(),
            'definitivos'   => $exps->where('estado', 'Incompleto definitivo')->count(),
            'vencidos'      => $exps->filter(fn($e) => $e->situacionPlazo() === 'vencido')->count(),
            'faltantes'     => (int) $exps->sum('faltantes'),
            'monto_total'   => $monto($exps),
            'monto_conforme'=> $monto($exps->where('estado', 'Conforme')),
            'monto_revision'=> $monto($exps->whereIn('estado', ['Completo', 'Revisado por zona'])),
            'monto_pendiente'=> $monto($exps->whereIn('estado', self::PENDIENTES)),
            'monto_definitivo'=> $monto($exps->where('estado', 'Incompleto definitivo')),
        ];

        // Documento que más falta (sobre los expedientes donde aplica)
        $docs = GobTipoDocumento::catalogo()->map(fn($t) => ['tipo' => $t, 'aplica' => 0, 'faltan' => 0, 'exigido_en' => 0])->keyBy(fn($x) => $x['tipo']->id);
        foreach ($exps as $e) {
            foreach ($checklist->documentos($e) as $it) {
                $fila = $docs[$it['tipo']->id];
                $fila['aplica']++;
                if ($it['exigido']) $fila['exigido_en']++;
                if (in_array($it['estado'], ['falta', 'no_disponible', 'observado'])) $fila['faltan']++;
                $docs[$it['tipo']->id] = $fila;
            }
        }
        // Primero los exigidos por COFIDE (son los que definen el estado), luego los complementarios
        $documentos = $docs->filter(fn($d) => $d['aplica'])
            ->sortBy([fn($a, $b) => ($b['exigido_en'] > 0) <=> ($a['exigido_en'] > 0), fn($a, $b) => $b['faltan'] <=> $a['faltan']])
            ->values();

        return view('gobierno.indicadores', [
            'kpis'       => $kpis,
            'porZona'    => $this->agrupar($exps, fn($e) => $e->zona->nombre ?? 'Sin zona'),
            'porLote'    => $this->agrupar($exps, fn($e) => $e->lote?->codigo ? $e->lote->codigo.($e->lote->programa ? ' · '.$e->lote->programa : '') : 'Sin lote'),
            'porTienda'  => $this->agrupar($exps, fn($e) => ($e->tienda->nombre ?? '—').' · '.($e->zona->nombre ?? '')),
            'documentos' => $documentos,
            'avance'     => $this->avance($exps->pluck('id')),
            // Impulso MyPerú: lotes, honramiento, pagos y devolución (revisión central)
            'impulso'    => \App\Http\Controllers\Impulso\CoreController::esCentral($u) ? app(\App\Services\Impulso\Indicadores::class)->resumen() : null,
            'porEstado'  => $exps->groupBy('estado')->map->count(),
            'lotes'      => Lote::orderBy('codigo')->get(),
            'zonas'      => Zona::whereIn('id', $exps->pluck('zona_id')->unique())->orderBy('nombre')->get(),
            'gestiones'  => Gestion::orderBy('nombre')->get(),
        ]);
    }

    /** Total, % completos, % conformes, faltantes y monto por grupo. */
    private function agrupar(Collection $exps, callable $clave): Collection
    {
        return $exps->groupBy($clave)->map(function ($g, $nombre) {
            $n = $g->count();
            return [
                'nombre'      => $nombre,
                'total'       => $n,
                'completos'   => $c = $g->whereIn('estado', self::COMPLETOS)->count(),
                'conformes'   => $k = $g->where('estado', 'Conforme')->count(),
                'pct_completo'=> $n ? round($c / $n * 100, 1) : 0,
                'pct_conforme'=> $n ? round($k / $n * 100, 1) : 0,
                'faltantes'   => (int) $g->sum('faltantes'),
                'monto'       => (float) $g->sum('monto_honrado'),
            ];
        })->values();
    }

    /** Conformidades por semana (zona y central) en las últimas 12 semanas. */
    private function avance(Collection $ids): array
    {
        $desde = now()->startOfWeek()->subWeeks(11);
        $revs = ExpedienteGobiernoRevision::whereIn('expediente_id', $ids)->where('resultado', 'conforme')
            ->where('revisado_en', '>=', $desde)->get(['nivel', 'revisado_en']);

        $semanas = [];
        for ($i = 0; $i < 12; $i++) {
            $ini = $desde->copy()->addWeeks($i);
            $semanas[] = [
                'etiqueta' => $ini->format('d/m'),
                'zona'     => $revs->where('nivel', 'zona')->filter(fn($r) => $r->revisado_en->between($ini, $ini->copy()->endOfWeek()))->count(),
                'central'  => $revs->where('nivel', 'central')->filter(fn($r) => $r->revisado_en->between($ini, $ini->copy()->endOfWeek()))->count(),
            ];
        }
        return $semanas;
    }
}
