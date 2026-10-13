<?php
namespace App\Contracts\Analitica;

use Illuminate\Support\Carbon;

/** Alertas de control: patrones inusuales para Jefatura y Auditoría. */
interface DetectorAnomalias
{
    /**
     * @return array[] Cada alerta: tipo, severidad, entidad_tipo, entidad_id, entidad_nombre, zona_id,
     *                 titulo, descripcion, metrica, valor_observado, valor_referencia, unidad, serie, detectada_en
     */
    public function detectar(Carbon $hoy): array;
}
