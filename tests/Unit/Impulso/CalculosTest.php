<?php

namespace Tests\Unit\Impulso;

use App\Services\Impulso\Calculos;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;

/** Reversión, regla de honrado y pari passu del programa Impulso MyPerú. */
class CalculosTest extends TestCase
{
    public function test_reversion_es_capital_por_factor_de_cobertura(): void
    {
        $this->assertSame('2000.00', Calculos::reversion('2500.00', '0.8000'));
        $this->assertSame('2250.00', Calculos::reversion(2500, 0.90));
        $this->assertSame('1155.71', Calculos::reversion('1284.12', '0.9000'));   // 1155.708 → 1155.71
    }

    public function test_reversion_redondea_half_up_a_dos_decimales_por_pago(): void
    {
        $this->assertSame('0.99', Calculos::reversion('1.01', '0.9800'));   // 0.9898 → 0.99
        $this->assertSame('1.03', Calculos::reversion('1.05', '0.9800'));   // 1.0290 → 1.03
        $this->assertSame('0.05', Calculos::reversion('0.05', '0.9500'));   // 0.0475 → 0.05
        $this->assertSame('0.00', Calculos::reversion('0', '0.9500'));
    }

    public function test_monto_a_honrar_es_saldo_mas_interes_por_factor(): void
    {
        $this->assertSame('21853.96', Calculos::montoHonrar('22377.60', '626.57', '0.9500'));
        $this->assertSame('0.00', Calculos::montoHonrar('0', '0', '0.98'));
    }

    public function test_honrado_a_la_fecha_del_pago(): void
    {
        $honramiento = Carbon::parse('2026-08-18');
        $this->assertTrue(Calculos::honradoALaFecha($honramiento, Carbon::parse('2026-08-20 10:15')));
        $this->assertTrue(Calculos::honradoALaFecha($honramiento, Carbon::parse('2026-08-18 09:00')), 'El mismo día cuenta como honrado');
        $this->assertFalse(Calculos::honradoALaFecha($honramiento, Carbon::parse('2026-08-18 09:00'), false));
        $this->assertFalse(Calculos::honradoALaFecha($honramiento, Carbon::parse('2026-08-17 18:30')), 'Pago entre la solicitud y el honramiento');
        $this->assertFalse(Calculos::honradoALaFecha(null, Carbon::parse('2026-08-20')), 'Crédito no honrado');
    }

    public function test_pari_passu_solo_suma_reversiones_agregadas_y_separa_lo_devuelto(): void
    {
        $r = Calculos::pariPassu([
            ['capital' => '1000.00', 'reversion' => '900.00', 'estado' => 'Devuelto'],
            ['capital' => '500.00',  'reversion' => '450.00', 'estado' => 'Incluido en devolución'],
            ['capital' => '300.00',  'reversion' => '270.00', 'estado' => 'Agregado'],
            ['capital' => '800.00',  'reversion' => '0.00',   'estado' => 'Sin reversión'],
        ]);
        $this->assertSame('1800.00', $r['capital_pagado']);
        $this->assertSame('1620.00', $r['reversion_acumulada']);
        $this->assertSame('900.00', $r['devuelta']);
        $this->assertSame('720.00', $r['pendiente']);
    }

    public function test_suma_exacta_sin_errores_de_coma_flotante(): void
    {
        $this->assertSame('0.30', Calculos::sumar(['0.10', '0.20']));
        $this->assertSame('100000.01', Calculos::sumar(array_merge(array_fill(0, 10, '10000.00'), ['0.01'])));
    }
}
