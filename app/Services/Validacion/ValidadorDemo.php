<?php
namespace App\Services\Validacion;

use App\Contracts\ValidadorDocumentos;

/**
 * Validador de demostración: simula la validación con IA según marcadores
 * en el nombre del archivo (sin importar mayúsculas). Reglas completas en
 * docs/REGLAS_DEMO_VALIDACION.md — documento interno; los marcadores nunca
 * se muestran en la interfaz (el nombre visible se guarda sin ellos).
 *
 *   _sf   firma del titular faltante        _sfg  firma del garante faltante
 *   _fd   firma distinta a la registrada    _fmt  no es la plantilla oficial
 *   _mto  monto distinto al aprobado        _dni  DNI distinto al titular
 *   _cred N° de crédito distinto            _vnc  búsqueda registral > 6 meses
 *   _ileg ilegible (rechazado)              _tipo tipo de documento distinto
 *   Solo programas de gobierno: _sld saldo distinto, _inc expediente incompleto
 *
 * Única verificación real: mismo hash ya cargado en el expediente → duplicado.
 * Resultado determinista: el mismo nombre siempre da el mismo resultado.
 */
class ValidadorDemo implements ValidadorDocumentos
{
    private const MARCADORES = ['sfg', 'sf', 'fd', 'fmt', 'mto', 'dni', 'cred', 'vnc', 'ileg', 'tipo', 'sld', 'inc'];
    private const SOLO_PROGRAMAS = ['sld', 'inc'];
    private const DIFERENCIA_MONTO = 150.00;

    public function validar(string $nombreArchivo, string $hash, string $tipoEsperado, array $contexto = []): ResultadoValidacion
    {
        $hallazgos = [];
        $esPrograma = (bool) ($contexto['programa'] ?? false);

        foreach ($this->marcadores($nombreArchivo) as $m) {
            if (in_array($m, self::SOLO_PROGRAMAS) && !$esPrograma) continue;
            $hallazgos[] = $this->hallazgo($m, $tipoEsperado, $contexto);
        }

        // Verificación real: el mismo archivo ya fue cargado en este expediente
        $existentes = $contexto['hashes_existentes'] ?? [];
        if (isset($existentes[$hash])) {
            $fecha = $existentes[$hash];
            $hallazgos[] = [
                'codigo'    => 'DUPLICADO',
                'severidad' => ResultadoValidacion::OBSERVADO,
                'mensaje'   => 'Documento duplicado, ya fue cargado el '
                    .($fecha ? \Illuminate\Support\Carbon::parse($fecha)->format('d/m/Y') : now()->format('d/m/Y')).'.',
            ];
        }

        return new ResultadoValidacion($hallazgos, $this->modo());
    }

    public function nombreVisible(string $nombreArchivo): string
    {
        $limpio = preg_replace($this->patron(), '', $nombreArchivo);
        return $limpio !== '' ? $limpio : $nombreArchivo;
    }

    public function modo(): string
    {
        return 'demo';
    }

    /** Marcadores presentes en el nombre, en el orden en que aparecen y sin repetir. */
    private function marcadores(string $nombre): array
    {
        preg_match_all($this->patron(), $nombre, $m);
        return array_values(array_unique(array_map('strtolower', $m[1])));
    }

    private function patron(): string
    {
        // _marcador seguido de otro "_", de la extensión o del final del nombre
        return '/_('.implode('|', self::MARCADORES).')(?=[_.\s-]|$)/i';
    }

    private function hallazgo(string $marcador, string $tipoEsperado, array $contexto): array
    {
        $aprobado = (float) ($contexto['monto_aprobado'] ?? 0);
        $soles    = fn(float $v) => 'S/ '.number_format($v, 2);

        [$codigo, $mensaje] = match ($marcador) {
            'sf'   => ['FIRMA_FALTANTE',  'Falta la firma del titular.'],
            'sfg'  => ['FIRMA_FALTANTE',  'Falta la firma del garante / participante.'],
            'fd'   => ['FIRMA_DIFERENTE', 'La firma no coincide con la registrada en otros documentos del expediente.'],
            'fmt'  => ['FORMATO',         'El documento no corresponde a la plantilla oficial vigente.'],
            'mto'  => ['MONTO',           'El monto del documento ('.$soles(max($aprobado - self::DIFERENCIA_MONTO, 0))
                                          .') no coincide con el monto aprobado ('.$soles($aprobado).').'],
            'dni'  => ['DNI',             'El DNI del documento no coincide con el titular del crédito.'],
            'cred' => ['CREDITO',         'El número de crédito del documento no coincide con el expediente.'],
            'vnc'  => ['VIGENCIA',        'La búsqueda registral tiene más de 6 meses de antigüedad.'],
            'ileg' => ['ILEGIBLE',        'Documento ilegible. Vuelva a escanear con mejor resolución.'],
            'tipo' => ['TIPO',            "El tipo de documento no corresponde al esperado (se esperaba: {$tipoEsperado})."],
            'sld'  => ['SALDO',           'El saldo del documento no coincide con el saldo del crédito a la fecha de solicitud.'],
            'inc'  => ['INCOMPLETO',      'Expediente incompleto: falta(n) documento(s) del checklist del programa.'],
        };

        return [
            'codigo'    => $codigo,
            'severidad' => $marcador === 'ileg' ? ResultadoValidacion::RECHAZADO : ResultadoValidacion::OBSERVADO,
            'mensaje'   => $mensaje,
        ];
    }
}
