<?php
namespace App\Services\Honramiento;

use App\Models\{ExpedienteGobierno, GobTipoDocumento};
use Illuminate\Support\Collection;

/**
 * Reglas del checklist de honramiento (criterio del área de Recuperaciones):
 * - Completo = tiene los documentos que exige COFIDE según el tipo de persona
 *   (natural: 6 · jurídica: 7). Los complementarios no bloquean.
 * - Pendiente = no subió ningún documento.
 * - Incompleto = falta uno o más documentos exigidos por COFIDE.
 * - Incompleto definitivo = los exigidos que faltan fueron declarados "no disponibles"
 *   por la tienda tras la búsqueda (queda incompleto de forma permanente).
 * - Observado / Revisado por zona / Conforme = resultado de la revisión.
 * N° faltantes = exigidos por COFIDE sin presentar, observados o no disponibles.
 */
class Checklist
{
    public const ETIQUETAS = [
        'falta'         => 'Falta',
        'cargado'       => 'Cargado',
        'conforme'      => 'Conforme',
        'observado'     => 'Observado',
        'no_disponible' => 'No disponible',
    ];

    /** @return Collection<int, array{tipo: GobTipoDocumento, doc: ?\App\Models\ExpedienteGobiernoDoc, estado: string, exigido: bool}> */
    public function documentos(ExpedienteGobierno $e): Collection
    {
        $docs = $e->relationLoaded('documentos') ? $e->documentos : $e->documentos()->get();
        $porTipo = $docs->sortByDesc('id')->unique('tipo_documento_id')->keyBy('tipo_documento_id');

        $tipoPersona = $e->tipo_persona ?? 'natural';

        // Aplica: exigido para su tipo de persona, o complementario común a ambos.
        // (DNI solo aplica a persona natural; Reporte tributario y Ficha RUC solo a jurídica.)
        return GobTipoDocumento::catalogo()
            ->filter(fn($t) => $t->exigidoPara($tipoPersona) || (!$t->cofide_natural && !$t->cofide_juridica))
            ->map(fn($t) => [
                'tipo'    => $t,
                'doc'     => $d = $porTipo->get($t->id),
                'estado'  => $d?->estado ?? 'falta',
                'exigido' => $t->exigidoPara($tipoPersona),
            ])->values();
    }

    public function calcular(ExpedienteGobierno $e): array
    {
        $docs = $this->documentos($e);
        $exig = $docs->where('exigido', true);
        $faltantes = $exig->whereIn('estado', ['falta', 'observado', 'no_disponible'])->count();
        $algoPresentado = $docs->whereIn('estado', ['cargado', 'conforme', 'observado', 'no_disponible'])->count() > 0;

        $estado = match (true) {
            $exig->contains('estado', 'observado')     => 'Observado',
            !$algoPresentado                           => 'Pendiente',
            $exig->contains('estado', 'falta')         => 'Incompleto',
            $exig->contains('estado', 'no_disponible') => 'Incompleto definitivo',
            (bool) $e->revisado_central_en             => 'Conforme',
            (bool) $e->revisado_zona_en                => 'Revisado por zona',
            default                                    => 'Completo',
        };

        return ['estado' => $estado, 'faltantes' => $faltantes];
    }

    public function recalcular(ExpedienteGobierno $e): void
    {
        $e->unsetRelation('documentos');
        $e->forceFill($this->calcular($e))->saveQuietly();
    }

    public function observaciones(ExpedienteGobierno $e): string
    {
        $docs = $this->documentos($e);
        $nombres = fn($c) => $c->map(fn($d) => $d['tipo']->nombre_corto)->implode(', ');
        $exig = $docs->where('exigido', true);
        $comp = $docs->where('exigido', false);

        return collect([
            ($x = $exig->where('estado', 'falta'))->count()         ? 'Falta (COFIDE): '.$nombres($x) : null,
            ($x = $exig->where('estado', 'observado'))->count()     ? 'Observado: '.$nombres($x) : null,
            ($x = $exig->where('estado', 'no_disponible'))->count() ? 'No disponible: '.$nombres($x) : null,
            ($x = $comp->where('estado', 'falta'))->count()         ? 'Complementarios faltantes: '.$nombres($x) : null,
        ])->filter()->implode(' | ');
    }

    /**
     * Estado agregado por columna del Excel (1..10). La columna 1 agrupa
     * DNI (natural) o Reporte tributario + Ficha RUC (jurídica).
     */
    public function porColumnaExcel(ExpedienteGobierno $e): array
    {
        $docs = $this->documentos($e);
        $orden = ['observado' => 0, 'no_disponible' => 1, 'falta' => 2, 'cargado' => 3, 'conforme' => 4];
        $cols = [];
        for ($c = 1; $c <= 10; $c++) {
            $grupo = $docs->filter(fn($d) => $d['tipo']->columna_excel === $c);
            if ($c === 1) {
                $grupo = $grupo->filter(fn($d) => $d['exigido']);   // según tipo de persona
            }
            $peor = $grupo->sortBy(fn($d) => $orden[$d['estado']])->first();
            $cols[$c] = [
                'estado'  => $peor['estado'] ?? 'falta',
                'exigido' => $grupo->contains('exigido', true),
                'nombre'  => $grupo->map(fn($d) => $d['tipo']->nombre_corto)->implode(' + '),
            ];
        }
        return $cols;
    }

    /** 1 = presentado (cargado o conforme) · 0 = falta, observado o no disponible. */
    public function valorExcel(string $estado): int
    {
        return in_array($estado, ['cargado', 'conforme']) ? 1 : 0;
    }

    /** Tipo de persona a partir del documento: DNI y RUC 10 → natural; RUC 20 → jurídica. */
    public static function tipoPersona(?string $documento): string
    {
        $d = preg_replace('/\D/', '', (string) $documento);
        return strlen($d) === 11 && str_starts_with($d, '20') ? 'juridica' : 'natural';
    }
}
