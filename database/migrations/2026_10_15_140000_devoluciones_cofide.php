<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Etapa 6 — Devolución mensual a COFIDE y cruce con la notificación TXT de recuperación extrajudicial.
 * Devolución del mes = Σ reversiones agregadas del mes no devueltas antes. Diferencia = RCT − TXT (debe ser 0).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('devoluciones_cofide', function (Blueprint $t) {
            $t->id();
            $t->date('periodo')->unique();                 // primer día del mes de los pagos
            $t->date('fecha_limite');
            $t->string('estado', 25)->default('Calculada'); // Calculada | Con diferencias | Cuadrada | Devuelta
            $t->unsignedInteger('creditos')->default(0);
            $t->unsignedInteger('pagos')->default(0);
            $t->decimal('monto_calculado', 15, 2)->default(0);
            $t->decimal('monto_notificado', 15, 2)->nullable();
            $t->decimal('diferencia', 15, 2)->nullable();
            $t->unsignedInteger('n_diferencias')->default(0);
            $t->string('txt_archivo')->nullable();
            $t->string('txt_ruta')->nullable();
            $t->dateTime('txt_importado_en')->nullable();
            $t->foreignId('cruzado_por')->nullable()->constrained('users')->nullOnDelete();
            $t->dateTime('cerrado_en')->nullable();
            $t->foreignId('cerrado_por')->nullable()->constrained('users')->nullOnDelete();
            $t->text('justificacion')->nullable();
            $t->dateTime('aviso_vencimiento_en')->nullable();
            $t->timestamps();
        });

        Schema::create('devolucion_detalles', function (Blueprint $t) {
            $t->id();
            $t->foreignId('devolucion_id')->constrained('devoluciones_cofide')->cascadeOnDelete();
            $t->unsignedBigInteger('expediente_id')->nullable();
            $t->string('credito_sicmact', 30);
            $t->string('cliente', 200)->nullable();
            $t->string('documento', 15)->nullable();
            $t->string('lote', 40)->nullable();
            $t->unsignedInteger('pagos')->default(0);
            $t->decimal('capital', 15, 2)->default(0);
            $t->decimal('factor_cobertura', 7, 4)->nullable();
            $t->decimal('monto_rct', 15, 2)->default(0);
            $t->decimal('monto_txt', 15, 2)->nullable();
            $t->decimal('diferencia', 15, 2)->nullable();
            $t->string('resultado', 15)->default('pendiente');   // pendiente | ok | diferencia | solo_rct | solo_txt
            $t->timestamps();
            $t->index(['devolucion_id', 'credito_sicmact']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('devolucion_detalles');
        Schema::dropIfExists('devoluciones_cofide');
    }
};
