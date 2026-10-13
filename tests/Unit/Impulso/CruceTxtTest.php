<?php

namespace Tests\Unit\Impulso;

use App\Services\Impulso\CruceTxt;
use PHPUnit\Framework\TestCase;

/** Cruce de la devolución calculada por RCT con la notificación TXT de COFIDE. */
class CruceTxtTest extends TestCase
{
    private function txt(array $lineas): string
    {
        return CruceTxt::escribir(array_map(fn($l) => ['CODIGO_IFI' => '0109', 'PERIODO' => '202608', 'NRO_LOTE' => '008',
            'NRO_CREDITO' => $l[0], 'TIPO_DOC' => 'DNI', 'NRO_DOC' => '12345678', 'CAPITAL_RECUPERADO' => $l[2] ?? '0.00',
            'FACTOR_COBERTURA' => '0.9000', 'MONTO_A_DEVOLVER' => $l[1]], $lineas));
    }

    public function test_lee_el_txt_con_encabezado(): void
    {
        $f = CruceTxt::leer($this->txt([['111', '4886.69', '5143.88'], ['222', '1364.63']]));
        $this->assertSame(['111', '222'], array_map('strval', array_keys($f)));
        $this->assertSame('4886.69', $f['111']['monto']);
        $this->assertSame('5143.88', $f['111']['capital']);
    }

    public function test_suma_las_lineas_repetidas_de_un_credito(): void
    {
        $f = CruceTxt::leer($this->txt([['111', '100.10'], ['111', '200.25']]));
        $this->assertSame('300.35', $f['111']['monto']);
    }

    public function test_admite_coma_decimal_y_otro_separador(): void
    {
        $f = CruceTxt::leer("NRO_CREDITO;MONTO_A_DEVOLVER\n111;1.234,56\n", ';', ',');
        $this->assertSame('1234.56', $f['111']['monto']);
    }

    public function test_rechaza_txt_sin_columnas_o_con_montos_invalidos(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        CruceTxt::leer("A|B\n1|2");
    }

    public function test_rechaza_monto_no_numerico(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        CruceTxt::leer("NRO_CREDITO|MONTO_A_DEVOLVER\n111|abc");
    }

    public function test_cuadra_con_diferencia_cero(): void
    {
        $r = CruceTxt::cruzar(['111' => '4886.69', '222' => '1364.63'], ['111' => '4886.69', '222' => '1364.63']);
        $this->assertTrue($r['cuadra']);
        $this->assertSame('0.00', $r['diferencia']);
        $this->assertSame(0, $r['n_diferencias']);
        $this->assertSame('6251.32', $r['total_rct']);
    }

    public function test_detecta_diferencia_por_credito_y_creditos_faltantes_o_sobrantes(): void
    {
        $r = CruceTxt::cruzar(
            ['111' => '1155.71', '222' => '2273.27', '333' => '500.00'],
            ['111' => '0.00', '333' => '500.00', '444' => '10.00'],
        );
        $this->assertFalse($r['cuadra']);
        $this->assertSame(3, $r['n_diferencias']);
        $this->assertSame('diferencia', $r['filas']['111']['resultado']);
        $this->assertSame('1155.71', $r['filas']['111']['diferencia']);   // RCT − TXT
        $this->assertSame('solo_rct', $r['filas']['222']['resultado']);
        $this->assertSame('ok', $r['filas']['333']['resultado']);
        $this->assertSame('solo_txt', $r['filas']['444']['resultado']);
        $this->assertSame('-10.00', $r['filas']['444']['diferencia']);
        $this->assertSame('3418.98', $r['diferencia']);                    // 3928.98 − 510.00
    }

    public function test_diferencias_que_se_compensan_no_cuadran(): void
    {
        $r = CruceTxt::cruzar(['111' => '100.00', '222' => '100.00'], ['111' => '90.00', '222' => '110.00']);
        $this->assertSame('0.00', $r['diferencia']);
        $this->assertFalse($r['cuadra'], 'El total en cero no basta: cada crédito debe cuadrar');
    }
}
