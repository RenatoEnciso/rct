<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use App\Models\{ExpedienteGobierno, ExpedienteGobiernoDoc, Lote, Tienda, User, Zona};
use App\Services\Honramiento\{EscritorExcel, ImportadorExcel};

/**
 * Expedientes de honramiento de demostración en el formato del Excel del área.
 * Genera el Excel, lo pasa por el importador real y completa con archivos cargados
 * en RCT y observaciones de revisión. Determinista y aditivo (no se repite).
 * Uso: php artisan db:seed --class=HonramientoSeeder
 */
class HonramientoSeeder extends Seeder
{
    public function run(): void
    {
        if (ExpedienteGobierno::where('id_externo', 'not like', 'RCT-%')->exists()) {
            $this->command?->warn('Los expedientes de honramiento de demostración ya existen.');
            return;
        }
        mt_srand(2027);
        $faker = \Faker\Factory::create('es_PE');
        $faker->seed(2027);

        // Lotes con plazos distintos: vencido, por vencer y vigente
        $lotes = [
            '008 Lote' => ['REACTIVA PERÚ',  now()->subDays(85)],
            '009 Lote' => ['FAE-MYPE',       now()->subDays(48)],
            '010 Lote' => ['IMPULSO MYPERÚ', now()->subDays(24)],
            '011 Lote' => ['IMPULSO MYPERÚ', now()->subDays(7)],
        ];
        foreach ($lotes as $codigo => [$programa, $conformidad]) {
            Lote::firstOrCreate(['codigo' => $codigo], [
                'programa' => $programa,
                'fecha_solicitud' => $conformidad->copy()->subDays(12)->toDateString(),
                'fecha_conformidad' => $conformidad->toDateString(),
            ]);
        }

        // Tiendas: las del sistema y una zona nueva como en el Excel real (Norte 5)
        $tiendas = Tienda::with('zona')->get()->map(fn($t) => [mb_strtoupper($t->zona->nombre), 'TIENDA '.mb_strtoupper($t->nombre)])->all();
        array_push($tiendas, ['NORTE 5', 'TIENDA SULLANA'], ['NORTE 5', 'TIENDA TALARA']);

        $filas = [];
        $usados = [];
        for ($i = 0; $i < 150; $i++) {
            do { $id = (string) mt_rand(20000, 260000); } while (isset($usados[$id]));
            $usados[$id] = true;

            $juridica = mt_rand(1, 100) <= 12;
            $doc = $juridica ? '20'.mt_rand(100000000, 609999999) : (string) mt_rand(10000000, 79999999);
            $loteCod = array_keys($lotes)[$this->ponderado([20, 25, 35, 20])];
            [$zona, $tienda] = $tiendas[mt_rand(0, count($tiendas) - 1)];
            $cliente = $juridica
                ? mb_strtoupper($faker->lastName().' '.['INVERSIONES', 'COMERCIAL', 'SERVICIOS GENERALES', 'DISTRIBUIDORA'][mt_rand(0, 3)].' S.A.C.')
                : mb_strtoupper($faker->lastName().' '.$faker->lastName().' '.$faker->firstName().(mt_rand(0, 1) ? ' '.$faker->firstName() : ''));

            // Situación documental objetivo
            $caso = ['completo', 'incompleto', 'pendiente', 'definitivo'][$this->ponderado([52, 30, 10, 8])];
            $exigidas = [1, 3, 4, 6, 7, 8];                 // columnas exigidas por COFIDE en el Excel
            $docs = array_fill(1, 10, 0);
            if ($caso !== 'pendiente') {
                foreach (range(1, 10) as $c) $docs[$c] = in_array($c, $exigidas) ? 1 : (mt_rand(1, 100) <= 68 ? 1 : 0);
                if (in_array($caso, ['incompleto', 'definitivo'])) {
                    $faltan = (array) array_rand(array_flip($exigidas), $caso === 'definitivo' ? 1 : mt_rand(1, 3));
                    foreach ($faltan as $c) $docs[$c] = 0;
                }
            }
            $faltantes = count(array_filter($exigidas, fn($c) => $docs[$c] === 0));
            $estado = ['completo' => 'COMPLETO', 'incompleto' => 'INCOMPLETO', 'pendiente' => 'PENDIENTE', 'definitivo' => 'INCOMPLETO DEFINITIVO'][$caso];

            // Algunas filas con el error típico del Excel: dice COMPLETO y 0 faltantes aunque falta un exigido
            if ($caso === 'incompleto' && mt_rand(1, 100) <= 15) { $estado = 'COMPLETO'; $faltantes = 0; }

            // Fechas de revisión (zona y central) solo en expedientes revisados
            $conformidad = $lotes[$loteCod][1];
            $fZona = $fCentral = null;
            if ($caso === 'completo' && mt_rand(1, 100) <= 55) {
                $fZona = $this->entre($conformidad->copy()->addDays(3), now()->subDay());
                if (mt_rand(1, 100) <= 70) $fCentral = $this->entre($fZona, now()->subDay());
            }

            $nombres = ['DNI', 'Hoja resumen', 'Formulario conocimiento', 'DJ Anexo 02', 'DJ Patrimonial', 'Contrato', 'Cronograma', 'Pagaré', 'RCC', 'Evidencia'];
            $obs = collect(range(1, 10))->filter(fn($c) => $docs[$c] === 0)->map(fn($c) => $nombres[$c - 1])->implode(' | ');

            $filas[] = [
                mt_rand(1, 100) <= 85 ? '1-TIENDAS' : '2-RECUPERACIONES',
                $fCentral?->format('d/m/Y'), $fZona?->format('d/m/Y'),
                $id, $doc, (string) mt_rand(100000000000, 699999999999), (string) mt_rand(100000, 799999), (string) mt_rand(100000, 299999),
                $zona, $tienda, $cliente,
                number_format(mt_rand(800000, 18000000) / 100, 2, ',', '.'),
                $loteCod, $estado, ...array_values($docs), $faltantes, $obs,
            ];
        }

        // Excel en el formato del área → importador real
        $ruta = Storage::disk('private')->path('importaciones/seed_honramiento.xlsx');
        @mkdir(dirname($ruta), 0775, true);
        app(EscritorExcel::class)->guardar($filas, $ruta);
        $importador = app(ImportadorExcel::class);
        $jefe = User::where('email', 'jefe@cajatrujillo.pe')->first() ?? User::first();
        $analisis = $importador->analizar($ruta);
        $imp = $importador->importar($analisis['filas'], $jefe, 'Revision_expedientes_honramiento_demo.xlsx');
        @unlink($ruta);

        $this->archivosYObservaciones();

        $this->command?->info("Honramiento: {$imp->creados} expedientes importados ({$imp->con_diferencias} con diferencias frente al Excel).");
    }

    /** Parte de los documentos con archivo PDF en RCT y algunos observados por el revisor. */
    private function archivosYObservaciones(): void
    {
        $czr = User::whereHas('rol', fn($q) => $q->where('nombre', 'Coordinador Zonal de Recuperaciones'))->get()->keyBy(fn($u) => $u->tienda?->zona_id);
        $jefe = User::where('email', 'jefe@cajatrujillo.pe')->first();
        $comentarios = ['El documento no está firmado por el titular.', 'Copia ilegible; volver a escanear.',
                        'El pagaré no corresponde al crédito honrado.', 'Falta la página 2 del contrato.'];

        foreach (ExpedienteGobierno::where('id_externo', 'not like', 'RCT-%')->with('documentos')->get() as $i => $e) {
            $revisor = $czr->get($e->zona_id) ?? $jefe;
            foreach ($e->documentos->where('origen', 'sftp') as $d) {
                if (mt_rand(1, 100) > 35) continue;
                $nombre = \Illuminate\Support\Str::slug($d->tipo_doc, '_')."_{$e->id_externo}.pdf";
                $ruta = "gobierno/{$e->id}/{$nombre}";
                Storage::disk('private')->put($ruta, $this->pdf($d->tipo_doc, $e));
                $d->update(['origen' => 'rct', 'nombre_archivo' => $nombre, 'ruta_archivo' => $ruta,
                    'estado_validacion' => 'conforme', 'hallazgos' => [], 'modo_validacion' => 'demo', 'validado_en' => $d->cargado_en]);
            }
            // Revisión: documentos exigidos conformes en revisados; observaciones en algunos completos
            if (in_array($e->estado, ['Revisado por zona', 'Conforme'])) {
                $e->documentos()->where('estado', 'cargado')->update(['estado' => 'conforme', 'revisado_por' => $revisor->id, 'revisado_en' => $e->revisado_zona_en]);
            } elseif ($e->estado === 'Completo' && mt_rand(1, 100) <= 22) {
                $doc = $e->checklist()->where('exigido', true)->whereNotNull('doc')->random()['doc'];
                $doc->update(['estado' => 'observado', 'comentario_revisor' => $comentarios[mt_rand(0, 3)],
                    'revisado_por' => $revisor->id, 'revisado_en' => now()->subDays(mt_rand(1, 6))->setTime(mt_rand(9, 17), mt_rand(0, 59))]);
                \App\Models\ExpedienteGobiernoRevision::create([
                    'expediente_id' => $e->id, 'nivel' => 'zona', 'resultado' => 'observado', 'comentario' => $doc->comentario_revisor,
                    'user_id' => $revisor->id, 'origen' => 'rct', 'revisado_en' => $doc->revisado_en,
                ]);
            }
            $e->recalcularEstado();
        }
    }

    private function ponderado(array $pesos): int
    {
        $r = mt_rand(1, array_sum($pesos));
        foreach ($pesos as $i => $p) if (($r -= $p) <= 0) return $i;
        return 0;
    }

    private function entre(Carbon $a, Carbon $b): Carbon
    {
        if ($b->lte($a)) return $a->copy();
        return Carbon::createFromTimestamp(mt_rand($a->timestamp, $b->timestamp))->startOfDay();
    }

    private function pdf(string $tipo, ExpedienteGobierno $e): string
    {
        $esc = fn($t) => str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], iconv('UTF-8', 'Windows-1252//TRANSLIT', $t));
        $lineas = [[16, 'CAJA TRUJILLO - Programas de Gobierno'], [13, $tipo], [11, 'Cliente: '.$e->cliente],
                   [11, 'DNI/RUC: '.$e->dni.'   Credito: '.$e->credito_sicmact], [11, 'Monto honrado: S/ '.number_format((float) $e->monto_honrado, 2)]];
        $stream = ''; $y = 780;
        foreach ($lineas as [$size, $txt]) { $stream .= "BT /F1 $size Tf 60 $y Td (".$esc($txt).") Tj ET\n"; $y -= $size + 14; }
        $objs = ['<< /Type /Catalog /Pages 2 0 R >>', '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
                 '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 5 0 R >> >> /Contents 4 0 R >>',
                 "<< /Length ".strlen($stream)." >>\nstream\n$stream\nendstream", '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>'];
        $pdf = "%PDF-1.4\n"; $off = [];
        foreach ($objs as $i => $o) { $off[] = strlen($pdf); $pdf .= ($i + 1)." 0 obj\n$o\nendobj\n"; }
        $x = strlen($pdf);
        $pdf .= "xref\n0 ".(count($objs) + 1)."\n0000000000 65535 f \n";
        foreach ($off as $o) $pdf .= sprintf("%010d 00000 n \n", $o);
        return $pdf."trailer\n<< /Size ".(count($objs) + 1)." /Root 1 0 R >>\nstartxref\n$x\n%%EOF";
    }
}
