<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Notificaciones leídas por usuario. Las notificaciones se calculan a partir
    // de los datos (decisiones, cargas, recordatorios); aquí solo se guarda la lectura.
    public function up(): void
    {
        Schema::create('notificacion_lecturas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('clave', 120);
            $table->timestamp('leida_en')->useCurrent();
            $table->unique(['user_id', 'clave']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notificacion_lecturas');
    }
};
