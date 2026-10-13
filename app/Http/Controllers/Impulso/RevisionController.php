<?php
namespace App\Http\Controllers\Impulso;

use App\Http\Controllers\Controller;
use App\Models\{ExpedienteGobierno, GobTipoDocumento, Lote, Zona};
use App\Services\Honramiento\Checklist;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Etapa 3 — Dashboard de revisión de expedientes con la regla del Anexo 13:
 * solo los documentos exigidos por COFIDE (6 persona natural / 7 jurídica) determinan el estado.
 */
class RevisionController extends Controller
{
    /** Los 4 estados del proceso. Observado (un obligatorio observado) cuenta como incompleto; revisado y conforme, como completo. */
    public const GRUPOS = [
        'PENDIENTE'             => ['Pendiente'],
        'INCOMPLETO'            => ['Incompleto', 'Observado'],
        'INCOMPLETO DEFINITIVO' => ['Incompleto definitivo'],
        'COMPLETO'              => ['Completo', 'Revisado por zona', 'Conforme'],
    ];

    public function index(Request $request, Checklist $checklist)
    {
        $u = $request->user();
        abort_unless($u->esAprobador(), 403);
        $zonaId = $u->nivel() === 1 ? $u->tienda?->zona_id : ($request->integer('zona_id') ?: null);
        $honramiento = $request->input('honramiento', 'Honrado');

        $exps = ExpedienteGobierno::with('lote', 'zona', 'documentos')
            ->when($zonaId, fn($q) => $q->where('zona_id', $zonaId))
            ->when($request->lote_id, fn($q, $v) => $q->where('lote_id', $v))
            ->when($honramiento !== 'todos', fn($q) => $q->where('estado_honramiento', $honramiento))
            ->get();

        $catalogo = GobTipoDocumento::catalogo();
        return view('impulso.revision', [
            'total'       => $exps->count(),
            'grupos'      => $this->grupos($exps),
            'porLote'     => $exps->groupBy(fn($e) => $e->lote?->nombre() ?? 'Sin lote')->map(fn($g) => $this->grupos($g))->sortKeys(),
            'porZona'     => $exps->groupBy(fn($e) => $e->zona->nombre ?? 'Sin zona')->map(fn($g) => $this->grupos($g))->sortKeys(),
            'documentos'  => $this->cumplimiento($exps, $checklist),
            'natural'     => $catalogo->filter(fn($t) => $t->cofide_natural)->values(),
            'juridica'    => $catalogo->filter(fn($t) => $t->cofide_juridica)->values(),
            'opcionales'  => $catalogo->filter(fn($t) => !$t->cofide_natural && !$t->cofide_juridica)->values(),
            'honramiento' => $honramiento,
            'zonaId'      => $zonaId,
            'zonaFija'    => $u->nivel() === 1,
            'zonas'       => Zona::whereIn('id', ExpedienteGobierno::distinct()->pluck('zona_id'))->orderBy('nombre')->get(),
            'lotes'       => Lote::orderBy('codigo')->get(),
        ]);
    }

    private function grupos(Collection $exps): array
    {
        $r = ['total' => $exps->count()];
        foreach (self::GRUPOS as $g => $estados) $r[$g] = $exps->whereIn('estado', $estados)->count();
        $r['pct_completo'] = $r['total'] ? round($r['COMPLETO'] / $r['total'] * 100, 1) : 0;
        return $r;
    }

    /** % de expedientes que presentan cada documento obligatorio (sobre los que lo exigen). */
    private function cumplimiento(Collection $exps, Checklist $checklist): Collection
    {
        $docs = [];
        foreach ($exps as $e) {
            foreach ($checklist->documentos($e)->where('exigido', true) as $it) {
                $id = $it['tipo']->id;
                $docs[$id] ??= ['tipo' => $it['tipo'], 'exigido' => 0, 'presentado' => 0, 'observado' => 0, 'no_disponible' => 0];
                $docs[$id]['exigido']++;
                if (in_array($it['estado'], ['cargado', 'conforme'])) $docs[$id]['presentado']++;
                if ($it['estado'] === 'observado') $docs[$id]['observado']++;
                if ($it['estado'] === 'no_disponible') $docs[$id]['no_disponible']++;
            }
        }
        return collect($docs)->map(fn($d) => $d + ['pct' => $d['exigido'] ? round($d['presentado'] / $d['exigido'] * 100, 1) : 0])
            ->sortBy('pct')->values();
    }
}
