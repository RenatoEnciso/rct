<?php
namespace App\Services\Validacion;

use App\Contracts\ValidadorDocumentos;
use App\Models\{ClienteCartera, ExpedienteGobierno, Sustento};
use Illuminate\Http\UploadedFile;

/**
 * Carga de documentos con validación automática, común a sustentos y
 * programas de gobierno.
 */
class CargaDocumentos
{
    public function __construct(private ValidadorDocumentos $validador) {}

    /** Valida sin guardar (vista previa durante la carga). */
    public function previsualizar(UploadedFile $archivo, string $tipo, array $contexto): array
    {
        $hash = hash_file('sha256', $archivo->getRealPath());
        $res  = $this->validador->validar($archivo->getClientOriginalName(), $hash, $tipo, $contexto);

        return $res->toArray() + [
            'nombre'    => $this->validador->nombreVisible($archivo->getClientOriginalName()),
            'demora_ms' => $this->demoraMs($archivo->getClientOriginalName()),
        ];
    }

    /**
     * Valida y guarda. Devuelve los campos para el modelo del documento.
     * $hashesLote acumula los hashes del mismo envío para detectar duplicados entre sí.
     */
    public function guardar(UploadedFile $archivo, string $tipo, array $contexto, string $directorio, array &$hashesLote = []): array
    {
        $hash = hash_file('sha256', $archivo->getRealPath());
        $contexto['hashes_existentes'] = ($contexto['hashes_existentes'] ?? []) + $hashesLote;

        $res = $this->validador->validar($archivo->getClientOriginalName(), $hash, $tipo, $contexto);
        $hashesLote[$hash] = now();

        return [
            'nombre_archivo'    => $this->validador->nombreVisible($archivo->getClientOriginalName()),
            'ruta_archivo'      => $archivo->store($directorio, 'private'),
            'hash'              => $hash,
            'estado_validacion' => $res->estado(),
            'hallazgos'         => $res->hallazgos,
            'validado_en'       => now(),
            'modo_validacion'   => $this->validador->modo(),
        ];
    }

    public function contextoSustento(Sustento $sustento): array
    {
        $fud = $sustento->propuesta;
        return [
            'monto_aprobado'    => (float) ($fud->detalleCancelacion?->monto_a_pagar ?? $fud->cliente->saldo_capital),
            'hashes_existentes' => $sustento->documentos()->whereNotNull('hash')
                ->pluck('cargado_en', 'hash')->all(),
            'programa'          => false,
        ];
    }

    public function contextoGobierno(ClienteCartera $cliente, ?ExpedienteGobierno $expediente = null): array
    {
        return [
            'monto_aprobado'    => (float) $cliente->saldo_capital,
            'hashes_existentes' => $expediente
                ? $expediente->documentos()->whereNotNull('hash')->pluck('cargado_en', 'hash')->all()
                : [],
            'programa'          => true,
        ];
    }

    /** Duración de la animación de análisis: fija por nombre de archivo (demo repetible). */
    private function demoraMs(string $nombre): int
    {
        $min = (int) config('rct.validacion.demora_min_ms', 1500);
        $max = (int) config('rct.validacion.demora_max_ms', 3000);
        return $min + (crc32($nombre) % max($max - $min, 1));
    }
}
