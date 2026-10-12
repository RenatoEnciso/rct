<?php
namespace App\Services\Validacion;

/**
 * Resultado de la validación automática: estado + hallazgos.
 * Códigos de hallazgo (comunes a validador demo y real):
 * FIRMA_FALTANTE, FIRMA_DIFERENTE, FORMATO, MONTO, DNI, CREDITO, VIGENCIA,
 * ILEGIBLE, TIPO, DUPLICADO, SALDO, INCOMPLETO.
 */
class ResultadoValidacion
{
    public const CONFORME  = 'conforme';
    public const OBSERVADO = 'observado';
    public const RECHAZADO = 'rechazado';

    public const MENSAJE_CONFORME = 'Documento conforme. Firmas, formato y montos verificados.';

    /** @param array<int, array{codigo:string, severidad:string, mensaje:string}> $hallazgos */
    public function __construct(
        public readonly array $hallazgos = [],
        public readonly string $modo = 'demo',
    ) {}

    public function estado(): string
    {
        if (!$this->hallazgos) return self::CONFORME;
        foreach ($this->hallazgos as $h) {
            if ($h['severidad'] === self::RECHAZADO) return self::RECHAZADO;
        }
        return self::OBSERVADO;
    }

    public static function etiqueta(?string $estado): string
    {
        return match ($estado) {
            self::CONFORME  => 'Conforme',
            self::OBSERVADO => 'Observado',
            self::RECHAZADO => 'Rechazado',
            default         => 'Sin validar',
        };
    }

    public function toArray(): array
    {
        return [
            'estado'    => $this->estado(),
            'etiqueta'  => self::etiqueta($this->estado()),
            'mensaje'   => $this->hallazgos ? null : self::MENSAJE_CONFORME,
            'hallazgos' => $this->hallazgos,
        ];
    }
}
