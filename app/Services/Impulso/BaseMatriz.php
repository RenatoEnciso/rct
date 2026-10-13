<?php
namespace App\Services\Impulso;

use App\Models\{ExpedienteGobierno, PagoPrograma};
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Cell\{Coordinate, DataType};
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\{Alignment, Border, Fill};
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Etapa 4 — Base matriz IMPULSO MYPERÚ: vista consolidada de cada crédito honrado
 * (datos del crédito, lote y fechas, montos, documentación y recuperos pari passu).
 * No es un Excel aparte: se arma con las demás etapas y se exporta al formato del Excel del área.
 */
class BaseMatriz
{
    public const COLUMNAS = ['ID', 'DNI / RUC', 'TIPO PERSONA', 'CRÉDITO SICMACT', 'CUENTA', 'OPERACIÓN', 'ZONA', 'TIENDA', 'CLIENTE',
        'N° LOTE', 'FECHA DE CORTE', 'FECHA DE SOLICITUD', 'FECHA DE HONRAMIENTO', 'SALDO INSOLUTO', 'INTERÉS DEVENGADO', 'FACTOR COBERTURA',
        'MONTO HONRADO', 'ESTADO DOCUMENTACIÓN', 'N° DOC. FALTANTES', 'CAPITAL PAGADO POST-HONRAMIENTO', 'REVERSIÓN ACUMULADA',
        'REVERSIÓN DEVUELTA', 'REVERSIÓN PENDIENTE'];

    /** Expedientes de la base matriz con su pari passu (filtros: lote_id, zona_id, documentacion, recupero, honramiento). */
    public function filas(array $f = []): Collection
    {
        $exps = ExpedienteGobierno::with('lote', 'zona', 'tienda')
            ->where('estado_honramiento', $f['honramiento'] ?? 'Honrado')
            ->when($f['lote_id'] ?? null, fn($q, $v) => $q->where('lote_id', $v))
            ->when($f['zona_id'] ?? null, fn($q, $v) => $q->where('zona_id', $v))
            ->when($f['documentacion'] ?? null, fn($q, $v) => $q->whereIn('estado', \App\Http\Controllers\Impulso\RevisionController::GRUPOS[$v] ?? [$v]))
            ->when($f['q'] ?? null, fn($q, $v) => $q->where(fn($w) => $w->where('cliente', 'like', "%$v%")->orWhere('dni', 'like', "%$v%")->orWhere('credito_sicmact', 'like', "%$v%")))
            ->orderBy('lote_id')->orderBy('cliente')->get();

        $pagos = PagoPrograma::whereIn('credito_sicmact', $exps->pluck('credito_sicmact'))->get(['credito_sicmact', 'capital', 'reversion', 'estado'])
            ->groupBy('credito_sicmact');
        foreach ($exps as $e) $e->pari = Calculos::pariPassu($pagos->get($e->credito_sicmact, collect()));

        if (!empty($f['recupero'])) $exps = $exps->filter(fn($e) => bccomp($e->pari['capital_pagado'], '0', 2) > 0)->values();
        return $exps;
    }

    public function totales(Collection $filas): array
    {
        return [
            'creditos'  => $filas->count(),
            'saldo'     => Calculos::sumar($filas->pluck('saldo_insoluto')),
            'honrado'   => Calculos::sumar($filas->pluck('monto_honrado')),
            'capital'   => Calculos::sumar($filas->pluck('pari.capital_pagado')),
            'reversion' => Calculos::sumar($filas->pluck('pari.reversion_acumulada')),
            'devuelta'  => Calculos::sumar($filas->pluck('pari.devuelta')),
            'pendiente' => Calculos::sumar($filas->pluck('pari.pendiente')),
        ];
    }

    public function valores(ExpedienteGobierno $e): array
    {
        return [$e->id_externo, $e->dni, $e->tipo_persona === 'juridica' ? 'JURÍDICA' : 'NATURAL', $e->credito_sicmact, $e->cuenta_bt, $e->operacion,
            mb_strtoupper($e->zona->nombre ?? ''), mb_strtoupper($e->tienda->nombre ?? ''), $e->cliente, $e->lote?->codigo,
            $e->lote?->fecha_corte?->format('d/m/Y'), ($e->lote?->fecha_carga ?? $e->lote?->fecha_solicitud)?->format('d/m/Y'),
            $e->fecha_honramiento?->format('d/m/Y'), (float) $e->saldo_insoluto, (float) $e->interes_devengado, (float) $e->factor_cobertura,
            (float) $e->monto_honrado, mb_strtoupper($e->estado), (int) $e->faltantes, (float) $e->pari['capital_pagado'],
            (float) $e->pari['reversion_acumulada'], (float) $e->pari['devuelta'], (float) $e->pari['pendiente']];
    }

    /** Exporta al formato del Excel de la base matriz (columnas supuestas: docs/REGLAS_DEMO_IMPULSO.md). */
    public function exportar(Collection $filas, string $ruta): void
    {
        $libro = new Spreadsheet();
        $h = $libro->getActiveSheet()->setTitle('IMPULSO MYPERU');
        foreach (self::COLUMNAS as $i => $t) $h->setCellValue(Coordinate::stringFromColumnIndex($i + 1).'1', $t);
        $ult = Coordinate::stringFromColumnIndex(count(self::COLUMNAS));
        $h->getStyle("A1:{$ult}1")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'A3182C']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
        ]);
        $h->getRowDimension(1)->setRowHeight(42);
        $fila = 2;
        foreach ($filas as $e) {
            foreach ($this->valores($e) as $j => $v) {
                $celda = Coordinate::stringFromColumnIndex($j + 1).$fila;
                in_array($j, [0, 1, 3, 4, 5], true) && $v !== null ? $h->setCellValueExplicit($celda, (string) $v, DataType::TYPE_STRING) : $h->setCellValue($celda, $v);
            }
            $fila++;
        }
        $t = $this->totales($filas);
        $h->setCellValue("I{$fila}", 'TOTAL ('.$t['creditos'].' créditos)');
        foreach (['N' => 'saldo', 'Q' => 'honrado', 'T' => 'capital', 'U' => 'reversion', 'V' => 'devuelta', 'W' => 'pendiente'] as $col => $k) {
            $h->setCellValue("{$col}{$fila}", (float) $t[$k]);
        }
        $h->getStyle("A{$fila}:{$ult}{$fila}")->getFont()->setBold(true);
        $h->getStyle("N2:O{$fila}")->getNumberFormat()->setFormatCode('#,##0.00');
        $h->getStyle("P2:P{$fila}")->getNumberFormat()->setFormatCode('0.0000');
        $h->getStyle("Q2:Q{$fila}")->getNumberFormat()->setFormatCode('#,##0.00');
        $h->getStyle("T2:W{$fila}")->getNumberFormat()->setFormatCode('#,##0.00');
        $h->getStyle("A1:{$ult}{$fila}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('D6D9DE');
        foreach ([1 => 9, 2 => 13, 3 => 11, 4 => 16, 5 => 11, 6 => 11, 7 => 14, 8 => 22, 9 => 34, 10 => 10, 11 => 12, 12 => 12, 13 => 13,
                  14 => 14, 15 => 13, 16 => 10, 17 => 14, 18 => 22, 19 => 10, 20 => 16, 21 => 14, 22 => 14, 23 => 14] as $c => $w) {
            $h->getColumnDimension(Coordinate::stringFromColumnIndex($c))->setWidth($w);
        }
        $h->freezePane('E2');
        $h->setAutoFilter("A1:{$ult}1");
        (new Xlsx($libro))->save($ruta);
    }
}
