<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('sustentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('propuesta_fud_id')->constrained('propuestas_fud');
            $table->foreignId('cargado_por')->constrained('users');
            $table->enum('estado', ['Pendiente','Completo'])->default('Pendiente');
            $table->date('fecha_carga')->nullable();
            $table->timestamps();
        });

        Schema::create('sustento_documentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sustento_id')
                  ->constrained('sustentos')->cascadeOnDelete();
            $table->enum('tipo_doc', [
                'Acuerdo de Pago',
                'Nota de Abono',
                'Comprobante de Pago',
                'Hoja de Convenio',
                'Informe-Propuesta SK>3UIT',
                'Otro'
            ]);
            $table->string('nombre_archivo', 255);
            $table->string('ruta_archivo', 500);
            $table->unsignedInteger('size_bytes')->nullable();
            $table->timestamp('cargado_en')->useCurrent();
        });
    }

    public function down(): void {
        Schema::dropIfExists('sustento_documentos');
        Schema::dropIfExists('sustentos');
    }
};