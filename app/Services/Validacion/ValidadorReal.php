<?php
namespace App\Services\Validacion;

use App\Contracts\ValidadorDocumentos;

/**
 * Validador real (pendiente): OCR / IA con visión para extraer montos, DNI,
 * N° de crédito y detectar firmas. Debe devolver los mismos códigos de
 * hallazgo que ValidadorDemo para que la interfaz no cambie.
 */
class ValidadorReal implements ValidadorDocumentos
{
    public function validar(string $nombreArchivo, string $hash, string $tipoEsperado, array $contexto = []): ResultadoValidacion
    {
        throw new \LogicException('La validación real de documentos aún no está implementada. Use RCT_VALIDACION_MODO=demo.');
    }

    public function nombreVisible(string $nombreArchivo): string
    {
        return $nombreArchivo;
    }

    public function modo(): string
    {
        return 'real';
    }
}
