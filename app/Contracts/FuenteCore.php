<?php
namespace App\Contracts;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Lectura del core financiero (fuente de verdad de créditos, saldos, días de atraso y pagos).
 * Hoy: FuenteCoreDemo (tablas espejo). Mañana: FuenteCoreReal (vista, API o archivo del core),
 * sin cambiar pantallas ni cálculos.
 */
interface FuenteCore
{
    /** Créditos del programa con sus datos vigentes en el core. */
    public function creditosPrograma(string $programa): Collection;

    /** Datos vigentes de un crédito (o null si no existe). */
    public function credito(string $creditoSicmact): ?object;

    /** Pagos registrados en el core desde el momento indicado (incluido), en orden cronológico. */
    public function pagosDesde(Carbon $desde): Collection;

    /** Momento de la última actualización del core. */
    public function actualizadoEn(): ?Carbon;
}
