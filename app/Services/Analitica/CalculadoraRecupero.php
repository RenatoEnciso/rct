<?php
namespace App\Services\Analitica;

use App\Models\{ClienteCartera, PropuestaFud};

/**
 * Montos en soles de un FUD pagado a partir de su detalle (% de descuento) y del crédito.
 * Deuda referencial: intereses y mora acumulados según los días de atraso.
 */
class CalculadoraRecupero
{
    public static function tasa(?string $calificacion): float
    {
        return (float) (config('rct.analitica.tasas_provision')[$calificacion] ?? 1.0);
    }

    /** Intereses compensatorios y mora acumulados (referencial). */
    public static function deuda(ClienteCartera $c): array
    {
        $anios = $c->dias_atraso / 365;
        return [
            'interes' => round($c->saldo_capital * min(0.30, $anios * 0.20), 2),
            'mora'    => round($c->saldo_capital * min(0.08, $anios * 0.04), 2),
        ];
    }

    /** @return array recuperado_capital, recuperado_interes, condonado_capital, condonado_interes, condonado_mora, provision_liberada */
    public function calcular(PropuestaFud $fud): array
    {
        $c     = $fud->cliente;
        $saldo = (float) $c->saldo_capital;
        $tasa  = self::tasa($c->calificacion_rcc);
        ['interes' => $interes, 'mora' => $mora] = self::deuda($c);
        $castigado = $c->estado_cartera === 'CASTIGADO';
        // Judicial, extrajudicial, castigada y FOCMAC: intereses y mora están en suspenso (no reconocidos como ingreso),
        // por lo que su exoneración no es una pérdida y no se cuenta como monto condonado
        $suspenso = $c->estado_cartera !== 'VENCIDO';

        switch ($fud->tipo_negociacion) {
            case 'cuotas':
                $d = $fud->detalleCuotas;
                $n = $d ? $d->cuota_hasta - $d->cuota_desde + 1 : 1;
                $capital = min($saldo, round((float) $c->monto_desembolso / 24 * $n, 2));
                $intCuotas  = $capital * 0.28;
                $moraCuotas = $capital * 0.06;
                $dInt  = ($d->desc_interes ?? 0) / 100;
                $dMora = ($d->desc_moratorio ?? 0) / 100;
                $r = [
                    'recuperado_capital' => $capital,
                    'recuperado_interes' => $intCuotas * (1 - $dInt) + $moraCuotas * (1 - $dMora),
                    'condonado_capital'  => 0,
                    'condonado_interes'  => $intCuotas * $dInt,
                    'condonado_mora'     => $moraCuotas * $dMora,
                    // Ponerse al día devuelve el crédito a Normal
                    'provision_liberada' => ($d->pone_al_dia ?? false) ? $saldo * max(0, $tasa - self::tasa('NORMAL')) : 0,
                ];
                break;

            case 'cancelacion':
                $d = $fud->detalleCancelacion;
                $pagado = (float) ($d->monto_a_pagar ?? $saldo);
                $dInt   = ($d->desc_interes ?? 100) / 100;
                $r = [
                    'recuperado_capital' => $pagado,
                    'recuperado_interes' => $interes * (1 - $dInt),
                    'condonado_capital'  => max(0, $saldo - $pagado),
                    'condonado_interes'  => $suspenso ? 0 : $interes * $dInt,
                    'condonado_mora'     => $mora,
                    'provision_liberada' => $castigado ? 0 : $saldo * $tasa,
                ];
                break;

            default:   // extrajudicial / judicial, castigada, FOCMAC: beneficio sobre el saldo capital
                $pct = (float) ($fud->detalleJudicial->beneficio_pct_sk ?? 70) / 100;
                $r = [
                    'recuperado_capital' => $saldo * (1 - $pct),
                    'recuperado_interes' => 0,
                    'condonado_capital'  => $saldo * $pct,
                    'condonado_interes'  => $suspenso ? 0 : $interes,
                    'condonado_mora'     => $suspenso ? 0 : $mora,
                    // La cartera castigada ya está fuera de balance: el recupero es ingreso, no libera provisión
                    'provision_liberada' => $castigado ? 0 : $saldo * $tasa,
                ];
        }

        return array_map(fn($v) => round($v, 2), $r);
    }
}
