<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use App\Models\{SeguimientoBeneficio, Zona};

/** Análisis de recaída: ¿el beneficio funcionó? (comportamiento a 30, 60 y 90 días). */
class RecaidaController extends Controller
{
    public function index(Request $request)
    {
        $u = $request->user();
        $zonaId = $u->nivel() === 1 ? $u->tienda?->zona_id : ($request->integer('zona_id') ?: null);
        $hoy = now()->startOfDay();

        $seg = SeguimientoBeneficio::with('cliente.tienda', 'asesor', 'propuesta.detalleCuotas')
            ->when($zonaId, fn($q) => $q->whereHas('cliente', fn($c) => $c->where('zona_id', $zonaId)))
            ->get();
        $con90 = $seg->whereNotNull('estado_90');

        return view('analitica.recaida', [
            'zonaId'   => $zonaId,
            'zonaFija' => $u->nivel() === 1,
            'zonas'    => Zona::whereIn('id', $seg->pluck('cliente.zona_id')->unique())->orderBy('nombre')->get(),
            'kpi'      => [
                'sostenida'   => $this->sostenida($con90, 'estado_90'),
                'base'        => $con90->count(),
                'recayeron'   => $con90->where('estado_90', 'Recayó')->count(),
                'leve'        => $con90->where('estado_90', 'Atraso leve')->count(),
                'sostenida30' => $this->sostenida($seg->whereNotNull('estado_30'), 'estado_30'),
                'seguimiento' => $seg->whereNull('estado_90')->whereNull('fecha_recaida')->count(),
            ],
            'curva'    => $this->curva($seg, $hoy),
            'tipos'    => $this->porTipo($con90),
            'riesgo'   => $seg->whereNull('estado_90')->whereNull('fecha_recaida')
                            ->sortByDesc('riesgo_recaida')->take(10)->values(),
            'hoy'      => $hoy,
        ]);
    }

    /** % de beneficios que siguen al día (o con atraso leve) en el plazo indicado. */
    private function sostenida(Collection $c, string $campo): ?float
    {
        return $c->count() ? round($c->where($campo, '!=', 'Recayó')->count() / $c->count() * 100, 1) : null;
    }

    /** Recuperación sostenida a 30, 60 y 90 días por mes del beneficio (cohortes). */
    private function curva(Collection $seg, $hoy): array
    {
        $out = [];
        foreach ($seg->groupBy(fn($s) => $s->fecha_beneficio->format('Y-m'))->sortKeys() as $mes => $g) {
            $m = \Illuminate\Support\Carbon::parse($mes.'-01');
            if ($m->isSameMonth($hoy)) continue;   // el mes en curso aún no tiene 30 días cumplidos
            $out[] = [
                'mes' => ucfirst($m->translatedFormat('M Y')),
                'n'   => $g->count(),
                'd30' => $this->sostenida($g->whereNotNull('estado_30'), 'estado_30'),
                'd60' => $this->sostenida($g->whereNotNull('estado_60'), 'estado_60'),
                'd90' => $this->sostenida($g->whereNotNull('estado_90'), 'estado_90'),
            ];
        }
        return $out;
    }

    /** % que recayó a 90 días por tipo de beneficio. */
    private function porTipo(Collection $con90): Collection
    {
        return $con90->groupBy(fn($s) => self::tipo($s))->map(fn($g, $tipo) => [
            'tipo'    => $tipo,
            'n'       => $g->count(),
            'recayo'  => round($g->where('estado_90', 'Recayó')->count() / $g->count() * 100, 1),
        ])->sortBy('recayo')->values();
    }

    public static function tipo(SeguimientoBeneficio $s): string
    {
        return match ($s->tipo_negociacion) {
            'cuotas'        => ($s->propuesta->detalleCuotas->pone_al_dia ?? false) ? 'Pago de cuotas que se pone al día' : 'Pago de algunas cuotas vencidas',
            'extrajudicial' => 'Extrajudicial / judicial',
            'castigada'     => 'Cartera castigada',
            'focmac'        => 'Cartera FOCMAC',
            default         => ucfirst($s->tipo_negociacion),
        };
    }
}
