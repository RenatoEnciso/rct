<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validación automática de documentos
    |--------------------------------------------------------------------------
    | modo: demo | real | off
    |   demo → App\Services\Validacion\ValidadorDemo (reglas internas en
    |          docs/REGLAS_DEMO_VALIDACION.md). Nunca activo en producción.
    |   real → App\Services\Validacion\ValidadorReal (OCR / IA con visión).
    |   off  → sin validación automática (todo queda pendiente de revisión).
    */
    'validacion' => [
        'modo'          => env('RCT_VALIDACION_MODO', 'demo'),
        'demora_min_ms' => 1500,
        'demora_max_ms' => 3000,
    ],

];
