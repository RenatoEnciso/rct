<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Reversión a COFIDE por pago (pari passu): capital × factor de cobertura si el crédito estaba honrado
 * a la fecha del pago; si no, 0 con motivo. Estados: Sincronizado → Agregado → Incluido en devolución → Devuelto
 * (o Sin reversión).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pagos_programa', function (Blueprint $t) {
            $t->unsignedBigInteger('expediente_id')->nullable()->after('credito_sicmact')->index();
            $t->boolean('honrado')->default(false)->after('canal');
            $t->decimal('factor_cobertura', 7, 4)->nullable()->after('honrado');
            $t->decimal('reversion', 15, 2)->default(0)->after('factor_cobertura');
            $t->string('motivo', 150)->nullable()->after('estado');
            $t->dateTime('agregado_en')->nullable()->after('motivo');
            $t->unsignedBigInteger('devolucion_id')->nullable()->after('agregado_en')->index();
        });
    }

    public function down(): void
    {
        Schema::table('pagos_programa', function (Blueprint $t) {
            $t->dropColumn(['expediente_id', 'honrado', 'factor_cobertura', 'reversion', 'motivo', 'agregado_en', 'devolucion_id']);
        });
    }
};
