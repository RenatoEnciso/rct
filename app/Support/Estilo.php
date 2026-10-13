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

    /** Estados del expediente de honramiento (Programas de Gobierno). */
    public static function badgeHonramiento(?string $estado): string
    {
        return match ($estado) {
            'Conforme'              => 'badge-ok',
            'Revisado por zona'     => 'badge-info',
            'Completo'              => 'badge-info',
            'Incompleto'            => 'badge-pending',
            'Observado'             => 'badge-danger',
            'Incompleto definitivo' => 'badge-rojo',
            default                 => 'badge-gray',   // Pendiente
        };
    }

    /** Ícono y color del estado de un documento del checklist. */
    public static function iconoDocumento(string $estado): array
    {
        return match ($estado) {
            'conforme'      => ['bi-check-circle-fill', 'text-verde', 'Conforme'],
            'cargado'       => ['bi-check-lg', 'text-verde', 'Cargado'],
            'observado'     => ['bi-exclamation-triangle-fill', 'text-mostaza', 'Observado'],
            'no_disponible' => ['bi-slash-circle', 'text-rojo', 'No disponible'],
            default         => ['bi-x-lg', 'text-rojo', 'Falta'],
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

    /** Monto entero con separador de miles por espacio, para tableros (S/ 1 234 567). */
    public static function solesEntero($monto): string
    {
        return 'S/ '.number_format(round((float) $monto), 0, '.', "\u{00A0}");
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
