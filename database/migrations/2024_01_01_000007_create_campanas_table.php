<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('campanas', function (Blueprint $table) {
            $table->smallIncrements('id');
            $table->string('nombre', 120);
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->boolean('activa')->default(true);
            $table->tinyInteger('max_conciliaciones_12m')->unsigned()->default(4);
            $table->tinyInteger('max_dias_pago_fraccionado')->unsigned()->default(90);
            $table->decimal('limite_sk_uit', 10, 2)->default(13950.00);
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('campanas'); }
};