<?php
namespace App\Services;

use App\Models\{ClienteCartera, PropuestaFud, User};

/**
 * Asigna automáticamente el aprobador de un FUD según el nivel de autonomía
 * del crédito. Lógica simple para la demo (sin casos borde normativos):
 *   nivel 1 = Coordinador Zonal, 2 = Jefe de Recuperaciones, 3 = Gerente de División.
 */
class AsignadorAprobador
{
    private const NIVEL_POR_AUTONOMIA = [
        'CZR'                    => 1,
        'Jefe de Recuperaciones' => 2,
        'Gerente de División'    => 3,
    ];

    public function nivelRequerido(ClienteCartera $cliente): int
    {
        $porCartera = self::NIVEL_POR_AUTONOMIA[$cliente->autonomia] ?? 1;
        $porRegla   = ($cliente->esMayor3UIT()
            || in_array($cliente->estado_cartera, ['JUDICIAL', 'EXTRAJUDICIAL'])) ? 2 : 1;

        return max($porCartera, $porRegla);
    }

    /**
     * Aprobador del nivel requerido (o el siguiente nivel si no hay nadie),
     * priorizando la misma zona del crédito y la menor carga pendiente.
     * Nunca devuelve al propio solicitante.
     */
    public function asignar(ClienteCartera $cliente, int $solicitanteId): ?User
    {
        $nivel = $this->nivelRequerido($cliente);

        for ($n = $nivel; $n <= 3; $n++) {
            $candidatos = User::with('tienda')
                ->where('id', '!=', $solicitanteId)
                ->whereHas('rol', fn($q) => $q->where('grupo', 'aprobador')->where('nivel', $n))
                ->get();

            if ($candidatos->isEmpty()) continue;

            return $candidatos
                ->sortBy(fn(User $u) => [
                    $u->tienda?->zona_id == $cliente->zona_id ? 0 : 1,
                    PropuestaFud::porAprobador($u->id)->enRevision()->count(),
                    $u->id,
                ])
                ->first();
        }

        return null;
    }
}
