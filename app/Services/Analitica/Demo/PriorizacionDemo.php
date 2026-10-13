<?php
namespace App\Services\Analitica\Demo;

use App\Contracts\Analitica\ModeloPriorizacion;
use App\Models\ClienteCartera;
use App\Services\Analitica\{CalculadoraRecupero, Determinista};

/**
 * Priorización con fórmula fija (ver docs/REGLAS_DEMO_ANALITICA.md):
 * puntaje = 100 × (0.30·Pago + 0.25·Recuperable + 0.15·Provisión + 0.20·Umbral + 0.10·Historial) ± 5
 */
class PriorizacionDemo implements ModeloPriorizacion
{
    private const BASE_PAGO = ['NORMAL' => 0.85, 'CPP' => 0.75, 'DEFICIENTE' => 0.60, 'DUDOSO' => 0.45, 'PÉRDIDA' => 0.25];
    // Días en los que el crédito pasa a la siguiente calificación
    private const UMBRALES = [31 => 'Deficiente', 61 => 'Dudoso', 121 => 'Pérdida'];
    private const HORARIOS = ['09:00 – 11:00', '11:00 – 13:00', '16:00 – 18:00', '18:00 – 20:00'];

    public function provisionLiberable(ClienteCartera $c): float
    {
        if ($c->estado_cartera === 'CASTIGADO') return 0;
        $tasa = CalculadoraRecupero::tasa($c->calificacion_rcc);
        // Pago de cuotas: normaliza el crédito (pasa a Normal); el resto lo cancela
        if ($c->tipo_negociacion_campana === 'cuotas') $tasa -= CalculadoraRecupero::tasa('NORMAL');
        return round((float) $c->saldo_capital * max(0, $tasa), 2);
    }

    public function evaluar(ClienteCartera $c, array $ctx): array
    {
        $saldo = (float) $c->saldo_capital;
        $provision = $this->provisionLiberable($c);
        $recuperable = $this->montoRecuperable($c);

        $pago = $this->pago($c);

        [$umbral, $siguiente, $diasRestantes] = $this->umbral($c->dias_atraso);
        $comp = [
            'pago'      => round($pago, 3),
            'provision' => round($this->percentil($ctx['provisiones'], $provision), 3),
            'umbral'    => round($umbral, 3),
            'recuperable' => round($this->percentil($ctx['recuperables'], $recuperable * $pago), 3),
            'saldo'     => round($this->percentil($ctx['saldos'], $saldo), 3),
            'historial' => isset($ctx['cumplidos'][$c->id]) ? 1 : 0,
        ];

        $ajuste = (int) round(Determinista::u('prio'.$c->id) * 10 - 5);
        $score  = (int) round(100 * (0.30 * $comp['pago'] + 0.25 * $comp['recuperable'] + 0.15 * $comp['provision']
                                   + 0.20 * $comp['umbral'] + 0.10 * $comp['historial'])) + $ajuste;
        $score  = max(1, min(99, $score));

        $seg = config('rct.analitica.segmentos');

        return [
            'score'               => $score,
            'segmento'            => $score >= $seg['Alta'] ? 'Alta' : ($score >= $seg['Media'] ? 'Media' : 'Baja'),
            'probabilidad_pago'   => max(5, min(95, round($pago * 100 + $ajuste, 1))),
            'monto_recuperable'   => $recuperable,
            'provision_liberable' => $provision,
            'motivos'             => $this->motivos($comp, $siguiente, $diasRestantes, $c, $ctx['hoy']),
            'accion_sugerida'     => $this->accion($comp, $c),
            'mejor_horario'       => $c->tipo_cliente === 'JURIDICO'
                                       ? self::HORARIOS[Determinista::entre('hor'.$c->id, 0, 1)]
                                       : self::HORARIOS[Determinista::entre('hor'.$c->id, 0, 3)],
            'componentes'         => $comp,
        ];
    }

    /** Cuotas: recupera las cuotas vencidas (25 % del saldo); el resto: saldo con el descuento máximo de su lineamiento. */
    public function montoRecuperable(ClienteCartera $c): float
    {
        $saldo = (float) $c->saldo_capital;
        if ($c->tipo_negociacion_campana === 'cuotas') return round($saldo * 0.25, 2);
        $pct = preg_match('/hasta (\d+)% de saldo capital/', (string) $c->lineamiento_campana, $m) ? $m[1] / 100 : 0;
        return round($saldo * (1 - $pct), 2);
    }

    /** Probabilidad base de pago (0–1) por calificación, usos previos y estado de cartera. */
    public function pago(ClienteCartera $c): float
    {
        $p = (self::BASE_PAGO[$c->calificacion_rcc] ?? 0.4) - 0.05 * $c->conc_operacion_12m
           - ($c->estado_cartera === 'JUDICIAL' ? 0.10 : 0) - ($c->estado_cartera === 'CASTIGADO' ? 0.15 : 0);
        return max(0.05, min(0.95, $p));
    }

    /** 1 si faltan 15 días o menos para pasar a la siguiente calificación; baja a 0 en los 30 días siguientes. */
    private function umbral(int $dias): array
    {
        foreach (self::UMBRALES as $limite => $nombre) {
            if ($dias < $limite) {
                $resto = $limite - $dias;
                return [$resto <= 15 ? 1 : max(0, 1 - ($resto - 15) / 30), $nombre, $resto];
            }
        }
        return [0, null, null];
    }

    private function percentil(array $ordenados, float $valor): float
    {
        $n = count($ordenados);
        if ($n < 2) return 0.5;
        $lo = 0; $hi = $n;
        while ($lo < $hi) { $mid = intdiv($lo + $hi, 2); $ordenados[$mid] < $valor ? $lo = $mid + 1 : $hi = $mid; }
        return min(1, $lo / ($n - 1));
    }

    private function motivos(array $c, ?string $siguiente, ?int $dias, ClienteCartera $cli, $hoy): array
    {
        $cand = [];
        if ($siguiente && $c['umbral'] >= 0.6) {
            // Si el crédito ya tiene esa calificación (o una peor), se menciona el umbral de días
            $orden = ['NORMAL' => 0, 'CPP' => 1, 'DEFICIENTE' => 2, 'DUDOSO' => 3, 'PÉRDIDA' => 4];
            $peor = ($orden[$cli->calificacion_rcc] ?? 0) >= ($orden[mb_strtoupper($siguiente)] ?? 9);
            $limite = array_search($siguiente, self::UMBRALES);
            $cand[] = [$c['umbral'] + 0.5, ($peor ? "Cerca de los {$limite} días de atraso" : "Cerca de pasar a {$siguiente}")
                                           .' ('.$dias.($dias === 1 ? ' día)' : ' días)')];
        }
        if ($c['historial'])                   $cand[] = [1.2, 'Cumplió última promesa'];
        if ($c['saldo'] >= 0.75)               $cand[] = [$c['saldo'], 'Saldo alto'];
        if ($c['provision'] >= 0.75)           $cand[] = [$c['provision'], 'Libera provisión'];
        if ($c['pago'] >= 0.65)                $cand[] = [$c['pago'], 'Alta probabilidad de pago'];
        $cierre = (int) $hoy->copy()->startOfDay()->diffInDays($hoy->copy()->endOfMonth()->startOfDay());
        if ($cierre <= 5)                      $cand[] = [0.9, $cierre === 0 ? 'Cierre de mes hoy' : 'Cierre de mes en '.$cierre.($cierre === 1 ? ' día' : ' días')];
        if ($cli->conc_operacion_12m == 0)     $cand[] = [0.3, 'Sin beneficios en 12 meses'];
        if ($cli->garante_celular)             $cand[] = [0.2, 'Garante contactable'];
        if (count($cand) < 2)                  $cand[] = [0.1, 'Datos de contacto vigentes'];

        usort($cand, fn($a, $b) => $b[0] <=> $a[0]);
        return array_column(array_slice($cand, 0, 3), 1);
    }

    private function accion(array $c, ClienteCartera $cli): string
    {
        if ($c['saldo'] >= 0.75 && $c['pago'] < 0.45) return 'Visitar';
        if ($cli->tipo_negociacion_campana === 'cuotas') return $c['pago'] >= 0.7 ? 'Llamar' : 'Ofrecer pago de cuotas';
        if ($c['pago'] >= 0.7) return 'Llamar';
        return 'Ofrecer cancelación';
    }
}
