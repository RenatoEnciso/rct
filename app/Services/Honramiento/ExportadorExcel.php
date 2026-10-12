<?php
namespace App\Services\Honramiento;

use App\Models\ExpedienteGobierno;
use Illuminate\Support\Collection;

/**
 * Convierte los expedientes al Excel de revisión del área (mismas columnas y orden).
 * El archivo exportado puede volver a importarse sin diferencias.
 */
class ExportadorExcel
{
    public function __construct(private Checklist $checklist) {}

    /** @param Collection<ExpedienteGobierno> $expedientes */
    public function filas(Collection $expedientes): array
    {
        return $expedientes->map(fn($e) => $this->fila($e))->all();
    }

    public function fila(ExpedienteGobierno $e): array
    {
        $cols = $this->checklist->porColumnaExcel($e);
        $docs = [];
        for ($c = 1; $c <= 10; $c++) $docs[] = $this->checklist->valorExcel($cols[$c]['estado']);

        return array_merge([
            $e->gestion->nombre ?? '',
            $e->revisado_central_en?->format('d/m/Y') ?? '',
            $e->revisado_zona_en?->format('d/m/Y') ?? '',
            $e->id_externo,
            $e->dni,
            $e->credito_sicmact,
            $e->cuenta_bt,
            $e->operacion,
            mb_strtoupper($e->zona->nombre ?? ''),
            $this->tienda($e->tienda->nombre ?? ''),
            $e->cliente,
            $e->monto_honrado !== null ? (float) $e->monto_honrado : null,
            $e->lote->codigo ?? '',
            $this->estado($e->estado),
        ], $docs, [
            $e->faltantes,
            $this->observaciones($e),
        ]);
    }

    /** Estado en el vocabulario del Excel. Revisado y Conforme siguen siendo COMPLETO; Observado cuenta como INCOMPLETO. */
    public function estado(string $estado): string
    {
        return match ($estado) {
            'Completo', 'Revisado por zona', 'Conforme' => 'COMPLETO',
            'Incompleto definitivo' => 'INCOMPLETO DEFINITIVO',
            'Pendiente' => 'PENDIENTE',
            default => 'INCOMPLETO',
        };
    }

    private function tienda(string $nombre): string
    {
        $n = mb_strtoupper($nombre);
        return $n === '' || preg_match('/^(TIENDA|AGENCIA|OFICINA)\s/', $n) ? $n : 'TIENDA '.$n;
    }

    /** Observaciones calculadas + observación registrada en RCT (si la hay). */
    private function observaciones(ExpedienteGobierno $e): string
    {
        return collect([
            $e->estado === 'Observado' ? 'Observado en revisión' : null,
            $this->checklist->observaciones($e) ?: null,
            $e->observaciones ?: null,
        ])->filter()->implode(' | ');
    }
}
