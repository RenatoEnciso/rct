<?php
namespace App\Services\Impulso;

/**
 * Cruce de la devolución calculada por RCT con la notificación TXT de COFIDE (puro: sin base de datos).
 * Formato del TXT (supuesto, configurable en rct.impulso.txt): una línea de encabezado y una por crédito,
 * campos separados por "|": CODIGO_IFI|PERIODO|NRO_LOTE|NRO_CREDITO|TIPO_DOC|NRO_DOC|CAPITAL_RECUPERADO|FACTOR_COBERTURA|MONTO_A_DEVOLVER
 */
final class CruceTxt
{
    public const CAMPOS = ['CODIGO_IFI', 'PERIODO', 'NRO_LOTE', 'NRO_CREDITO', 'TIPO_DOC', 'NRO_DOC', 'CAPITAL_RECUPERADO', 'FACTOR_COBERTURA', 'MONTO_A_DEVOLVER'];

    /**
     * Lee el TXT y devuelve [nro_credito => [monto, capital, factor, documento, lote]] (montos como texto con 2 decimales).
     * @throws \InvalidArgumentException si falta una columna o un monto no es válido
     */
    public static function leer(string $contenido, string $separador = '|', string $decimal = '.'): array
    {
        $lineas = array_values(array_filter(array_map('trim', preg_split('/\R/', ltrim($contenido, "\xEF\xBB\xBF"))), fn($l) => $l !== ''));
        if (!$lineas) throw new \InvalidArgumentException('El archivo TXT está vacío.');
        $cab = array_map(fn($c) => strtoupper(trim($c)), explode($separador, $lineas[0]));
        foreach (['NRO_CREDITO', 'MONTO_A_DEVOLVER'] as $req) {
            if (!in_array($req, $cab, true)) throw new \InvalidArgumentException("Falta la columna {$req} en el encabezado del TXT.");
        }
        $idx = array_flip($cab);
        $num = function (?string $v, int $linea) use ($decimal) {
            $v = trim((string) $v);
            if ($decimal === ',') $v = str_replace(['.', ','], ['', '.'], $v); else $v = str_replace(',', '', $v);
            if (!preg_match('/^-?\d+(\.\d+)?$/', $v)) throw new \InvalidArgumentException("Monto no válido en la línea {$linea} del TXT.");
            return Calculos::redondear($v);
        };

        $filas = [];
        foreach (array_slice($lineas, 1) as $n => $l) {
            $c = explode($separador, $l);
            $credito = trim($c[$idx['NRO_CREDITO']] ?? '');
            if ($credito === '') continue;
            $monto = $num($c[$idx['MONTO_A_DEVOLVER']] ?? null, $n + 2);
            // Un crédito repetido se suma (COFIDE puede notificar más de una línea por crédito)
            $previo = $filas[$credito]['monto'] ?? '0.00';
            $filas[$credito] = [
                'monto'     => bcadd($previo, $monto, 2),
                'capital'   => isset($idx['CAPITAL_RECUPERADO']) ? $num($c[$idx['CAPITAL_RECUPERADO']] ?? '0', $n + 2) : null,
                'factor'    => isset($idx['FACTOR_COBERTURA']) ? trim($c[$idx['FACTOR_COBERTURA']] ?? '') : null,
                'documento' => isset($idx['NRO_DOC']) ? trim($c[$idx['NRO_DOC']] ?? '') : null,
                'lote'      => isset($idx['NRO_LOTE']) ? trim($c[$idx['NRO_LOTE']] ?? '') : null,
            ];
        }
        return $filas;
    }

    /**
     * Cruza crédito por crédito. Diferencia = RCT − TXT. Debe ser 0 en cada crédito y en el total.
     * @param array $rct [nro_credito => monto]
     * @param array $txt [nro_credito => monto]
     * @return array filas [credito => [rct, txt, diferencia, resultado]], total_rct, total_txt, diferencia, n_diferencias, cuadra
     */
    public static function cruzar(array $rct, array $txt): array
    {
        $filas = [];
        foreach ($rct as $credito => $monto) {
            $m = (string) $monto;
            if (!array_key_exists($credito, $txt)) {
                $filas[$credito] = ['rct' => $m, 'txt' => null, 'diferencia' => Calculos::redondear($m), 'resultado' => 'solo_rct'];
                continue;
            }
            $dif = bcsub($m, (string) $txt[$credito], 2);
            $filas[$credito] = ['rct' => $m, 'txt' => (string) $txt[$credito], 'diferencia' => $dif, 'resultado' => bccomp($dif, '0', 2) === 0 ? 'ok' : 'diferencia'];
        }
        foreach ($txt as $credito => $monto) {
            if (!array_key_exists($credito, $rct)) {
                $filas[$credito] = ['rct' => '0.00', 'txt' => (string) $monto, 'diferencia' => bcsub('0', (string) $monto, 2), 'resultado' => 'solo_txt'];
            }
        }
        $totalRct = Calculos::sumar($rct);
        $totalTxt = Calculos::sumar($txt);
        $nDif = count(array_filter($filas, fn($f) => $f['resultado'] !== 'ok'));
        $diferencia = bcsub($totalRct, $totalTxt, 2);
        return ['filas' => $filas, 'total_rct' => $totalRct, 'total_txt' => $totalTxt, 'diferencia' => $diferencia,
                'n_diferencias' => $nDif, 'cuadra' => $nDif === 0 && bccomp($diferencia, '0', 2) === 0];
    }

    /** Genera el TXT con el formato supuesto (para la demo y para pruebas). */
    public static function escribir(array $lineas, string $separador = '|'): string
    {
        $out = [implode($separador, self::CAMPOS)];
        foreach ($lineas as $l) $out[] = implode($separador, array_map(fn($c) => $l[$c] ?? '', self::CAMPOS));
        return implode("\r\n", $out)."\r\n";
    }
}
