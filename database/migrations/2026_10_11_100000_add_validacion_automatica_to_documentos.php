<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Sustentos: resultado de la validación automática + historial (no se borran documentos)
        Schema::table('sustento_documentos', function (Blueprint $table) {
            $table->string('estado_validacion', 20)->nullable()->change(); // conforme | observado | rechazado
            $table->json('hallazgos')->nullable()->after('estado_validacion');
            $table->timestamp('validado_en')->nullable()->after('hallazgos');
            $table->string('modo_validacion', 10)->nullable()->after('validado_en');
            $table->string('hash', 64)->nullable()->index()->after('ruta_archivo');
            $table->boolean('conforme_manual')->default(false)->after('estado_revision');
            $table->boolean('vigente')->default(true)->after('conforme_manual');
        });

        DB::table('sustento_documentos')->where('estado_validacion', 'ok')->update(['estado_validacion' => 'conforme']);
        DB::table('sustento_documentos')->where('estado_validacion', 'warn')->update(['estado_validacion' => 'observado']);
        DB::table('sustento_documentos')->where('estado_validacion', 'error')->update(['estado_validacion' => 'rechazado']);

        // Programas de gobierno: mismo resultado de validación
        Schema::table('expediente_gobierno_docs', function (Blueprint $table) {
            $table->string('hash', 64)->nullable()->index()->after('ruta_archivo');
            $table->string('estado_validacion', 20)->nullable()->after('hash');
            $table->json('hallazgos')->nullable()->after('estado_validacion');
            $table->timestamp('validado_en')->nullable()->after('hallazgos');
            $table->string('modo_validacion', 10)->nullable()->after('validado_en');
            $table->boolean('conforme_manual')->default(false)->after('modo_validacion');
            $table->text('comentario_revisor')->nullable()->after('conforme_manual');
            $table->boolean('vigente')->default(true)->after('comentario_revisor');
        });
        DB::table('expediente_gobierno_docs')->whereNull('estado_validacion')->update(['estado_validacion' => 'conforme']);
    }

    public function down(): void
    {
        Schema::table('expediente_gobierno_docs', function (Blueprint $table) {
            $table->dropColumn(['hash', 'estado_validacion', 'hallazgos', 'validado_en', 'modo_validacion', 'conforme_manual', 'comentario_revisor', 'vigente']);
        });
        Schema::table('sustento_documentos', function (Blueprint $table) {
            $table->dropColumn(['hallazgos', 'validado_en', 'modo_validacion', 'hash', 'conforme_manual', 'vigente']);
        });
    }
};
