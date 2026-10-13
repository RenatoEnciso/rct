<?php
namespace App\Services\Analitica\Demo;

use App\Contracts\Analitica\ModeloRecaida;
use App\Models\{PropuestaFud, Recupero};
use App\Services\Analitica\Determinista;
use Illuminate\Support\Carbon;

/**
 * Recaída con probabilidad fija por tipo de beneficio (ver docs/REGLAS_DEMO_ANALITICA.md).
 * Ponerse al día recae menos que pagar algunas cuotas; las calificaciones peores y los
 * clientes con beneficios previos recaen más.
 */
class RecaidaDemo implements ModeloRecaida
{
    private const MULT_CALIFICACION = ['NORMAL' => 0.7, 'CPP' => 0.8, 'DEFICIENTE' => 1.0, 'DUDOSO' => 1.15, 'PÉRDIDA' => 1.3];

    public function aplica(PropuestaFud $fud): bool
    {
        return $fud->tipo_negociacion !== 'cancelacion';
    }

    public function probabilidad(PropuestaFud $fud): float
    {
        $base = match ($fud->tipo_negociacion) {
            'cuotas'        => ($fud->detalleCuotas->pone_al_dia ?? false) ? 0.12 : 0.38,
            'extrajudicial' => 0.28,
            'castigada'     => 0.20,
            default         => 0.24,   // focmac
        };
        $c = $fud->cliente;
        $p = $base * (self::MULT_CALIFICACION[$c->calificacion_rcc] ?? 1) * ($c->conc_operacion_12m >= 2 ? 1.3 : 1);
        return min(0.85, $p);
    }

    public function evaluar(PropuestaFud $fud, Recupero $recupero, Carbon $hoy): array
    {
        $p = $this->probabilidad($fud);
        $u = Determinista::u('recaida'.$fud->id);
        $recae = $u < $p;
        $leve  = !$recae && $u < $p + 0.10;
        $diaRecaida = $recae ? 12 + (int) floor($u / $p * 78) : null;   // entre el día 12 y el 90

        $inicio = $recupero->fecha_pago->copy();
        $transcurridos = (int) $inicio->diffInDays($hoy);

        $estado = function (int $h) use ($transcurridos, $recae, $leve, $diaRecaida) {
            if ($transcurridos < $h) return null;
            if ($recae && $diaRecaida <= $h) return 'Recayó';
            if ($leve && $h >= 60) return 'Atraso leve';
            return 'Al día';
        };

        $recayo = $recae && $diaRecaida <= $transcurridos;
        $riesgo = $recayo ? 100 : (int) min(95, round($p * 100 * ($leve ? 1.6 : 1) + ($u * 10 - 5)));
        $riesgo = max(3, $riesgo);

        return [
            'estado_30'      => $estado(30),
            'estado_60'      => $estado(60),
            'estado_90'      => $estado(90),
            'fecha_recaida'  => $recayo ? $inicio->copy()->addDays($diaRecaida)->toDateString() : null,
            'riesgo_recaida' => $riesgo,
            'semaforo'       => $recayo || $riesgo >= 40 ? 'rojo' : ($riesgo >= 20 ? 'ambar' : 'verde'),
        ];
    }
}
