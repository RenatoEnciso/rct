<?php
namespace App\Contracts;

use App\Services\Validacion\ResultadoValidacion;

/**
 * Validación automática de documentos de un expediente (sustentos de
 * conciliación y documentos de programas de gobierno).
 */
interface ValidadorDocumentos
{
    /**
     * @param string $nombreArchivo  Nombre original del archivo subido.
     * @param string $hash           SHA-256 del contenido.
     * @param string $tipoEsperado   Tipo de documento del espacio de carga.
     * @param array  $contexto       monto_aprobado (?float), hashes_existentes ([hash => Carbon]),
     *                               programa (bool: documento de programa de gobierno).
     */
    public function validar(string $nombreArchivo, string $hash, string $tipoEsperado, array $contexto = []): ResultadoValidacion;

    /** Nombre con el que se guarda y muestra el archivo. */
    public function nombreVisible(string $nombreArchivo): string;

    /** Identificador interno del validador (se guarda en modo_validacion). */
    public function modo(): string;
}
