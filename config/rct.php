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

    /*
    |--------------------------------------------------------------------------
    | Notificaciones y recordatorios
    |--------------------------------------------------------------------------
    | ventana_dias: antigüedad máxima de los avisos de actividad.
    | dias_recordatorio: días tras los que se recuerda una tarea pendiente
    | (p. ej. sustentos sin cargar). dias_alerta: a partir de aquí el
    | recordatorio se marca como urgente.
    */
    'notificaciones' => [
        'ventana_dias'      => 30,
        'dias_recordatorio' => 2,
        'dias_alerta'       => 7,
    ],

    /*
    |--------------------------------------------------------------------------
    | Canal WhatsApp (vista de demostración)
    |--------------------------------------------------------------------------
    | Réplica funcional del canal de mensajería en /whatsapp. No pide login:
    | opera como el usuario configurado. Por defecto solo se habilita en local.
    */
    'whatsapp' => [
        'habilitado' => (bool) env('RCT_WHATSAPP_DEMO', env('APP_ENV') === 'local'),
        'usuario'    => env('RCT_WHATSAPP_USUARIO', 'ejecutivo1@cajatrujillo.pe'),
        'numero'     => '+51 944 120 300',
    ],

];
