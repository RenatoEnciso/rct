<?php
namespace App\Services\Impulso;

use App\Models\{ExpedienteGobierno, Lote, Seguimiento, User};
use App\Services\Honramiento\FormatoExcel;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\{DB, Storage};
use PhpOffice\PhpSpreadsheet\Cell\{Coordinate, DataType};
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Etapa 2 — Reporte detallado de honramiento de COFIDE (Excel): saldo insoluto, interés devengado,
 * factor de cobertura y monto a honrar por crédito. Formato supuesto: ver docs/REGLAS_DEMO_IMPULSO.md.
 */
class ReporteHonramiento
{
    public const COLUMNAS = ['N° LOTE', 'N° CRÉDITO', 'TIPO DOC.', 'N° DOCUMENTO', 'NOMBRE / RAZÓN SOCIAL', 'SALDO INSOLUTO',
        'INTERÉS DEVENGADO', 'FACTOR COBERTURA', 'MONTO A HONRAR', 'FECHA HONRAMIENTO', 'ESTADO', 'OBSERVACIÓN'];

    public function __construct(private Avisos $avisos) {}

    /** Lee el Excel y lo cruza con los créditos del lote (no guarda nada). */
    public function analizar(Lote $lote, string $ruta): array
    {
        $hoja = IOFactory::load($ruta)->getActiveSheet()->toArray(null, true, false, false);
        $cab = null;
        foreach ($hoja as $i => $f) {
            if (in_array('N CREDITO', array_map(fn($v) => FormatoExcel::clave((string) $v), $f), true)) { $cab = $i; break; }
        }
        if ($cab === null) return ['error' => 'No se encontró la fila de encabezados (N° CRÉDITO, SALDO INSOLUTO, FACTOR COBERTURA…).', 'filas' => []];
        $mapa = array_flip(array_map(fn($v) => FormatoExcel::clave((string) $v), $hoja[$cab]));
        $v = fn($f, $col) => $f[$mapa[FormatoExcel::clave($col)] ?? -1] ?? null;

        $exp = $lote->expedientes()->get()->keyBy('credito_sicmact');
        $filas = [];
        foreach (array_slice($hoja, $cab + 1) as $n => $f) {
            $credito = FormatoExcel::texto($v($f, 'N° CRÉDITO'));
            if ($credito === '') continue;
            $saldo = FormatoExcel::monto($v($f, 'SALDO INSOLUTO'));
            $interes = FormatoExcel::monto($v($f, 'INTERÉS DEVENGADO')) ?? 0;
            $factor = FormatoExcel::monto($v($f, 'FACTOR COBERTURA'));
            if ($factor !== null && $factor > 1) $factor /= 100;                 // admite 95 o 0.95
            $monto = FormatoExcel::monto($v($f, 'MONTO A HONRAR'));
            $fecha = FormatoExcel::fecha($v($f, 'FECHA HONRAMIENTO'));
            $estado = FormatoExcel::clave((string) $v($f, 'ESTADO')) === 'OBSERVADO' ? 'No honrado' : 'Honrado';
            $e = $exp->get($credito);

            $r = ['fila' => $cab + $n + 2, 'credito' => $credito, 'cliente' => trim((string) $v($f, 'NOMBRE / RAZÓN SOCIAL')),
                  'saldo' => $saldo, 'interes' => $interes, 'factor' => $factor, 'monto' => $monto, 'fecha' => $fecha?->toDateString(),
                  'estado' => $estado, 'observacion' => trim((string) $v($f, 'OBSERVACIÓN')), 'expediente_id' => $e?->id,
                  'errores' => [], 'avisos' => []];
            if (!$e) $r['errores'][] = 'El crédito no pertenece al lote '.$lote->codigo.'.';
            if ($estado === 'Honrado') {
                if ($saldo === null || $factor === null || $monto === null) $r['errores'][] = 'Faltan saldo insoluto, factor de cobertura o monto a honrar.';
                if (!$fecha) $r['errores'][] = 'Falta la fecha de honramiento.';
                if ($saldo !== null && $factor !== null && $monto !== null
                    && bccomp(Calculos::montoHonrar($saldo, $interes, $factor), number_format($monto, 2, '.', ''), 2) !== 0) {
                    $r['avisos'][] = 'El monto a honrar no es (saldo + interés) × factor: se usa el monto de COFIDE.';
                }
                if ($e && $saldo !== null && bccomp((string) $e->saldo_insoluto, number_format($saldo, 2, '.', ''), 2) !== 0) {
                    $r['avisos'][] = 'Saldo insoluto distinto al de la solicitud ('.\App\Support\Estilo::solesPe($e->saldo_insoluto).').';
                }
            }
            $filas[] = $r;
        }
        $enReporte = collect($filas)->pluck('credito')->flip();
        $faltantes = $exp->reject(fn($e, $c) => isset($enReporte[$c]))->values()
            ->map(fn($e) => ['credito' => $e->credito_sicmact, 'cliente' => $e->cliente])->all();

        return ['error' => null, 'filas' => $filas, 'faltantes' => $faltantes];
    }

    /** Aplica el reporte: cada crédito pasa a Honrado (o No honrado) con sus montos; el lote queda honrado. Avisa a las agencias. */
    public function importar(Lote $lote, array $analisis, User $usuario, string $archivo, bool $avisar = true): array
    {
        $validas = collect($analisis['filas'])->filter(fn($r) => !$r['errores']);
        throw_if($validas->isEmpty(), new \DomainException('El reporte no tiene filas válidas para el lote.'));

        DB::transaction(function () use ($lote, $validas, $analisis, $usuario, $archivo) {
            foreach ($validas as $r) {
                $e = ExpedienteGobierno::find($r['expediente_id']);
                $e->update($r['estado'] === 'Honrado' ? [
                    'estado_honramiento' => 'Honrado', 'fecha_honramiento' => $r['fecha'],
                    'saldo_insoluto' => $r['saldo'], 'interes_devengado' => $r['interes'],
                    'factor_cobertura' => number_format($r['factor'], 4, '.', ''), 'monto_honrado' => $r['monto'],
                ] : ['estado_honramiento' => 'No honrado', 'fecha_honramiento' => null]);
            }
            // Los créditos del lote que no figuran en el reporte no fueron honrados
            $lote->expedientes()->whereIn('credito_sicmact', collect($analisis['faltantes'])->pluck('credito'))
                ->update(['estado_honramiento' => 'No honrado', 'fecha_honramiento' => null]);

            $honrados = $lote->expedientes()->where('estado_honramiento', 'Honrado')->count();
            $total = $lote->expedientes()->count();
            $lote->update([
                'estado' => $honrados === $total ? 'Honrado' : 'Honrado parcial',
                'fecha_honramiento' => $validas->where('estado', 'Honrado')->max('fecha'),
                'reporte_importado_en' => now(), 'reporte_archivo' => $archivo,
            ]);
            Seguimiento::registrar($usuario->id, 'Honramiento COFIDE', $lote->estado, null, null, $lote->codigo,
                "Reporte {$archivo}: {$honrados} de {$total} créditos honrados del {$lote->nombre()}.");
        });

        if ($avisar) $this->avisos->loteHonrado($lote->fresh());
        return ['honrados' => $lote->expedientes()->where('estado_honramiento', 'Honrado')->count(), 'total' => $lote->expedientes()->count()];
    }

    /** Reporte de ejemplo con el formato de COFIDE (para la demo: simula la descarga de la plataforma). */
    public function ejemplo(Lote $lote, ?Carbon $fecha = null, array $observados = []): string
    {
        $fecha ??= now();
        $libro = new Spreadsheet();
        $h = $libro->getActiveSheet()->setTitle('Reporte honramiento');
        $h->setCellValue('A1', 'COFIDE · Reporte detallado de honramiento · Programa '.$lote->programa);
        $h->getStyle('A1')->getFont()->setBold(true)->setSize(12);
        foreach (self::COLUMNAS as $i => $t) $h->setCellValue(Coordinate::stringFromColumnIndex($i + 1).'3', $t);
        $h->getStyle('A3:L3')->applyFromArray(['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0B3D6B']]]);
        $fila = 4;
        foreach ($lote->expedientes()->orderBy('cliente')->get() as $e) {
            $obs = in_array($e->credito_sicmact, $observados, true);
            $valores = [$lote->codigo, $e->credito_sicmact, $e->tipo_persona === 'juridica' ? 'RUC' : 'DNI', $e->dni, $e->cliente,
                (float) $e->saldo_insoluto, (float) $e->interes_devengado, (float) $e->factor_cobertura,
                (float) Calculos::montoHonrar($e->saldo_insoluto, $e->interes_devengado, $e->factor_cobertura),
                $fecha->format('d/m/Y'), $obs ? 'OBSERVADO' : 'HONRADO', $obs ? 'Garantía observada: documentación de desembolso incompleta' : ''];
            foreach ($valores as $j => $v) {
                $celda = Coordinate::stringFromColumnIndex($j + 1).$fila;
                in_array($j, [1, 3], true) ? $h->setCellValueExplicit($celda, (string) $v, DataType::TYPE_STRING) : $h->setCellValue($celda, $v);
            }
            $fila++;
        }
        foreach (range('A', 'L') as $c) $h->getColumnDimension($c)->setAutoSize(true);
        $ruta = Storage::disk('private')->path('impulso/reportes/Reporte_Honramiento_COFIDE_'.preg_replace('/\D/', '', $lote->codigo).'.xlsx');
        @mkdir(dirname($ruta), 0775, true);
        (new Xlsx($libro))->save($ruta);
        return $ruta;
    }
}
