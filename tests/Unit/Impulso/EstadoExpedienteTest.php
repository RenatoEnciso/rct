<?php

namespace Tests\Unit\Impulso;

use App\Models\{ExpedienteGobierno, ExpedienteGobiernoDoc, GobTipoDocumento};
use App\Services\Honramiento\Checklist;
use PHPUnit\Framework\TestCase;

/**
 * Estado del expediente con la regla del Anexo 13: solo los documentos exigidos por COFIDE
 * (6 persona natural / 7 jurídica) determinan el estado; los demás no lo afectan.
 */
class EstadoExpedienteTest extends TestCase
{
    // [id, código, exigido natural, exigido jurídica]
    private const CATALOGO = [
        [1, 'dni', true, false], [2, 'reporte_tributario', false, true], [3, 'ficha_ruc', false, true],
        [4, 'splaft', true, true], [5, 'dj_programa', true, true], [6, 'contrato', true, true],
        [7, 'cronograma', true, true], [8, 'pagare', true, true],
        [9, 'hoja_resumen', false, false], [10, 'dj_patrimonial', false, false], [11, 'rcc_experian', false, false], [12, 'evidencia', false, false],
    ];

    protected function setUp(): void
    {
        $tipos = collect(self::CATALOGO)->map(function ($t) {
            $m = new GobTipoDocumento(['codigo' => $t[1], 'nombre' => $t[1], 'nombre_corto' => $t[1], 'cofide_natural' => $t[2], 'cofide_juridica' => $t[3], 'activo' => true]);
            $m->id = $t[0];
            return $m;
        });
        $r = new \ReflectionProperty(GobTipoDocumento::class, 'cache');
        $r->setValue(null, $tipos);
    }

    protected function tearDown(): void
    {
        (new \ReflectionProperty(GobTipoDocumento::class, 'cache'))->setValue(null, null);
    }

    /** @param array<int, string> $docs [tipo_id => estado] */
    private function estado(string $tipoPersona, array $docs): array
    {
        $e = new ExpedienteGobierno(['tipo_persona' => $tipoPersona]);
        $e->setRelation('documentos', collect($docs)->map(function ($estado, $tipo) {
            $d = new ExpedienteGobiernoDoc(['tipo_documento_id' => $tipo, 'estado' => $estado]);
            $d->id = $tipo;
            return $d;
        })->values());
        return (new Checklist())->calcular($e);
    }

    public function test_persona_natural_completa_con_sus_6_obligatorios_aunque_falten_los_opcionales(): void
    {
        $r = $this->estado('natural', [1 => 'cargado', 4 => 'cargado', 5 => 'cargado', 6 => 'cargado', 7 => 'cargado', 8 => 'conforme']);
        $this->assertSame('Completo', $r['estado']);
        $this->assertSame(0, $r['faltantes']);
    }

    public function test_persona_juridica_necesita_7_obligatorios(): void
    {
        $seis = [2 => 'cargado', 4 => 'cargado', 5 => 'cargado', 6 => 'cargado', 7 => 'cargado', 8 => 'cargado'];
        $r = $this->estado('juridica', $seis);
        $this->assertSame('Incompleto', $r['estado'], 'Falta la Ficha RUC');
        $this->assertSame(1, $r['faltantes']);
        $this->assertSame('Completo', $this->estado('juridica', $seis + [3 => 'cargado'])['estado']);
    }

    public function test_el_dni_no_cuenta_para_persona_juridica(): void
    {
        $r = $this->estado('juridica', [1 => 'cargado', 2 => 'cargado', 3 => 'cargado', 4 => 'cargado', 5 => 'cargado', 6 => 'cargado', 7 => 'cargado', 8 => 'cargado']);
        $this->assertSame('Completo', $r['estado']);
    }

    public function test_los_opcionales_no_cambian_el_estado(): void
    {
        $r = $this->estado('natural', [9 => 'cargado', 10 => 'cargado', 11 => 'cargado', 12 => 'cargado', 1 => 'cargado']);
        $this->assertSame('Incompleto', $r['estado']);
        $this->assertSame(5, $r['faltantes']);
    }

    public function test_sin_documentos_queda_pendiente(): void
    {
        $r = $this->estado('natural', []);
        $this->assertSame('Pendiente', $r['estado']);
        $this->assertSame(6, $r['faltantes']);
        $this->assertSame(7, $this->estado('juridica', [])['faltantes']);
    }

    public function test_obligatorio_no_disponible_es_incompleto_definitivo(): void
    {
        $r = $this->estado('natural', [1 => 'cargado', 4 => 'cargado', 5 => 'cargado', 6 => 'cargado', 7 => 'cargado', 8 => 'no_disponible']);
        $this->assertSame('Incompleto definitivo', $r['estado']);
        $this->assertSame(1, $r['faltantes']);
    }
}
