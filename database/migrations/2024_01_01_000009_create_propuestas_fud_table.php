<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('propuestas_fud', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_cartera_id')->constrained('clientes_cartera');
            $table->foreignId('enviado_por')->constrained('users');
            $table->foreignId('aprobador_id')->nullable()->constrained('users');
            $table->enum('tipo_negociacion', [
                'cuotas','cancelacion','extrajudicial','castigada','focmac'
            ]);
            $table->string('tipo_negociacion_desc', 120)->nullable();
            $table->boolean('en_campana')->default(true);
            $table->enum('tipo_operacion', [
                'CAMPAÑA','ORDINARIA','ESPECIAL'
            ])->default('CAMPAÑA');
            $table->text('motivo');
            $table->tinyInteger('nivel_autonomia_req')->unsigned()->default(1);
            $table->enum('estado_fud', [
                'En revisión','Aprobado','Observado','Rechazado'
            ])->default('En revisión');
            $table->foreignId('aprobado_por')->nullable()->constrained('users');
            $table->text('comentario_aprobador')->nullable();
            $table->date('fecha_decision')->nullable();
            $table->date('fecha_envio');
            $table->timestamps();
            $table->index('estado_fud');
            $table->index('enviado_por');
        });
    }
    public function down(): void { Schema::dropIfExists('propuestas_fud'); }
};