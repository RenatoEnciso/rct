<?php
namespace App\Contracts\Analitica;

use App\Models\ClienteCartera;

/** Modelo de priorización de cartera: a quién gestionar primero. */
interface ModeloPriorizacion
{
    /**
     * @param array $contexto saldos, provisiones, recuperables (float[] ordenados),
     *                        cumplidos (cliente_id => true), hoy (Carbon)
     * @return array score, segmento, probabilidad_pago, monto_recuperable, provision_liberable,
     *               motivos, accion_sugerida, mejor_horario, componentes
     */
    public function evaluar(ClienteCartera $cliente, array $contexto): array;

    /** Provisión que se libera si el crédito se normaliza o cancela. */
    public function provisionLiberable(ClienteCartera $cliente): float;

    /** Monto que se recuperaría si el cliente acepta la negociación sugerida. */
    public function montoRecuperable(ClienteCartera $cliente): float;

    /** Probabilidad de pago (0–1). */
    public function pago(ClienteCartera $cliente): float;
}
