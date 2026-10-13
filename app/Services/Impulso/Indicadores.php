<?php
namespace App\Services\Impulso;

use App\Http\Controllers\Impulso\RevisionController;
use App\Models\{DevolucionCofide, ExpedienteGobierno, Lote, PagoPrograma};
use Illuminate\Support\Carbon;

/** Indicadores del módulo Impulso MyPerú (lotes, honramiento, documentación, pagos y devolución). */
class Indicadores
{
    public function resumen(?Carbon $mes = null): array
    {
        $mes ??= now();
        $ini = $mes->copy()->startOfMonth();
        $fin = $mes->copy()->endOfMonth();

        $lotesMes = Lote::whereBetween('fecha_corte', [$ini, $fin])->get();
        $honrados = ExpedienteGobierno::with('zona')->where('estado_honramiento', 'Honrado')->get();
        $pagosMes = PagoPrograma::whereBetween('fecha_pago', [$ini, $fin])->get();
        $revMes = $pagosMes->whereIn('estado', ['Agregado', 'Incluido en devolución', 'Devuelto']);
        $completo = RevisionController::GRUPOS['COMPLETO'];

        // Pagos (capital) por día del mes, de todos los meses: muestra la concentración hacia el cierre
        $porDia = array_fill(1, 31, 0.0);
        foreach (PagoPrograma::get(['fecha_pago', 'capital']) as $p) $porDia[(int) $p->fecha_pago->day] += (float) $p->capital;

        $porVencer = DevolucionCofide::where('estado', '!=', 'Devuelta')->orderBy('fecha_limite')->first();

        return [
            'mes'              => $ini,
            'lotes_mes'        => $lotesMes->count(),
            'lotes_estados'    => $lotesMes->countBy('estado')->all(),
            'creditos_mes'     => $lotesMes->sum('registros_rct'),
            'honrado_total'    => Calculos::sumar($honrados->pluck('monto_honrado')),
            'creditos_honrados'=> $honrados->count(),
            'honrado_mes'      => Calculos::sumar($honrados->filter(fn($e) => $e->fecha_honramiento?->between($ini, $fin))->pluck('monto_honrado')),
            'pendientes_zona'  => $honrados->whereNotIn('estado', $completo)->groupBy(fn($e) => $e->zona->nombre ?? 'Sin zona')
                                    ->map(fn($g, $z) => ['zona' => $z, 'pendientes' => $g->count(),
                                        'total' => $honrados->filter(fn($e) => ($e->zona->nombre ?? 'Sin zona') === $z)->count()])
                                    ->sortByDesc('pendientes')->values(),
            'reversion_mes'    => Calculos::sumar($revMes->pluck('reversion')),
            'pagos_mes'        => $pagosMes->count(),
            'capital_mes'      => Calculos::sumar($pagosMes->pluck('capital')),
            'sin_reversion_mes'=> $pagosMes->where('estado', 'Sin reversión')->count(),
            'por_vencer'       => $porVencer,
            'por_dia'          => array_map(fn($v) => round($v, 2), array_values($porDia)),
        ];
    }
}
