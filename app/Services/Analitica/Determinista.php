<?php
namespace App\Services\Analitica;

/** Variación determinista: el mismo texto produce siempre el mismo número (sin rand()). */
final class Determinista
{
    /** Número entre 0 y 1 a partir de una clave. */
    public static function u(string $clave): float
    {
        return (crc32($clave) % 100000) / 100000;
    }

    /** Entero entre $min y $max a partir de una clave. */
    public static function entre(string $clave, int $min, int $max): int
    {
        return $min + (int) floor(self::u($clave) * ($max - $min + 1 - 1e-9));
    }
}
