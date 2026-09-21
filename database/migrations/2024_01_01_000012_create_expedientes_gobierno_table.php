<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('expedientes_gobierno', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_cartera_id')->constrained('clientes_cartera');
            $table->foreignId('registrado_por')->constrained('users');
            $table->enum('programa', [
                'IMPULSA PERÚ','FAE-MYPE','Reactiva Perú','Otro'
            ]);
            $table->enum('motivo_habilitacion', [
                'Garantía rechazada por Cía. Seguros',
                'Pérdida de eficacia de la garantía'
            ]);
            $table->boolean('confirmacion_dpto')->default(false);
            $table->string('responsable', 120)->nullable();
            $table->date('fecha_registro');
            $table->enum('estado', ['Incompleto','Completo'])->default('Incompleto');
            $table->timestamps();
        });

        Schema::create('expediente_gobierno_docs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expediente_id')
                  ->constrained('expedientes_gobierno')->cascadeOnDelete();
            $table->enum('tipo_doc', [
                'DNI / Reporte tributario con Ficha RUC',
                'Hoja Resumen',
                'Formulario Conocimiento del Cliente',
                'DJ del Programa – Anexo 02',
                'DJ Patrimonial',
                'Cargo del Contrato de Crédito',
                'Cronograma de Pagos',
                'Pagaré',
                'Clasificación Regulatoria (RCC, EXPERIAN)',
                'Evidencia de Ingresos'
            ]);
            $table->string('nombre_archivo', 255);
            $table->string('ruta_archivo', 500);
            $table->timestamp('cargado_en')->useCurrent();
        });
    }

    public function down(): void {
        Schema::dropIfExists('expediente_gobierno_docs');
        Schema::dropIfExists('expedientes_gobierno');
    }
};