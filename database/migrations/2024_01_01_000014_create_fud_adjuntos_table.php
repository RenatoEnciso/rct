<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // ── Adjuntos (para FUD y para decisiones del aprobador) ──
        Schema::create('fud_adjuntos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('propuesta_fud_id')
                  ->constrained('propuestas_fud')->cascadeOnDelete();
            $table->foreignId('subido_por')->constrained('users');
            $table->string('nombre_archivo', 255);
            $table->string('ruta_archivo', 500);
            $table->enum('tipo_adjunto', [
                'Evidencia negociación',
                'Captura sistema',
                'Documento cliente',
                'Adjunto aprobador',
                'Otro'
            ])->default('Otro');
            $table->unsignedInteger('size_bytes')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        // ── Columnas nuevas en propuestas_fud ──
        Schema::table('propuestas_fud', function (Blueprint $table) {
            // Para traza: si este FUD es corrección de otro
            $table->foreignId('parent_fud_id')
                  ->nullable()
                  ->after('id')
                  ->constrained('propuestas_fud')
                  ->nullOnDelete();
            // Contador de observaciones acumuladas
            $table->tinyInteger('veces_observado')
                  ->unsigned()->default(0)
                  ->after('estado_fud');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fud_adjuntos');
        Schema::table('propuestas_fud', function (Blueprint $table) {
            $table->dropForeign(['parent_fud_id']);
            $table->dropColumn(['parent_fud_id', 'veces_observado']);
        });
    }
};
