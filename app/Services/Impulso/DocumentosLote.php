<?php
namespace App\Services\Impulso;

use App\Models\Lote;
use App\Support\Estilo;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Cell\{Coordinate, DataType};
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\{Alignment, Border, Fill};
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpWord\{IOFactory, PhpWord};
use PhpOffice\PhpWord\SimpleType\Jc;

/**
 * Documentos del lote para la plataforma de COFIDE:
 * notificación (Word) y plantilla de honramiento (Excel). Formato supuesto: ver docs/REGLAS_DEMO_IMPULSO.md.
 */
class DocumentosLote
{
    public const COLUMNAS_PLANTILLA = ['N°', 'CÓDIGO IFI', 'N° LOTE', 'TIPO DOC.', 'N° DOCUMENTO', 'TIPO PERSONA', 'NOMBRE / RAZÓN SOCIAL',
        'N° CRÉDITO', 'FECHA DESEMBOLSO', 'MONTO DESEMBOLSADO', 'SALDO INSOLUTO', 'INTERÉS DEVENGADO', 'DÍAS DE ATRASO',
        'FACTOR COBERTURA', 'FECHA DE CORTE'];

    public function nombreBase(Lote $lote): string
    {
        return 'Lote_'.preg_replace('/\D/', '', $lote->codigo).'_'.$lote->fecha_corte?->format('Ymd');
    }

    /** Notificación de solicitud de honramiento (Word). */
    public function word(Lote $lote): string
    {
        $exp = $lote->expedientes()->orderBy('cliente')->get();
        $core = \App\Models\CoreCredito::whereIn('credito_sicmact', $exp->pluck('credito_sicmact'))->get()->keyBy('credito_sicmact');

        $w = new PhpWord();
        $w->setDefaultFontName('Calibri');
        $w->setDefaultFontSize(10.5);
        $s = $w->addSection(['marginTop' => 1100, 'marginBottom' => 1000, 'marginLeft' => 1200, 'marginRight' => 1200]);

        $s->addText('CAJA MUNICIPAL DE AHORRO Y CRÉDITO DE TRUJILLO', ['bold' => true, 'color' => 'A3182C', 'size' => 12]);
        $s->addText('Área de Recuperaciones · Programas de Gobierno', ['color' => '5F6B7A', 'size' => 9]);
        $s->addTextBreak();
        $s->addText('Trujillo, '.($lote->fecha_corte ?? now())->translatedFormat('d \d\e F \d\e Y'), [], ['alignment' => Jc::END]);
        $s->addTextBreak();
        $s->addText('Señores', ['bold' => true]);
        $s->addText('CORPORACIÓN FINANCIERA DE DESARROLLO S.A. – COFIDE', ['bold' => true]);
        $s->addText('Gerencia de Programas de Garantía');
        $s->addTextBreak();
        $s->addText('Asunto: Solicitud de honramiento de garantías – Programa '.ucwords(mb_strtolower($lote->programa ?? 'Impulso MyPerú')).' – '.$lote->codigo, ['bold' => true]);
        $s->addTextBreak();
        $s->addText('De nuestra consideración:');
        $s->addText('Por medio de la presente, en el marco del Programa '.($lote->programa ?? 'IMPULSO MYPERÚ').', solicitamos el honramiento de las '
            .'garantías de los créditos que, a la fecha de corte del '.$lote->fecha_corte?->format('d/m/Y').', registran 90 o más días de atraso, '
            .'conforme al detalle adjunto en la plantilla de honramiento cargada en su plataforma:', [], ['alignment' => Jc::BOTH, 'spaceAfter' => 160]);

        $t = $s->addTable(['borderSize' => 4, 'borderColor' => 'CFD4DA', 'cellMargin' => 80]);
        foreach ([['N° de lote', $lote->codigo], ['Fecha de corte', $lote->fecha_corte?->format('d/m/Y')],
                  ['N° de registros', (string) $lote->registros_rct], ['Saldo insoluto total', Estilo::solesPe($lote->saldo_rct)]] as [$k, $v]) {
            $t->addRow();
            $t->addCell(3200, ['bgColor' => 'F4F5F7'])->addText($k, ['bold' => true]);
            $t->addCell(4200)->addText($v);
        }
        $s->addTextBreak();
        $s->addText('Detalle de créditos', ['bold' => true]);
        $d = $s->addTable(['borderSize' => 4, 'borderColor' => 'CFD4DA', 'cellMargin' => 50]);
        $d->addRow();
        foreach (['N°' => 500, 'Documento' => 1300, 'Cliente' => 3300, 'N° crédito' => 1700, 'Días' => 700, 'Saldo insoluto' => 1600] as $h => $ancho) {
            $d->addCell($ancho, ['bgColor' => 'A3182C'])->addText($h, ['bold' => true, 'color' => 'FFFFFF', 'size' => 9]);
        }
        foreach ($exp as $i => $e) {
            $d->addRow();
            foreach ([(string) ($i + 1), $e->dni, $e->cliente, $e->credito_sicmact, (string) ($core[$e->credito_sicmact]->dias_atraso ?? ''),
                      Estilo::solesPe($e->saldo_insoluto)] as $j => $v) {
                $d->addCell()->addText((string) $v, ['size' => 9], $j === 5 ? ['alignment' => Jc::END] : []);
            }
        }
        $s->addTextBreak();
        $s->addText('Sin otro particular, quedamos de ustedes.', [], ['spaceAfter' => 360]);

        $firmas = collect($lote->firmas ?? [])->keyBy('nombre');
        $f = $s->addTable();
        $f->addRow();
        foreach (config('rct.impulso.representantes') as $r) {
            $c = $f->addCell(4600);
            $firma = $firmas->get($r['nombre']);
            $c->addText($firma ? 'Firmado digitalmente' : 'Pendiente de firma digital', ['italic' => true, 'color' => $firma ? '1F7A4D' : '8A94A3', 'size' => 9]);
            if ($firma) $c->addText('Fecha: '.$firma['autorizado_en'], ['color' => '5F6B7A', 'size' => 8]);
            $c->addText('_____________________________');
            $c->addText($r['nombre'], ['bold' => true]);
            $c->addText($r['cargo'], ['size' => 9]);
            $c->addText('Representante ante COFIDE', ['size' => 9, 'color' => '5F6B7A']);
        }

        $ruta = Storage::disk('private')->path('impulso/lotes/Notificacion_'.$this->nombreBase($lote).'.docx');
        @mkdir(dirname($ruta), 0775, true);
        IOFactory::createWriter($w, 'Word2007')->save($ruta);
        return $ruta;
    }

    /** Plantilla de honramiento con el formato de carga de COFIDE (Excel). */
    public function excel(Lote $lote): string
    {
        $exp = $lote->expedientes()->orderBy('cliente')->get();
        $core = \App\Models\CoreCredito::whereIn('credito_sicmact', $exp->pluck('credito_sicmact'))->get()->keyBy('credito_sicmact');

        $libro = new Spreadsheet();
        $h = $libro->getActiveSheet()->setTitle('Honramiento');
        foreach (self::COLUMNAS_PLANTILLA as $i => $t) $h->setCellValue(Coordinate::stringFromColumnIndex($i + 1).'1', $t);
        $ult = Coordinate::stringFromColumnIndex(count(self::COLUMNAS_PLANTILLA));
        $h->getStyle("A1:{$ult}1")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'A3182C']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
        ]);
        $h->getRowDimension(1)->setRowHeight(34);

        $fila = 2;
        foreach ($exp as $i => $e) {
            $c = $core[$e->credito_sicmact] ?? null;
            $valores = [$i + 1, config('rct.impulso.codigo_ifi'), (int) $lote->codigo, $e->tipo_persona === 'juridica' ? 'RUC' : 'DNI', $e->dni,
                $e->tipo_persona === 'juridica' ? 'JURÍDICA' : 'NATURAL', $e->cliente, $e->credito_sicmact, $c?->fecha_desembolso?->format('d/m/Y'),
                (float) ($c?->monto_desembolso ?? 0), (float) $e->saldo_insoluto, (float) $e->interes_devengado, $c?->dias_atraso,
                (float) $e->factor_cobertura, $lote->fecha_corte?->format('d/m/Y')];
            foreach ($valores as $j => $v) {
                $celda = Coordinate::stringFromColumnIndex($j + 1).$fila;
                in_array($j, [1, 4, 7], true) ? $h->setCellValueExplicit($celda, (string) $v, DataType::TYPE_STRING) : $h->setCellValue($celda, $v);
            }
            $fila++;
        }
        $h->setCellValue('G'.$fila, 'TOTAL');
        $h->setCellValue('K'.$fila, '=SUM(K2:K'.($fila - 1).')');
        $h->getStyle("A{$fila}:{$ult}{$fila}")->getFont()->setBold(true);
        $h->getStyle("J2:L{$fila}")->getNumberFormat()->setFormatCode('#,##0.00');
        $h->getStyle("N2:N{$fila}")->getNumberFormat()->setFormatCode('0.0000');
        $h->getStyle("A1:{$ult}{$fila}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('D6D9DE');
        foreach ([1 => 5, 2 => 9, 3 => 8, 4 => 8, 5 => 13, 6 => 11, 7 => 36, 8 => 16, 9 => 12, 10 => 14, 11 => 14, 12 => 13, 13 => 9, 14 => 11, 15 => 12] as $col => $ancho) {
            $h->getColumnDimension(Coordinate::stringFromColumnIndex($col))->setWidth($ancho);
        }
        $h->freezePane('A2');

        $ruta = Storage::disk('private')->path('impulso/lotes/Plantilla_'.$this->nombreBase($lote).'.xlsx');
        @mkdir(dirname($ruta), 0775, true);
        (new Xlsx($libro))->save($ruta);
        return $ruta;
    }
}
