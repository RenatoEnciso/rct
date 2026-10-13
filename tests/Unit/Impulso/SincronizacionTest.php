<?php

namespace Tests\Unit\Impulso;

use App\Services\Impulso\SincronizadorCore;
use PHPUnit\Framework\TestCase;

/** Idempotencia de la sincronización con el core: un pago (id_core) se procesa una sola vez. */
class SincronizacionTest extends TestCase
{
    private function pago(string $id): object
    {
        return (object) ['id_core' => $id, 'credito_sicmact' => '111', 'capital' => '100.00'];
    }

    public function test_descarta_los_pagos_ya_procesados(): void
    {
        $nuevos = SincronizadorCore::filtrarNuevos([$this->pago('A'), $this->pago('B'), $this->pago('C')], ['A', 'C']);
        $this->assertSame(['B'], array_column($nuevos, 'id_core'));
    }

    public function test_descarta_repetidos_dentro_de_la_misma_lectura(): void
    {
        $nuevos = SincronizadorCore::filtrarNuevos([$this->pago('A'), $this->pago('A'), $this->pago('B')], []);
        $this->assertSame(['A', 'B'], array_column($nuevos, 'id_core'));
    }

    public function test_dos_sincronizaciones_seguidas_no_duplican(): void
    {
        $leidos = [$this->pago('A'), $this->pago('B')];
        $primera = SincronizadorCore::filtrarNuevos($leidos, []);
        $procesados = array_column($primera, 'id_core');
        $segunda = SincronizadorCore::filtrarNuevos(array_merge($leidos, [$this->pago('C')]), $procesados);
        $this->assertCount(2, $primera);
        $this->assertSame(['C'], array_column($segunda, 'id_core'));
        $this->assertSame([], SincronizadorCore::filtrarNuevos($leidos, ['A', 'B']));
    }
}
