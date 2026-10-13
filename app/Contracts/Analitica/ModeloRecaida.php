<?php
namespace App\Contracts\Analitica;

use App\Models\{PropuestaFud, Recupero};
use Illuminate\Support\Carbon;

/** Análisis de recaída: comportamiento del crédito después del beneficio. */
interface ModeloRecaida
{
    /** True si el tipo de beneficio deja el crédito vigente (la cancelación total no puede recaer). */
    public function aplica(PropuestaFud $fud): bool;

    /**
     * @return array estado_30, estado_60, estado_90, fecha_recaida, riesgo_recaida, semaforo
     */
    public function evaluar(PropuestaFud $fud, Recupero $recupero, Carbon $hoy): array;
}
