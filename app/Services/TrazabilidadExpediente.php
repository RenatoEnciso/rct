<?php
namespace App\Services;

use App\Models\{PropuestaFud, Seguimiento, Sustento, SustentoDocumento};
use App\Services\Validacion\ResultadoValidacion;
use App\Support\Estilo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Reconstruye la historia completa de una operación (todas las versiones del
 * FUD, decisiones, adjuntos, sustentos, validación automática y revisión)
 * para la pantalla de trazabilidad y su PDF.
 */
class TrazabilidadExpediente
{
    public function construir(PropuestaFud $fud): array
    {
        $versiones = $this->cadena($fud);
        $vigente   = $versiones->last();
        $ids       = $versiones->pluck('id');
        $cliente   = $vigente->cliente()->with('tienda', 'zona')->first();

        $sustento = Sustento::with(['cargador.rol', 'revisador.rol'])
            ->whereIn('propuesta_fud_id', $ids)->latest('id')->first();
        $documentos = $sustento
            ? SustentoDocumento::where('sustento_id', $sustento->id)->orderBy('cargado_en')->orderBy('id')->get()
            : collect();

        return [
            'versiones'  => $versiones,
            'vigente'    => $vigente,
            'cliente'    => $cliente,
            'detalle'    => $vigente->getDetalle(),
            'sustento'   => $sustento,
            'documentos' => $documentos->map(fn($d) => $this->documento($d, $sustento)),
            'adjuntos'   => $versiones->flatMap(fn($v) => $v->adjuntos->map(fn($a) => [
                'version' => $this->nombreVersion($v, $versiones),
                'nombre'  => $a->nombre_archivo,
                'tipo'    => $a->tipo_adjunto,
                'por'     => $a->subidor->name ?? '—',
                'fecha'   => $a->created_at ? Carbon::parse($a->created_at) : null,
                'tamano'  => $a->size_formateado,
            ]))->unique(fn($a) => $a['nombre'].$a['version']),
            'eventos'    => $this->eventos($versiones, $sustento, $ids),
            'resumen'    => $this->resumen($vigente, $sustento),
            'generado'   => now(),
        ];
    }

    /** Todas las versiones del FUD, de la primera a la vigente. */
    private function cadena(PropuestaFud $fud): Collection
    {
        $raiz = $fud;
        while ($raiz->parent_fud_id && ($p = PropuestaFud::find($raiz->parent_fud_id))) $raiz = $p;

        $cadena = collect([$raiz]);
        $actual = $raiz;
        while ($hija = $actual->versiones()->latest('id')->first()) {
            $cadena->push($hija);
            $actual = $hija;
        }
        return $cadena->each->load('enviador.rol', 'aprobador.rol', 'aprobadoPor.rol', 'adjuntos.subidor');
    }

    private function nombreVersion(PropuestaFud $v, Collection $versiones): string
    {
        $i = $versiones->search(fn($x) => $x->id === $v->id);
        return $i === 0 ? 'Envío inicial' : "Corrección {$i}";
    }

    /** Decisión real del aprobador: una versión reemplazada por corrección fue "Observada". */
    private function decision(PropuestaFud $v): ?string
    {
        if (!$v->decidido_en && !$v->fecha_decision) return null;
        return $v->versiones()->exists() ? 'Observado' : $v->estado_fud;
    }

    private function eventos(Collection $versiones, ?Sustento $sustento, Collection $ids): Collection
    {
        $ev = collect();
        $porWhatsapp = Seguimiento::whereIn('propuesta_fud_id', $ids)->where('tipo_operacion', 'FUD enviado')
            ->where('descripcion', 'like', '%WhatsApp%')->exists();

        foreach ($versiones as $i => $v) {
            $ev->push([
                'fecha'   => $v->created_at,
                'titulo'  => $i === 0 ? 'Propuesta FUD enviada'.($porWhatsapp ? ' (canal WhatsApp)' : '') : "Corrección {$i} enviada",
                'actor'   => $this->actor($v->enviador),
                'detalle' => "{$v->tipo_negociacion_desc} · nivel de autonomía {$v->nivel_autonomia_req} · asignado a ".($v->aprobador->name ?? '—')
                             .($v->adjuntos->count() ? ' · '.$v->adjuntos->count().' archivo(s) adjunto(s)' : ''),
                'estado'  => 'info',
            ]);

            if ($dec = $this->decision($v)) {
                $ev->push([
                    'fecha'   => $v->decidido_en ?? $v->fecha_decision,
                    'titulo'  => match ($dec) { 'Aprobado' => 'Propuesta aprobada', 'Observado' => 'Propuesta observada', default => 'Propuesta rechazada' },
                    'actor'   => $this->actor($v->aprobadoPor),
                    'detalle' => $v->comentario_aprobador ?: 'Sin comentario.',
                    'estado'  => match ($dec) { 'Aprobado' => 'ok', 'Observado' => 'warn', default => 'danger' },
                ]);
            }
        }

        if ($sustento) {
            // La aprobación habilita la carga de sustentos
            $ev->push([
                'fecha'   => $versiones->last()->decidido_en ?? $sustento->created_at,
                'titulo'  => 'Sustentos habilitados para carga',
                'actor'   => 'Sistema RCT',
                'detalle' => 'Responsable de la carga: '.($sustento->cargador->name ?? '—'),
                'estado'  => 'info',
            ]);

            // Cargas, recargas y revisiones registradas en la bitácora (conservan la hora de cada evento)
            $bitacora = Seguimiento::with('user.rol')->whereIn('propuesta_fud_id', $ids)
                ->whereIn('tipo_operacion', ['Carga de sustentos', 'Recarga de sustentos', 'Revisión de sustentos'])
                ->orderBy('created_at')->get();

            foreach ($bitacora as $s) {
                $esRevision = $s->tipo_operacion === 'Revisión de sustentos';
                $ev->push([
                    'fecha'   => $s->created_at ? Carbon::parse($s->created_at) : $s->fecha_accion,
                    'titulo'  => $esRevision
                        ? 'Revisión de sustentos: '.($s->estado === 'Aprobado' ? 'conforme' : mb_strtolower($s->estado))
                        : ($s->tipo_operacion === 'Recarga de sustentos' ? 'Documentos corregidos y recargados' : 'Sustentos cargados'),
                    'actor'   => $this->actor($s->user),
                    'detalle' => $s->descripcion ?: ($esRevision ? null : 'Validación automática aplicada a cada documento.'),
                    'estado'  => $esRevision ? match ($s->estado) { 'Aprobado' => 'ok', 'Observado' => 'warn', 'Rechazado' => 'danger', default => 'info' } : 'info',
                ]);
            }

            // Si la bitácora no registró la última revisión, se toma del sustento
            if ($sustento->fecha_revision && !$bitacora->where('tipo_operacion', 'Revisión de sustentos')->count()) {
                $ev->push([
                    'fecha'   => $sustento->fecha_revision,
                    'titulo'  => 'Revisión de sustentos: '.($sustento->estado === 'Aprobado' ? 'conforme' : mb_strtolower($sustento->estado)),
                    'actor'   => $this->actor($sustento->revisador),
                    'detalle' => $sustento->comentario_revision,
                    'estado'  => match ($sustento->estado) { 'Aprobado' => 'ok', 'Observado' => 'warn', 'Rechazado' => 'danger', default => 'info' },
                ]);
            }
        }

        return $ev->filter(fn($e) => $e['fecha'])->sortBy(fn($e) => Carbon::parse($e['fecha'])->timestamp)->values();
    }

    /** Documento de sustento con su validación automática y el resultado de la revisión humana. */
    private function documento(SustentoDocumento $d, ?Sustento $s): array
    {
        $revisor = $s?->revisador?->name;
        $automatica = ResultadoValidacion::etiqueta($d->estado_validacion);

        $resultado = match (true) {
            !$d->vigente                                            => 'Reemplazado por una versión corregida',
            // Aprobado pese a observaciones de la validación automática = conformidad manual del revisor
            $d->estado_revision === 'aprobado' && ($d->conforme_manual || $d->tieneObservacionesCarga())
                                                                     => 'Conforme manual'.($revisor ? " — {$revisor}" : ''),
            $d->estado_revision === 'aprobado'                      => 'Validación automática conforme, ratificada'.($revisor ? " por {$revisor}" : ''),
            $d->estado_revision === 'observado'                     => 'Observado en revisión'.($revisor ? " por {$revisor}" : ''),
            default                                                  => 'Solo validación automática (pendiente de revisión)',
        };

        return [
            'tipo'        => Estilo::tipoDoc($d->tipo_doc),
            'nombre'      => $d->nombre_archivo,
            'tamano'      => Estilo::tamano($d->size_bytes),
            'cargado_en'  => $d->cargado_en,
            'automatica'  => $automatica,
            'auto_estado' => $d->estado_validacion,
            'hallazgos'   => collect($d->hallazgos ?? [])->pluck('mensaje')->all(),
            'resultado'   => $resultado,
            'comentario'  => $d->comentario_revisor,
            'vigente'     => (bool) $d->vigente,
            'manual'      => (bool) $d->conforme_manual,
        ];
    }

    private function resumen(PropuestaFud $v, ?Sustento $s): array
    {
        $etapa = match (true) {
            $v->estado_fud === 'En revisión'          => 'Propuesta en revisión del aprobador',
            $v->estado_fud === 'Observado'            => 'Propuesta observada: pendiente de corrección del ejecutivo',
            $v->estado_fud === 'Rechazado'            => 'Propuesta rechazada (cerrada)',
            !$s || $s->estado === 'Pendiente'         => 'Aprobada: pendiente de carga de sustentos',
            $s->estado === 'En revisión'              => 'Aprobada: sustentos en revisión',
            $s->estado === 'Observado'                => 'Aprobada: sustentos observados, pendiente de corrección',
            $s->estado === 'Rechazado'                => 'Aprobada: sustentos rechazados',
            default                                    => 'Conciliación completa: sustentos conformes',
        };

        return [
            'etapa'           => $etapa,
            'estado_fud'      => $v->estado_fud,
            'estado_sustento' => $s?->estado,
        ];
    }

    private function actor($user): string
    {
        if (!$user) return '—';
        return $user->name.($user->rol ? ' · '.$user->rol->nombre : '');
    }
}
