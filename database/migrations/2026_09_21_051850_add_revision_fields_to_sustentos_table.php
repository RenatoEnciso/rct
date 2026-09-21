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
    DB::statement("ALTER TABLE sustentos MODIFY COLUMN estado 
        ENUM('Pendiente','En revisión','Aprobado','Observado','Rechazado') 
        NOT NULL DEFAULT 'Pendiente'");

    Schema::table('sustentos', function (Blueprint $table) {
        $table->unsignedBigInteger('revisado_por')->nullable()->after('cargado_por');
        $table->timestamp('fecha_revision')->nullable()->after('fecha_carga');
        $table->text('comentario_revision')->nullable()->after('fecha_revision');
        $table->foreign('revisado_por')->references('id')->on('users');
    });
}

public function down(): void
{
    Schema::table('sustentos', function (Blueprint $table) {
        $table->dropForeign(['revisado_por']);
        $table->dropColumn(['revisado_por', 'fecha_revision', 'comentario_revision']);
    });

    DB::statement("ALTER TABLE sustentos MODIFY COLUMN estado 
        ENUM('Pendiente','Completo') NOT NULL DEFAULT 'Pendiente'");
}
};
