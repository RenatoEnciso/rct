<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('seguimiento', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('propuesta_fud_id')
                  ->nullable()->constrained('propuestas_fud')->nullOnDelete();
            $table->string('tipo_operacion', 80);
            $table->string('cliente', 200)->nullable();
            $table->string('operacion', 20)->nullable();
            $table->string('estado', 60)->nullable();
            $table->text('descripcion')->nullable();
            $table->date('fecha_accion');
            $table->timestamp('created_at')->useCurrent();
            $table->index('fecha_accion');
        });
    }
    public function down(): void { Schema::dropIfExists('seguimiento'); }
};