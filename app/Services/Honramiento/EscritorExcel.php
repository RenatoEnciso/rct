<?php
namespace App\Services\Honramiento;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\{Alignment, Border, Fill};
use PhpOffice\PhpSpreadsheet\Cell\{Coordinate, DataType};
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/** Genera el Excel de revisión con las columnas y el orden del archivo del área. */
class EscritorExcel
{
    /**
     * @param array $filas Cada fila: valores en el orden de FormatoExcel::encabezados()
     */
    public function guardar(array $filas, string $ruta, string $hoja = 'Revisión expedientes', array $instrucciones = []): void
    {
        $libro = new Spreadsheet();
        $s = $libro->getActiveSheet()->setTitle($hoja);
        $enc = FormatoExcel::encabezados();

        foreach ($enc as $i => $titulo) {
            $s->setCellValue(Coordinate::stringFromColumnIndex($i + 1).'1', $titulo);
        }
        $ultimaCol = Coordinate::stringFromColumnIndex(count($enc));
        $s->getStyle("A1:{$ultimaCol}1")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'A3182C']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
        ]);
        $s->getRowDimension(1)->setRowHeight(42);

        // Texto explícito para DNI, crédito, cuenta y operación (no perder ceros ni precisión)
        $columnasTexto = [4, 5, 6, 7, 8];
        foreach ($filas as $n => $fila) {
            foreach (array_values($fila) as $i => $valor) {
                $celda = Coordinate::stringFromColumnIndex($i + 1).($n + 2);
                if (in_array($i + 1, $columnasTexto) && $valor !== null && $valor !== '') {
                    $s->setCellValueExplicit($celda, (string) $valor, DataType::TYPE_STRING);
                } else {
                    $s->setCellValue($celda, $valor);
                }
            }
        }

        $total = count($filas) + 1;
        if ($total > 1) {
            $s->getStyle("A2:{$ultimaCol}{$total}")->getFont()->setSize(10);
            $s->getStyle("L2:L{$total}")->getNumberFormat()->setFormatCode('#,##0.00');
            $s->getStyle("O2:Y{$total}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $s->getStyle("A1:{$ultimaCol}{$total}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('D6D9DE');
        }
        foreach ([1 => 12, 2 => 13, 3 => 13, 4 => 10, 5 => 11, 6 => 15, 7 => 11, 8 => 11, 9 => 12, 10 => 22, 11 => 34, 12 => 14, 13 => 11, 14 => 20] as $c => $ancho) {
            $s->getColumnDimension(Coordinate::stringFromColumnIndex($c))->setWidth($ancho);
        }
        for ($c = 15; $c <= 24; $c++) $s->getColumnDimension(Coordinate::stringFromColumnIndex($c))->setWidth(13);
        $s->getColumnDimension('Y')->setWidth(13);
        $s->getColumnDimension('Z')->setWidth(48);
        $s->freezePane('A2');
        $s->setAutoFilter("A1:{$ultimaCol}1");

        if ($instrucciones) {
            $info = $libro->createSheet()->setTitle('Instrucciones');
            foreach ($instrucciones as $i => $linea) $info->setCellValue('A'.($i + 1), $linea);
            $info->getStyle('A1')->getFont()->setBold(true)->setSize(12);
            $info->getColumnDimension('A')->setWidth(120);
            $libro->setActiveSheetIndex(0);
        }

        (new Xlsx($libro))->save($ruta);
    }
}
