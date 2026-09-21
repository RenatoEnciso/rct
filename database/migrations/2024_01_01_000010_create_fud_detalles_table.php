<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {

        // Detalle: Pago de Cuotas
        Schema::create('fud_detalle_cuotas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('propuesta_fud_id')
                  ->unique()->constrained('propuestas_fud')->cascadeOnDelete();
            $table->smallInteger('cuota_desde')->unsigned()->nullable();
            $table->smallInteger('cuota_hasta')->unsigned()->nullable();
            $table->decimal('desc_interes', 5, 2)->nullable();
            $table->decimal('desc_moratorio', 5, 2)->default(100);
            $table->boolean('pone_al_dia')->default(false);
            $table->boolean('cierra_mes_vigente')->default(true);
            $table->timestamp('created_at')->useCurrent();
        });

        // Detalle: Cancelación Total
        Schema::create('fud_detalle_cancelacion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('propuesta_fud_id')
                  ->unique()->constrained('propuestas_fud')->cascadeOnDelete();
            $table->decimal('monto_a_pagar', 14, 2)->nullable();
            $table->decimal('desc_interes', 5, 2)->nullable();
            $table->decimal('desc_capital', 5, 2)->default(0);
            $table->date('fecha_limite')->nullable();
            $table->boolean('mayor_tres_uit')->default(false);
            $table->boolean('sunarp_positivo')->default(false);
            $table->timestamp('created_at')->useCurrent();
        });

        // Detalle: Judicial / Extrajudicial / Castigada / FOCMAC
        Schema::create('fud_detalle_judicial', function (Blueprint $table) {
            $table->id();
            $table->foreignId('propuesta_fud_id')
                  ->unique()->constrained('propuestas_fud')->cascadeOnDelete();
            $table->decimal('monto_gravamen', 14, 2)->nullable();
            $table->enum('etapa_procesal', [
                'Pre-judicial',
                'Ejecución de garantías',
                'Medidas cautelares',
                'Etapa de remate'
            ])->nullable();
            $table->decimal('beneficio_pct_sk', 5, 2)->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void {
        Schema::dropIfExists('fud_detalle_judicial');
        Schema::dropIfExists('fud_detalle_cancelacion');
        Schema::dropIfExists('fud_detalle_cuotas');
    }
};