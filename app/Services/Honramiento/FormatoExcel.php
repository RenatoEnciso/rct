<?php
namespace App\Services\Honramiento;

use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

/** Columnas del Excel de revisión de expedientes (mismo orden que usa el área). */
class FormatoExcel
{
    public const DOCUMENTOS = [
        1  => 'DNI / Reporte tributario con Ficha RUC',
        2  => 'Hoja resumen',
        3  => 'Formulario para el conocimiento del cliente',
        4  => 'DJ del programa – Anexo 02',
        5  => 'DJ patrimonial',
        6  => 'Cargo del contrato de crédito',
        7  => 'Cronograma de pagos',
        8  => 'Pagaré',
        9  => 'Clasificación regulatoria (RCC, Experian)',
        10 => 'Evidencia de ingresos (fotos y recibos)',
    ];

    public const ANTES = ['GESTIÓN', 'FECHA DE REVISIÓN ACTUAL', 'FECHA DE REVISIÓN POR ZONAS', 'ID', 'DNI', 'CRÉDITO SICMACT',
                          'CUENTA BT', 'OPERACIÓN', 'ZONA', 'TIENDA', 'CLIENTE', 'MONTO HONRADO', 'LOTE', 'ESTADO'];
    public const DESPUES = ['N° DOCUMENTOS FALTANTES', 'OBSERVACIONES'];

    public static function encabezados(): array
    {
        return array_merge(self::ANTES, array_values(self::DOCUMENTOS), self::DESPUES);
    }

    /** Clave normalizada de un encabezado: mayúsculas, sin tildes ni símbolos. */
    public static function clave(?string $texto): string
    {
        $t = Str::upper(Str::ascii((string) $texto));
        $t = preg_replace('/[^A-Z0-9 ]/', ' ', $t);
        return trim(preg_replace('/\s+/', ' ', $t));
    }

    /** "32.605,88" → 32605.88 · "32,605.88" → 32605.88 · valores numéricos se respetan. */
    public static function monto($v): ?float
    {
        if ($v === null || $v === '') return null;
        if (is_int($v) || is_float($v)) return round((float) $v, 2);
        $s = preg_replace('/[^\d,.\-]/', '', (string) $v);
        if ($s === '' || $s === '-') return null;
        $ultComa = strrpos($s, ','); $ultPunto = strrpos($s, '.');
        if ($ultComa !== false && $ultPunto !== false) {
            $s = $ultComa > $ultPunto ? str_replace(',', '.', str_replace('.', '', $s)) : str_replace(',', '', $s);
        } elseif ($ultComa !== false) {
            // Solo comas: decimal si tiene 1 o 2 dígitos al final; si no, separador de miles
            $s = preg_match('/,\d{1,2}$/', $s) ? str_replace(',', '.', str_replace('.', '', $s)) : str_replace(',', '', $s);
        } elseif ($ultPunto !== false && substr_count($s, '.') > 1) {
            $s = str_replace('.', '', $s);   // 1.234.567
        }
        return is_numeric($s) ? round((float) $s, 2) : null;
    }

    /** Texto sin pérdida: números grandes sin notación científica ni decimales. */
    public static function texto($v): string
    {
        if (is_float($v) || is_int($v)) return sprintf('%.0f', $v);
        return trim((string) $v);
    }

    public static function fecha($v): ?Carbon
    {
        if ($v === null || $v === '') return null;
        if (is_numeric($v)) {
            return Carbon::instance(ExcelDate::excelToDateTimeObject((float) $v))->startOfDay();
        }
        foreach (['d/m/Y', 'd-m-Y', 'Y-m-d', 'd/m/y'] as $f) {
            try { return Carbon::createFromFormat($f, trim((string) $v))->startOfDay(); } catch (\Throwable) {}
        }
        return null;
    }

    public static function estadoExcel(?string $v): ?string
    {
        return match (self::clave($v)) {
            'COMPLETO'              => 'Completo',
            'INCOMPLETO'            => 'Incompleto',
            'INCOMPLETO DEFINITIVO' => 'Incompleto definitivo',
            'PENDIENTE'             => 'Pendiente',
            ''                      => null,
            default                 => 'desconocido',
        };
    }
}
