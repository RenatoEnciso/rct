<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Impulso MyPerú: el core se actualiza cada 30 minutos (ejecutar el planificador con `php artisan schedule:work`)
Illuminate\Support\Facades\Schedule::command('rct:sincronizar-pagos-core')->everyThirtyMinutes()->withoutOverlapping();
