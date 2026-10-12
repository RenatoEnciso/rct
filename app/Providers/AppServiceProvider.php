<?php

namespace App\Providers;

use App\Contracts\ValidadorDocumentos;
use App\Services\Validacion\{ValidadorDemo, ValidadorReal};
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Validación de documentos: demo (reglas internas) o real, sin cambiar la interfaz
        $this->app->bind(ValidadorDocumentos::class, fn() =>
            config('rct.validacion.modo') === 'demo' ? new ValidadorDemo() : new ValidadorReal()
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Interfaz en español (mensajes de validación, fechas, paginación)
        app()->setLocale('es');
        Carbon::setLocale('es');

        Paginator::defaultView('vendor.pagination.rct');
        Paginator::defaultSimpleView('vendor.pagination.rct');
    }
}
