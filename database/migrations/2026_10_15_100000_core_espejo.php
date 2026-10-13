<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tablas espejo del core financiero (créditos y pagos) y registro de sincronizaciones.
 * Simulan lo que entrega el core cada 30 minutos; RCT las lee a través de App\Contracts\FuenteCore.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('core_creditos', function (Blueprint $t) {
            $t->id();
            $t->string('credito_sicmact', 30)->unique();
            $t->string('cuenta', 30)->nullable();
            $t->string('operacion', 30)->nullable();
            $t->string('tipo_documento', 5);            // DNI | RUC
            $t->string('nro_documento', 15)->index();
            $t->string('tipo_persona', 10);             // natural | juridica
            $t->string('cliente', 200);
            $t->unsignedSmallInteger('zona_id')->nullable()->index();
            $t->unsignedSmallInteger('tienda_id')->nullable()->index();
            $t->string('programa', 60)->nullable()->index();
            $t->string('producto', 80)->nullable();
            $t->date('fecha_desembolso')->nullable();
            $t->decimal('monto_desembolso', 15, 2)->default(0);
            $t->decimal('saldo_capital', 15, 2)->default(0);
            $t->decimal('interes_devengado', 15, 2)->default(0);
            $t->unsignedInteger('dias_atraso')->default(0);
            $t->decimal('factor_cobertura', 7, 4)->nullable();
            $t->string('estado', 20)->default('VIGENTE');
            $t->dateTime('actualizado_en')->nullable();
            $t->timestamps();
        });

        Schema::create('core_pagos', function (Blueprint $t) {
            $t->id();
            $t->string('id_core', 40)->unique();
            $t->string('credito_sicmact', 30)->index();
            $t->dateTime('fecha_pago');
            $t->decimal('monto_total', 15, 2);
            $t->decimal('capital', 15, 2)->default(0);
            $t->decimal('interes', 15, 2)->default(0);
            $t->decimal('mora', 15, 2)->default(0);
            $t->unsignedSmallInteger('tienda_id')->nullable();
            $t->string('canal', 40)->nullable();
            $t->dateTime('registrado_en')->index();     // momento en que el core registró el pago
            $t->timestamps();
        });

        // Pagos del programa leídos del core: un registro por id_core (garantiza la idempotencia)
        Schema::create('pagos_programa', function (Blueprint $t) {
            $t->id();
            $t->string('id_core', 40)->unique();
            $t->string('credito_sicmact', 30)->index();
            $t->dateTime('fecha_pago')->index();
            $t->decimal('monto_total', 15, 2);
            $t->decimal('capital', 15, 2)->default(0);
            $t->decimal('interes', 15, 2)->default(0);
            $t->decimal('mora', 15, 2)->default(0);
            $t->unsignedSmallInteger('tienda_id')->nullable();
            $t->string('canal', 40)->nullable();
            $t->string('estado', 25)->default('Sincronizado');
            $t->dateTime('sincronizado_en');
            $t->unsignedBigInteger('sincronizacion_id')->nullable()->index();
            $t->timestamps();
        });

        Schema::create('sincronizaciones_core', function (Blueprint $t) {
            $t->id();
            $t->string('origen', 15)->default('programado');   // programado | manual | simulacion
            $t->dateTime('iniciado_en');
            $t->dateTime('finalizado_en')->nullable();
            $t->unsignedInteger('creditos_leidos')->default(0);
            $t->unsignedInteger('pagos_leidos')->default(0);
            $t->unsignedInteger('pagos_nuevos')->default(0);
            $t->unsignedInteger('pagos_programa')->default(0);
            $t->unsignedInteger('agregados')->default(0);
            $t->unsignedInteger('sin_reversion')->default(0);
            $t->unsignedInteger('candidatos_nuevos')->default(0);
            $t->string('estado', 10)->default('ok');          // ok | error
            $t->text('mensaje')->nullable();
            $t->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sincronizaciones_core');
        Schema::dropIfExists('pagos_programa');
        Schema::dropIfExists('core_pagos');
        Schema::dropIfExists('core_creditos');
    }
};
