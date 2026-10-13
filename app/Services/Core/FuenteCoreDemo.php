<?php
namespace App\Services\Core;

use App\Contracts\FuenteCore;
use App\Models\{CoreCredito, CorePago};
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/** Lee el core desde las tablas espejo core_creditos y core_pagos (modo demo). */
class FuenteCoreDemo implements FuenteCore
{
    public function creditosPrograma(string $programa): Collection
    {
        return CoreCredito::where('programa', $programa)->get();
    }

    public function credito(string $creditoSicmact): ?object
    {
        return CoreCredito::where('credito_sicmact', $creditoSicmact)->first();
    }

    public function pagosDesde(Carbon $desde): Collection
    {
        return CorePago::where('registrado_en', '>=', $desde)->orderBy('registrado_en')->orderBy('id')->get();
    }

    public function actualizadoEn(): ?Carbon
    {
        $c = CoreCredito::max('actualizado_en');
        $p = CorePago::max('registrado_en');
        $valores = array_filter([$c, $p]);
        return $valores ? Carbon::parse(max($valores)) : null;
    }
}
