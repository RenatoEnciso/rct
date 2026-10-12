<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Revisión de expedientes de honramiento de Programas de Gobierno (antes en Excel).
 * Extiende expedientes_gobierno y expediente_gobierno_docs; agrega catálogos,
 * lotes (con el plazo de 30 días), historial de revisiones e importaciones.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gestiones', function (Blueprint $t) {
            $t->smallIncrements('id');
            $t->string('nombre', 60)->unique();
            $t->timestamps();
        });

        Schema::create('lotes', function (Blueprint $t) {
            $t->id();
            $t->string('codigo', 40)->unique();              // "010 Lote"
            $t->string('programa', 60)->nullable();          // IMPULSO MYPERÚ, REACTIVA PERÚ...
            $t->date('fecha_solicitud')->nullable();         // solicitud formal de honramiento
            $t->date('fecha_conformidad')->nullable();       // conformidad → plazo de 30 días calendario
            $t->timestamps();
        });

        // Checklist como datos: exigencia COFIDE por tipo de persona y columna del Excel
        Schema::create('gob_tipos_documento', function (Blueprint $t) {
            $t->smallIncrements('id');
            $t->string('codigo', 30)->unique();
            $t->string('nombre', 120);
            $t->string('nombre_corto', 40);
            $t->unsignedTinyInteger('orden');
            $t->unsignedTinyInteger('columna_excel');        // 1..10 del Excel de revisión
            $t->boolean('cofide_natural')->default(false);
            $t->boolean('cofide_juridica')->default(false);
            $t->boolean('activo')->default(true);
        });

        $tipos = [
            ['dni',              'DNI',                                              'DNI',               1,  1, true,  false],
            ['reporte_tributario','Reporte tributario',                              'Reporte tributario', 2, 1, false, true],
            ['ficha_ruc',        'Ficha RUC / consulta RUC',                         'Ficha RUC',         3,  1, false, true],
            ['splaft',           'Ficha SPLAFT (formulario de conocimiento del cliente)', 'Ficha SPLAFT', 4,  3, true,  true],
            ['hoja_resumen',     'Hoja resumen',                                     'Hoja resumen',      5,  2, false, false],
            ['dj_programa',      'DJ del programa (Anexo 02 o 03)',                  'DJ del programa',   6,  4, true,  true],
            ['dj_patrimonial',   'DJ patrimonial',                                   'DJ patrimonial',    7,  5, false, false],
            ['contrato',         'Contrato de crédito (cargo)',                      'Contrato',          8,  6, true,  true],
            ['cronograma',       'Cronograma de pagos',                              'Cronograma',        9,  7, true,  true],
            ['pagare',           'Pagaré',                                           'Pagaré',           10,  8, true,  true],
            ['rcc_experian',     'Clasificación regulatoria (RCC y Experian)',      'RCC / Experian',   11,  9, false, false],
            ['evidencia',        'Evidencia de ingresos (fotos y recibos)',          'Evidencia',        12, 10, false, false],
        ];
        foreach ($tipos as [$c, $n, $nc, $o, $col, $nat, $jur]) {
            DB::table('gob_tipos_documento')->insert([
                'codigo' => $c, 'nombre' => $n, 'nombre_corto' => $nc, 'orden' => $o, 'columna_excel' => $col,
                'cofide_natural' => $nat, 'cofide_juridica' => $jur, 'activo' => true,
            ]);
        }
        DB::table('gestiones')->insert(['nombre' => '1-TIENDAS', 'created_at' => now(), 'updated_at' => now()]);

        // ── Expediente ───────────────────────────────────────
        Schema::table('expedientes_gobierno', function (Blueprint $t) {
            $t->unsignedBigInteger('cliente_cartera_id')->nullable()->change();
            $t->unsignedBigInteger('registrado_por')->nullable()->change();
            $t->string('programa', 60)->nullable()->change();
            $t->string('motivo_habilitacion', 120)->nullable()->change();
            $t->date('fecha_registro')->nullable()->change();
            $t->string('estado', 30)->default('Pendiente')->change();

            $t->unsignedSmallInteger('gestion_id')->nullable()->after('id');
            $t->string('id_externo', 30)->nullable()->unique()->after('gestion_id');   // ID del Excel
            $t->string('dni', 15)->nullable()->index()->after('id_externo');
            $t->enum('tipo_persona', ['natural', 'juridica'])->default('natural')->after('dni');
            $t->string('credito_sicmact', 30)->nullable()->index()->after('tipo_persona');
            $t->string('cuenta_bt', 30)->nullable()->after('credito_sicmact');
            $t->string('operacion', 30)->nullable()->index()->after('cuenta_bt');
            $t->string('cliente', 200)->nullable()->after('operacion');
            $t->unsignedSmallInteger('zona_id')->nullable()->after('cliente');
            $t->unsignedSmallInteger('tienda_id')->nullable()->after('zona_id');
            $t->unsignedBigInteger('lote_id')->nullable()->after('tienda_id');
            $t->decimal('monto_honrado', 15, 2)->nullable()->after('lote_id');
            $t->unsignedTinyInteger('faltantes')->default(0)->after('estado');       // exigidos por COFIDE
            $t->timestamp('revisado_zona_en')->nullable()->after('faltantes');
            $t->timestamp('revisado_central_en')->nullable()->after('revisado_zona_en');
            $t->text('observaciones')->nullable()->after('revisado_central_en');     // comentario libre

            $t->foreign('gestion_id')->references('id')->on('gestiones');
            $t->foreign('zona_id')->references('id')->on('zonas');
            $t->foreign('tienda_id')->references('id')->on('tiendas');
            $t->foreign('lote_id')->references('id')->on('lotes');
            $t->index(['estado', 'zona_id']);
        });

        // ── Documentos del checklist ────────────────────────
        Schema::table('expediente_gobierno_docs', function (Blueprint $t) {
            $t->string('tipo_doc', 120)->nullable()->change();
            $t->string('nombre_archivo', 255)->nullable()->change();
            $t->string('ruta_archivo', 500)->nullable()->change();
            $t->unsignedSmallInteger('tipo_documento_id')->nullable()->after('expediente_id');
            // falta no se guarda: es la ausencia de fila vigente
            $t->string('estado', 20)->default('cargado')->after('tipo_documento_id');   // cargado | conforme | observado | no_disponible
            $t->string('origen', 10)->default('rct')->after('estado');                  // rct (archivo) | sftp | excel
            $t->unsignedBigInteger('cargado_por')->nullable()->after('origen');
            $t->unsignedBigInteger('revisado_por')->nullable()->after('comentario_revisor');
            $t->timestamp('revisado_en')->nullable()->after('revisado_por');
            $t->text('motivo_no_disponible')->nullable()->after('revisado_en');
            $t->foreign('tipo_documento_id')->references('id')->on('gob_tipos_documento');
            $t->foreign('cargado_por')->references('id')->on('users');
            $t->foreign('revisado_por')->references('id')->on('users');
        });

        // ── Historial de revisiones por nivel ───────────────
        Schema::create('expediente_gobierno_revisiones', function (Blueprint $t) {
            $t->id();
            $t->foreignId('expediente_id')->constrained('expedientes_gobierno')->cascadeOnDelete();
            $t->enum('nivel', ['zona', 'central']);
            $t->enum('resultado', ['conforme', 'observado']);
            $t->text('comentario')->nullable();
            $t->foreignId('user_id')->nullable()->constrained('users');
            $t->string('origen', 10)->default('rct');     // rct | excel
            $t->timestamp('revisado_en');
            $t->index(['expediente_id', 'nivel']);
        });

        Schema::create('importaciones_honramiento', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained('users');
            $t->string('archivo', 255);
            $t->unsignedInteger('filas')->default(0);
            $t->unsignedInteger('creados')->default(0);
            $t->unsignedInteger('actualizados')->default(0);
            $t->unsignedInteger('con_diferencias')->default(0);
            $t->unsignedInteger('con_error')->default(0);
            $t->timestamps();
        });

        $this->convertirExistentes();
    }

    /** Expedientes registrados con el modelo anterior: datos del crédito y documentos al nuevo checklist. */
    private function convertirExistentes(): void
    {
        $tipos = DB::table('gob_tipos_documento')->pluck('id', 'codigo');
        $gestion = DB::table('gestiones')->where('nombre', '1-TIENDAS')->value('id');

        foreach (DB::table('expedientes_gobierno')->get() as $e) {
            $c = DB::table('clientes_cartera')->find($e->cliente_cartera_id);
            $doc = $c?->nro_documento;
            $juridica = $doc && strlen($doc) === 11 && str_starts_with($doc, '20');
            DB::table('expedientes_gobierno')->where('id', $e->id)->update([
                'gestion_id'      => $gestion,
                'id_externo'      => 'RCT-'.$e->id,
                'dni'             => $doc,
                'tipo_persona'    => $juridica ? 'juridica' : 'natural',
                'credito_sicmact' => $c?->credito_sicmact,
                'cuenta_bt'       => $c ? (string) $c->cuenta : null,
                'operacion'       => $c ? (string) $c->operacion : null,
                'cliente'         => $c?->nombre_cliente,
                'zona_id'         => $c?->zona_id,
                'tienda_id'       => $c?->tienda_id,
                'monto_honrado'   => $c ? round($c->saldo_capital * 0.8, 2) : null,
            ]);
        }

        foreach (DB::table('expediente_gobierno_docs')->get() as $d) {
            $n = Str::lower(Str::ascii((string) $d->tipo_doc));
            $codigo = match (true) {
                str_contains($n, 'dni')          => 'dni',
                str_contains($n, 'hoja')         => 'hoja_resumen',
                str_contains($n, 'conocimiento') => 'splaft',
                str_contains($n, 'programa')     => 'dj_programa',
                str_contains($n, 'patrimonial')  => 'dj_patrimonial',
                str_contains($n, 'contrato')     => 'contrato',
                str_contains($n, 'cronograma')   => 'cronograma',
                str_contains($n, 'pagar')        => 'pagare',
                str_contains($n, 'clasificacion')=> 'rcc_experian',
                str_contains($n, 'evidencia')    => 'evidencia',
                default                          => null,
            };
            DB::table('expediente_gobierno_docs')->where('id', $d->id)->update([
                'tipo_documento_id' => $codigo ? $tipos[$codigo] : null,
                'estado'            => $d->conforme_manual ? 'conforme' : 'cargado',
                'origen'            => $d->ruta_archivo ? 'rct' : 'sftp',
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('importaciones_honramiento');
        Schema::dropIfExists('expediente_gobierno_revisiones');
        Schema::table('expediente_gobierno_docs', function (Blueprint $t) {
            $t->dropForeign(['tipo_documento_id']);
            $t->dropForeign(['cargado_por']);
            $t->dropForeign(['revisado_por']);
            $t->dropColumn(['tipo_documento_id', 'estado', 'origen', 'cargado_por', 'revisado_por', 'revisado_en', 'motivo_no_disponible']);
        });
        Schema::table('expedientes_gobierno', function (Blueprint $t) {
            $t->dropForeign(['gestion_id']);
            $t->dropForeign(['zona_id']);
            $t->dropForeign(['tienda_id']);
            $t->dropForeign(['lote_id']);
            $t->dropIndex(['estado', 'zona_id']);
            $t->dropColumn(['gestion_id', 'id_externo', 'dni', 'tipo_persona', 'credito_sicmact', 'cuenta_bt', 'operacion',
                'cliente', 'zona_id', 'tienda_id', 'lote_id', 'monto_honrado', 'faltantes', 'revisado_zona_en',
                'revisado_central_en', 'observaciones']);
        });
        Schema::dropIfExists('gob_tipos_documento');
        Schema::dropIfExists('lotes');
        Schema::dropIfExists('gestiones');
    }
};
