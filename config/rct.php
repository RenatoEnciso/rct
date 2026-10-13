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

    /*
    |--------------------------------------------------------------------------
    | Analítica (priorización, recaída y alertas de control)
    |--------------------------------------------------------------------------
    | modo: demo → App\Services\Analitica\Demo\* (fórmulas fijas y deterministas,
    |       documentadas en docs/REGLAS_DEMO_ANALITICA.md). Los resultados se
    |       precalculan en tablas con `php artisan rct:analitica-recalcular`.
    | tasas_provision: tasa de provisión por calificación (referencial SBS).
    | segmentos: puntaje mínimo de los segmentos Alta y Media.
    */
    'analitica' => [
        'modo' => env('RCT_ANALITICA_MODO', 'demo'),
        'tasas_provision' => [
            'NORMAL'     => 0.01,
            'CPP'        => 0.05,
            'DEFICIENTE' => 0.25,
            'DUDOSO'     => 0.60,
            'PÉRDIDA'    => 1.00,
        ],
        'segmentos' => ['Alta' => 55, 'Media' => 35],
    ],

    /*
    |--------------------------------------------------------------------------
    | Impulso MyPerú: honramiento y devolución a COFIDE
    |--------------------------------------------------------------------------
    | Supuestos documentados en docs/REGLAS_DEMO_IMPULSO.md (preguntas abiertas
    | de docs/PROCESO_IMPULSO_MYPERU.md §6). Cambiarlos aquí, no en el código.
    | fuente_core: demo → App\Services\Core\FuenteCoreDemo (tablas espejo).
    | margen_minutos: cada sincronización relee este margen anterior a la última
    | (los pagos ya procesados se descartan por id_core).
    */
    'impulso' => [
        'programa'       => 'IMPULSO MYPERÚ',
        'fuente_core'    => env('RCT_FUENTE_CORE', 'demo'),
        'margen_minutos' => 60,
        'dias_candidato' => 90,              // días de atraso para ser candidato a honramiento
        'codigo_ifi'     => '0109',          // código de la Caja ante COFIDE (supuesto)
        // Factor de cobertura por monto desembolsado [hasta S/, factor] (supuesto; COFIDE lo confirma en su reporte)
        'tramos_cobertura' => [[30000, 0.98], [90000, 0.95], [300000, 0.90], [null, 0.80]],
        // Destinatarios de los avisos (sección 5): el revisor de programas hoy es la Jefatura de Recuperaciones
        'revisores'      => ['jefe@cajatrujillo.pe'],
        'contabilidad'   => ['contabilidad@cajatrujillo.pe'],
        // Nombre de archivo que se comunica a las agencias (supuesto)
        'formato_archivo' => '{N° CRÉDITO}_{DOCUMENTO}.pdf',
        // Representantes ante COFIDE con firma digital (supuesto; nombres ficticios)
        'representantes' => [
            ['nombre' => 'Carlos Benites Alvarado', 'cargo' => 'Gerente de División de Negocios'],
            ['nombre' => 'Rosa Elvira Campos Lázaro', 'cargo' => 'Gerente de Finanzas'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Regeneración de la demo (rct:demo-reset)
    |--------------------------------------------------------------------------
    | mysqldump: ejecutable para el respaldo previo (storage/backups).
    */
    'demo' => [
        'mysqldump' => env('RCT_MYSQLDUMP', 'E:/laragon/bin/mysql/mysql-8.4.3-winx64/bin/mysqldump.exe'),
    ],

];
