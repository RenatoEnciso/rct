<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clientes_cartera', function (Blueprint $table) {

            // =========================================================
            // IDENTIFICACIÓN DE LA OPERACIÓN / CRÉDITO
            // =========================================================

            $table->id();

            $table->unsignedBigInteger('operacion')
                ->unique()
                ->comment('Número de operación del crédito');

            $table->unsignedBigInteger('zona_id')
                ->nullable();

            $table->unsignedBigInteger('tienda_id')
                ->nullable();

            $table->string('credito_sicmact', 30)
                ->nullable()
                ->comment('Código del crédito en sistema SICMACT');

            $table->unsignedBigInteger('cuenta')
                ->nullable();

            $table->string('nombre_cliente', 200);

            // =========================================================
            // TIPO DE CRÉDITO / CLIENTE
            // =========================================================

            $table->string('tipo_credito', 100)
                ->nullable();

            $table->enum('tipo_cliente', [
                'NATURAL',
                'JURIDICO'
            ])->default('NATURAL');

            // =========================================================
            // MÓDULO Y TIPO DE OPERACIÓN BANCARIA
            // =========================================================

            $table->unsignedSmallInteger('id_modulo')
                ->nullable()
                ->comment('ID del módulo en el sistema bancario');

            $table->string('modulo', 80)
                ->nullable()
                ->comment('Nombre del módulo');

            $table->unsignedSmallInteger('id_tipo_operacion')
                ->nullable();

            $table->string('tipo_operacion_bco', 80)
                ->nullable()
                ->comment('Tipo de operación bancaria interna');

            // =========================================================
            // SALDO / MONEDA
            // =========================================================

            $table->decimal('saldo_capital', 14, 2)
                ->default(0);

            $table->unsignedTinyInteger('id_moneda')
                ->default(1)
                ->comment('1=PEN, 2=USD');

            $table->enum('moneda', [
                'PEN',
                'USD'
            ])->default('PEN');

            $table->decimal('uit_referencia', 10, 2)
                ->nullable()
                ->comment('Valor UIT vigente al corte');

            $table->decimal('monto_desembolso', 14, 2)
                ->nullable()
                ->comment('Monto original desembolsado del crédito');

            // =========================================================
            // ATRASO / CALIFICACIÓN / SITUACIÓN
            // =========================================================

            $table->unsignedInteger('dias_atraso')
                ->default(0);

            $table->enum('rango_atraso', [
                '1-30',
                '31-60',
                '61-90',
                '91-180',
                '181-365',
                '>365',
            ])->nullable();

            $table->enum('calificacion_rcc', [
                'NORMAL',
                'CPP',
                'DEFICIENTE',
                'DUDOSO',
                'PÉRDIDA',
            ])->nullable();

            $table->enum('situacion_credito', [
                'NORMAL',
                'CPP',
                'DEFICIENTE',
                'DUDOSO',
                'PÉRDIDA',
            ])->nullable()
              ->comment('Situación interna del crédito al corte');

            $table->string('estado_cartera', 50)
                ->nullable();

            // =========================================================
            // TRANSACCIÓN / CONCILIACIONES
            // =========================================================

            $table->string('transaccion', 50)
                ->nullable();

            $table->unsignedTinyInteger('veces_conciliado')
                ->default(0);

            $table->unsignedTinyInteger('conc_cuenta_12m')
                ->default(0)
                ->comment('Conciliaciones cuenta últimos 12 meses');

            $table->unsignedTinyInteger('conc_operacion_12m')
                ->default(0)
                ->comment('Conciliaciones operación últimos 12 meses');

            $table->unsignedTinyInteger('conc_cuenta_camp_ant')
                ->default(0)
                ->comment('Conciliaciones cuenta campaña anterior');

            $table->unsignedTinyInteger('conc_operacion_camp_ant')
                ->default(0)
                ->comment('Conciliaciones operación campaña anterior');

            $table->unsignedTinyInteger('conc_cuenta_mes_actual')
                ->default(0)
                ->comment('Conciliaciones cuenta mes actual');

            $table->unsignedTinyInteger('conc_operacion_mes_actual')
                ->default(0)
                ->comment('Conciliaciones operación mes actual');

            // =========================================================
            // CAMPAÑA
            // =========================================================

            $table->enum('tipo_negociacion_campana', [
                'cuotas',
                'cancelacion',
                'extrajudicial',
                'castigada',
                'focmac',
            ])->nullable()
              ->comment('Calculado por el sistema');

            $table->enum('lineamiento_campana', [
                'DESCUENTO hasta 70% de saldo capital y 100% de intereses comp. y mora',
                'DESCUENTO hasta 50% de saldo capital y 100% de intereses comp. y mora',
                'DESCUENTO hasta 30% de intereses comp. y mora',
                'DESCUENTO hasta 60% de intereses comp. y mora',
                'DESCUENTO hasta 70% de intereses comp. y mora',
                'Aplica la EXONERACION según lineamiento de campaña',
                'NO APLICA',
            ])->nullable()
              ->comment('Calculado por el sistema');

            $table->enum('autonomia', [
                'CZR',
                'Jefe de Recuperaciones',
                'Gerente de División',
            ])->nullable()
              ->comment('Nivel de autonomía requerido');

            $table->boolean('activo_campana')
                ->default(true);

            // =========================================================
            // CARACTERÍSTICAS DEL CRÉDITO
            // =========================================================

            $table->boolean('es_pignoraticio')
                ->default(false);

            $table->boolean('es_leasing')
                ->default(false);

            $table->boolean('es_multigas')
                ->default(false);

            $table->boolean('es_cartera_adq')
                ->default(false);

            $table->boolean('es_prog_gobierno')
                ->default(false);

            $table->boolean('es_fraude')
                ->default(false);

            // =========================================================
            // PROGRAMA DE GOBIERNO
            // =========================================================

            $table->boolean('prog_gob_califica')
                ->default(false)
                ->comment('Indica si califica para programa de gobierno');

            $table->text('comentarios_prog_gob')
                ->nullable();

            // =========================================================
            // ASESOR DE NEGOCIO
            // =========================================================

            $table->unsignedInteger('id_asesor_negocio')
                ->nullable();

            $table->string('asesor_negocio', 120)
                ->nullable()
                ->comment('Asesor que originó el crédito');

            // =========================================================
            // DATOS DEL CLIENTE
            // =========================================================

            $table->unsignedTinyInteger('id_tipo_documento')
                ->nullable()
                ->comment('1=DNI, 2=RUC, 3=CE');

            $table->enum('tipo_documento', [
                'DNI',
                'RUC',
                'CE',
                'OTRO',
            ])->nullable();

            $table->string('nro_documento', 15)
                ->nullable();

            $table->string('telefono', 20)
                ->nullable();

            $table->string('direccion', 300)
                ->nullable();

            // =========================================================
            // GARANTE
            // =========================================================

            $table->string('garante_nombre', 200)
                ->nullable();

            $table->string('garante_celular', 20)
                ->nullable();

            // =========================================================
            // TIMESTAMPS
            // =========================================================

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clientes_cartera');
    }
};