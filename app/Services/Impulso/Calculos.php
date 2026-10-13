<?php
namespace App\Services\Impulso;

use Illuminate\Support\Carbon;

/**
 * Cálculos del programa Impulso MyPerú (puros: sin base de datos, cubiertos por tests unitarios).
 * Montos con BCMath y redondeo a 2 decimales (half-up) solo en el resultado.
 */
final class Calculos
{
    /** Redondeo half-up a $dec decimales de un número en texto (BCMath). */
    public static function redondear(string $valor, int $dec = 2): string
    {
        $ajuste = '0.'.str_repeat('0', $dec).'5';
        return str_starts_with($valor, '-') ? bcsub($valor, $ajuste, $dec) : bcadd($valor, $ajuste, $dec);
    }

    /** Monto a honrar (estimado de RCT) = (saldo insoluto + interés devengado) × factor de cobertura. */
    public static function montoHonrar(string|float $saldo, string|float $interes, string|float $factor): string
    {
        return self::redondear(bcmul(bcadd((string) $saldo, (string) $interes, 4), (string) $factor, 8));
    }

    /** Reversión de un pago = capital pagado × factor de cobertura (redondeada por pago). */
    public static function reversion(string|float $capital, string|float $factor): string
    {
        return self::redondear(bcmul((string) $capital, (string) $factor, 8));
    }

    /**
     * Un crédito está honrado a la fecha del pago si tiene fecha de honramiento y el pago es de ese día o posterior.
     * Los pagos entre la solicitud y el honramiento no generan reversión.
     */
    public static function honradoALaFecha(?Carbon $fechaHonramiento, Carbon $fechaPago, bool $incluyeDia = true): bool
    {
        if (!$fechaHonramiento) return false;
        $h = $fechaHonramiento->copy()->startOfDay();
        $p = $fechaPago->copy()->startOfDay();
        return $incluyeDia ? $p->gte($h) : $p->gt($h);
    }

    /** Suma exacta de montos (BCMath). */
    public static function sumar(iterable $montos): string
    {
        $t = '0.00';
        foreach ($montos as $m) $t = bcadd($t, (string) $m, 2);
        return $t;
    }

    /**
     * Pari passu de un crédito a partir de sus pagos del programa.
     * @param iterable $pagos arrays u objetos con capital, reversion, estado
     * @return array capital_pagado, reversion_acumulada, devuelta, pendiente
     */
    public static function pariPassu(iterable $pagos): array
    {
        $capital = $acum = $dev = '0.00';
        foreach ($pagos as $p) {
            $p = (object) $p;
            if (in_array($p->estado, ['Agregado', 'Incluido en devolución', 'Devuelto'], true)) {
                $capital = bcadd($capital, (string) $p->capital, 2);
                $acum = bcadd($acum, (string) $p->reversion, 2);
                if ($p->estado === 'Devuelto') $dev = bcadd($dev, (string) $p->reversion, 2);
            }
        }
        return ['capital_pagado' => $capital, 'reversion_acumulada' => $acum, 'devuelta' => $dev, 'pendiente' => bcsub($acum, $dev, 2)];
    }
}
