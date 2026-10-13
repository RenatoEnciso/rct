<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Analítica: recuperos (montos en soles por FUD pagado), metas por zona,
 * priorización de cartera, seguimiento post-beneficio (recaída) y alertas de control.
 * Los datos se precalculan con `php artisan rct:analitica-recalcular`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recuperos', function (Blueprint $t) {
            $t->id();
            $t->foreignId('propuesta_fud_id')->unique()->constrained('propuestas_fud')->cascadeOnDelete();
            $t->foreignId('cliente_cartera_id')->constrained('clientes_cartera')->cascadeOnDelete();
            $t->unsignedSmallInteger('zona_id')->index();
            $t->unsignedSmallInteger('tienda_id')->index();
            $t->foreignId('asesor_id')->nullable()->constrained('users')->nullOnDelete();
            $t->string('tipo_negociacion', 20);
            $t->date('fecha_pago')->index();
            $t->decimal('recuperado_capital', 15, 2)->default(0);
            $t->decimal('recuperado_interes', 15, 2)->default(0);
            $t->decimal('condonado_capital', 15, 2)->default(0);
            $t->decimal('condonado_interes', 15, 2)->default(0);
            $t->decimal('condonado_mora', 15, 2)->default(0);
            $t->decimal('provision_liberada', 15, 2)->default(0);
            $t->timestamps();
        });

        Schema::create('metas_zona', function (Blueprint $t) {
            $t->id();
            $t->unsignedSmallInteger('zona_id');
            $t->unsignedSmallInteger('campana_id')->nullable();
            $t->date('mes');                       // primer día del mes
            $t->decimal('meta_recupero', 15, 2);
            $t->timestamps();
            $t->unique(['zona_id', 'mes']);
        });

        Schema::create('priorizacion_cartera', function (Blueprint $t) {
            $t->id();
            $t->foreignId('cliente_cartera_id')->unique()->constrained('clientes_cartera')->cascadeOnDelete();
            $t->date('fecha_corte');
            $t->unsignedTinyInteger('score');
            $t->string('segmento', 10)->index();
            $t->decimal('probabilidad_pago', 5, 2);
            $t->decimal('monto_recuperable', 15, 2);
            $t->decimal('provision_liberable', 15, 2);
            $t->json('motivos');
            $t->string('accion_sugerida', 40);
            $t->string('mejor_horario', 20);
            $t->json('componentes');
            $t->timestamps();
        });

        Schema::create('seguimiento_beneficio', function (Blueprint $t) {
            $t->id();
            $t->foreignId('propuesta_fud_id')->unique()->constrained('propuestas_fud')->cascadeOnDelete();
            $t->foreignId('cliente_cartera_id')->constrained('clientes_cartera')->cascadeOnDelete();
            $t->foreignId('asesor_id')->nullable()->constrained('users')->nullOnDelete();
            $t->string('tipo_negociacion', 20);
            $t->date('fecha_beneficio')->index();
            $t->string('estado_30', 15)->nullable();   // Al día | Atraso leve | Recayó (null: aún no cumple el plazo)
            $t->string('estado_60', 15)->nullable();
            $t->string('estado_90', 15)->nullable();
            $t->date('fecha_recaida')->nullable();
            $t->unsignedTinyInteger('riesgo_recaida');
            $t->string('semaforo', 10);                 // verde | ambar | rojo
            $t->timestamps();
        });

        Schema::create('alertas_control', function (Blueprint $t) {
            $t->id();
            $t->string('tipo', 40)->index();
            $t->string('severidad', 10)->index();     // Alta | Media | Baja
            $t->string('entidad_tipo', 20);           // asesor | aprobador | tienda | cliente | expediente
            $t->unsignedBigInteger('entidad_id');
            $t->string('entidad_nombre', 200);
            $t->unsignedSmallInteger('zona_id')->nullable()->index();
            $t->string('titulo', 200);
            $t->text('descripcion');
            $t->string('metrica', 120);
            $t->decimal('valor_observado', 12, 2);
            $t->decimal('valor_referencia', 12, 2);
            $t->string('unidad', 20)->nullable();
            $t->json('serie')->nullable();
            $t->dateTime('detectada_en');
            $t->string('estado', 15)->default('Nueva')->index();   // Nueva | En revisión | Descartada | Confirmada
            $t->text('comentario')->nullable();
            $t->foreignId('actualizado_por')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });

        Schema::create('alerta_historial', function (Blueprint $t) {
            $t->id();
            $t->foreignId('alerta_id')->constrained('alertas_control')->cascadeOnDelete();
            $t->foreignId('user_id')->constrained('users');
            $t->string('estado_anterior', 15);
            $t->string('estado_nuevo', 15);
            $t->text('comentario')->nullable();
            $t->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alerta_historial');
        Schema::dropIfExists('alertas_control');
        Schema::dropIfExists('seguimiento_beneficio');
        Schema::dropIfExists('priorizacion_cartera');
        Schema::dropIfExists('metas_zona');
        Schema::dropIfExists('recuperos');
    }
};
