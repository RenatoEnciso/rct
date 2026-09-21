<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
{
    Schema::table('sustento_documentos', function (Blueprint $table) {
        $table->json('observaciones_carga')->nullable()->after('size_bytes');
        $table->enum('estado_validacion', ['ok','warn','error'])->nullable()->after('observaciones_carga');
        $table->text('comentario_revisor')->nullable()->after('estado_validacion');
        $table->enum('estado_revision', ['pendiente','aprobado','observado'])->default('pendiente')->after('comentario_revisor');
    });
}

public function down(): void
{
    Schema::table('sustento_documentos', function (Blueprint $table) {
        $table->dropColumn([
            'observaciones_carga',
            'estado_validacion', 
            'comentario_revisor',
            'estado_revision',
        ]);
    });
}
};
