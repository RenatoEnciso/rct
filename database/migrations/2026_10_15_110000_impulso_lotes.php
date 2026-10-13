<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Impulso MyPerú — solicitud por lotes y honramiento:
 * datos del lote (corte, carga, confirmación de COFIDE, firma, estado),
 * datos de honramiento por crédito y candidatos detectados en el core.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('lotes', 'registros_rct')) Schema::table('lotes', function (Blueprint $t) {
            $t->date('fecha_corte')->nullable()->after('programa');
            $t->date('fecha_carga')->nullable()->after('fecha_corte');
            $t->string('estado', 25)->default('En preparación')->after('fecha_conformidad');
            $t->unsignedInteger('registros_rct')->default(0);
            $t->decimal('saldo_rct', 15, 2)->default(0);
            $t->unsignedInteger('registros_cofide')->nullable();
            $t->decimal('saldo_cofide', 15, 2)->nullable();
            $t->string('constancia_cofide', 60)->nullable();
            $t->dateTime('confirmado_en')->nullable();
            $t->json('firmas')->nullable();                 // autorizaciones de los representantes ante COFIDE
            $t->dateTime('documentos_generados_en')->nullable();
            $t->date('fecha_honramiento')->nullable();
            $t->dateTime('reporte_importado_en')->nullable();
            $t->string('reporte_archivo')->nullable();
            $t->dateTime('aviso_agencias_en')->nullable();
            $t->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
        });

        if (!Schema::hasColumn('expedientes_gobierno', 'estado_honramiento')) Schema::table('expedientes_gobierno', function (Blueprint $t) {
            $t->string('estado_honramiento', 15)->nullable()->after('estado');   // Solicitado | Honrado | No honrado
            $t->decimal('saldo_insoluto', 15, 2)->nullable()->after('monto_honrado');
            $t->decimal('interes_devengado', 15, 2)->nullable()->after('saldo_insoluto');
            $t->decimal('factor_cobertura', 7, 4)->nullable()->after('interes_devengado');
            $t->date('fecha_honramiento')->nullable()->after('factor_cobertura');
        });

        Schema::create('candidatos_honramiento', function (Blueprint $t) {
            $t->id();
            $t->string('credito_sicmact', 30)->unique();
            $t->unsignedInteger('dias_atraso');
            $t->dateTime('detectado_en');
            $t->dateTime('notificado_en')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidatos_honramiento');
        Schema::table('expedientes_gobierno', function (Blueprint $t) {
            $t->dropColumn(['estado_honramiento', 'saldo_insoluto', 'interes_devengado', 'factor_cobertura', 'fecha_honramiento']);
        });
        Schema::table('lotes', function (Blueprint $t) {
            $t->dropConstrainedForeignId('creado_por');
            $t->dropColumn(['fecha_corte', 'fecha_carga', 'estado', 'registros_rct', 'saldo_rct', 'registros_cofide', 'saldo_cofide',
                'constancia_cofide', 'confirmado_en', 'firmas', 'documentos_generados_en', 'fecha_honramiento',
                'reporte_importado_en', 'reporte_archivo', 'aviso_agencias_en']);
        });
    }
};
