<?php
namespace App\Support;

/** Presentación consistente de estados en todas las pantallas. */
class Estilo
{
    /** Clase de badge para estados de FUD, sustentos, expedientes y validación. */
    public static function badge(?string $estado): string
    {
        return match (mb_strtolower((string) $estado)) {
            'aprobado', 'completo', 'conforme', 'conforme manual' => 'badge-ok',
            'en revisión'                                       => 'badge-info',
            'observado', 'incompleto'                           => 'badge-pending',
            'rechazado'                                         => 'badge-danger',
            default                                             => 'badge-gray',
        };
    }

    /** Ícono (Bootstrap Icons) asociado al estado. */
    public static function icono(?string $estado): string
    {
        return match (mb_strtolower((string) $estado)) {
            'aprobado', 'completo', 'conforme', 'conforme manual' => 'bi-check-circle',
            'en revisión'                                       => 'bi-hourglass-split',
            'observado', 'incompleto'                           => 'bi-exclamation-circle',
            'rechazado'                                         => 'bi-x-circle',
            default                                             => 'bi-circle',
        };
    }

    /** Nombre legible del tipo de documento. */
    public static function tipoDoc(?string $tipo): string
    {
        return $tipo === 'Informe-Propuesta SK>3UIT' ? 'Informe-Propuesta (SK > 3 UIT)' : (string) $tipo;
    }

    public static function soles($monto): string
    {
        return 'S/ '.number_format((float) $monto, 2);
    }

    /** Monto abreviado para indicadores (S/ 1.25 M, S/ 845.3 mil). */
    public static function solesCorto($monto): string
    {
        $m = (float) $monto;
        if ($m >= 1_000_000) return 'S/ '.number_format($m / 1_000_000, 2).' M';
        if ($m >= 1_000)     return 'S/ '.number_format($m / 1_000, 1).' mil';
        return 'S/ '.number_format($m, 2);
    }

    public static function tamano(?int $bytes): string
    {
        if (!$bytes) return '—';
        return $bytes >= 1048576 ? number_format($bytes / 1048576, 1).' MB' : number_format($bytes / 1024, 0).' KB';
    }
}
