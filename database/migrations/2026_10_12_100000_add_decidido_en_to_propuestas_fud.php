<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Fecha y hora exactas de la decisión del aprobador (fecha_decision solo guarda el día)
        Schema::table('propuestas_fud', function (Blueprint $table) {
            $table->timestamp('decidido_en')->nullable()->after('fecha_decision');
        });

        // Registros existentes: hora tomada de la bitácora; si no hay, el mediodía del día de decisión
        $fuds = DB::table('propuestas_fud')->whereNotNull('fecha_decision')->get(['id', 'fecha_decision']);
        foreach ($fuds as $f) {
            $hora = DB::table('seguimiento')
                ->where('propuesta_fud_id', $f->id)
                ->where('tipo_operacion', 'Decisión FUD')
                ->orderBy('id')
                ->value('created_at');
            DB::table('propuestas_fud')->where('id', $f->id)
                ->update(['decidido_en' => $hora ?? $f->fecha_decision.' 12:00:00']);
        }
    }

    public function down(): void
    {
        Schema::table('propuestas_fud', function (Blueprint $table) {
            $table->dropColumn('decidido_en');
        });
    }
};
