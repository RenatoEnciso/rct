<?php
namespace App\Services;

use App\Models\{ExpedienteGobierno, PropuestaFud, Sustento, User};
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Notificaciones calculadas a partir de la actividad real (decisiones, cargas,
 * revisiones) y recordatorios de tareas pendientes. Solo la lectura se guarda
 * (tabla notificacion_lecturas). Los recordatorios se renuevan cada día y
 * desaparecen solos cuando la tarea se completa.
 *
 * Cada notificación: clave, categoria (decision|solicitud|sustento|recordatorio),
 * titulo, mensaje, url, fecha, icono, color (ok|warn|danger|info), leida.
 */
class Notificaciones
{
    private static array $cache = [];

    public function paraUsuario(User $u): Collection
    {
        if (isset(self::$cache[$u->id])) return self::$cache[$u->id];

        $items = $u->esGrupoFud() ? $this->ejecutivo($u) : $this->aprobador($u);

        $leidas = DB::table('notificacion_lecturas')->where('user_id', $u->id)
            ->whereIn('clave', $items->pluck('clave'))->pluck('clave')->flip();

        return self::$cache[$u->id] = $items
            ->map(fn($n) => $n + ['leida' => isset($leidas[$n['clave']])])
            ->sortByDesc(fn($n) => [$n['categoria'] === 'recordatorio' ? 1 : 0, $n['fecha']->timestamp])
            ->values();
    }

    public function noLeidas(User $u): int
    {
        return $this->paraUsuario($u)->where('leida', false)->count();
    }

    public function marcarLeida(User $u, string $clave): void
    {
        DB::table('notificacion_lecturas')->insertOrIgnore(['user_id' => $u->id, 'clave' => $clave, 'leida_en' => now()]);
        unset(self::$cache[$u->id]);
    }

    public function marcarTodas(User $u): void
    {
        $filas = $this->paraUsuario($u)->where('leida', false)
            ->map(fn($n) => ['user_id' => $u->id, 'clave' => $n['clave'], 'leida_en' => now()])->values()->all();
        if ($filas) DB::table('notificacion_lecturas')->insertOrIgnore($filas);
        unset(self::$cache[$u->id]);
    }

    // ── Ejecutivo ────────────────────────────────────────────

    private function ejecutivo(User $u): Collection
    {
        $n = collect();
        $desde = now()->subDays($this->ventana());
        $hoy = now()->toDateString();

        // Decisiones sobre mis propuestas
        $decididas = PropuestaFud::with('cliente', 'aprobadoPor')->withCount('versiones')
            ->where('enviado_por', $u->id)->where('decidido_en', '>=', $desde)->get();
        foreach ($decididas as $f) {
            $dec = $f->versiones_count ? 'Observado' : $f->estado_fud;
            $cod = $this->codigo($f);
            $quien = $f->aprobadoPor->name ?? 'El aprobador';
            $n->push($this->item("dec:{$f->id}", 'decision', $f->decidido_en, route('fud.show', $f->ultimaVersion()), ...match ($dec) {
                'Aprobado'  => ['Propuesta aprobada', "{$quien} aprobó {$cod} de {$f->cliente->nombre_cliente}. Ya puede cargar los sustentos.", 'bi-check-circle', 'ok'],
                'Observado' => ['Propuesta observada', "{$quien} observó {$cod} de {$f->cliente->nombre_cliente}: «".str($f->comentario_aprobador)->limit(90)."»", 'bi-arrow-return-left', 'warn'],
                default     => ['Propuesta rechazada', "{$quien} rechazó {$cod} de {$f->cliente->nombre_cliente}.", 'bi-x-circle', 'danger'],
            }));
        }

        // Resultado de la revisión de mis sustentos
        $revisados = Sustento::with('propuesta.cliente', 'revisador')->where('cargado_por', $u->id)
            ->where('fecha_revision', '>=', $desde)->whereIn('estado', ['Aprobado', 'Observado', 'Rechazado'])->get();
        foreach ($revisados as $s) {
            $cli = $s->propuesta->cliente->nombre_cliente;
            $n->push($this->item("rev:{$s->id}:{$s->fecha_revision->timestamp}", 'sustento', $s->fecha_revision, route('sustentos.index'), ...match ($s->estado) {
                'Aprobado'  => ['Sustentos conformes', "Los sustentos de {$cli} fueron validados por ".($s->revisador->name ?? 'Recuperaciones').'.', 'bi-shield-check', 'ok'],
                'Observado' => ['Sustentos observados', "Corrija los documentos observados de {$cli}.".($s->comentario_revision ? ' «'.str($s->comentario_revision)->limit(70).'»' : ''), 'bi-exclamation-triangle', 'warn'],
                default     => ['Sustentos rechazados', "Los sustentos de {$cli} fueron rechazados.", 'bi-x-octagon', 'danger'],
            }));
        }

        // Recordatorio: aprobadas sin sustentos cargados
        $minimo = $this->diasRecordatorio();
        $pendientes = Sustento::with('propuesta.cliente', 'propuesta')->where('cargado_por', $u->id)->where('estado', 'Pendiente')->get();
        foreach ($pendientes as $s) {
            $aprobado = $s->propuesta->decidido_en ?? $s->created_at;
            $dias = (int) $aprobado->diffInDays(now());
            if ($dias < $minimo) continue;
            $n->push($this->item("rec-carga:{$s->id}:{$hoy}", 'recordatorio', now(), route('sustentos.index'),
                "Sustentos sin cargar · {$dias} días",
                "Hace {$dias} días se aprobó la conciliación de {$s->propuesta->cliente->nombre_cliente} y aún no se cargan los sustentos. Súbalos para no dejar el expediente pendiente.",
                'bi-alarm', $dias >= $this->diasAlerta() ? 'danger' : 'warn'));
        }

        // Recordatorio: sustentos observados sin corregir
        $sinCorregir = Sustento::with('propuesta.cliente')->where('cargado_por', $u->id)->where('estado', 'Observado')
            ->where('fecha_revision', '<=', now()->subDay())->get();
        foreach ($sinCorregir as $s) {
            $dias = (int) $s->fecha_revision->diffInDays(now());
            $n->push($this->item("rec-obs:{$s->id}:{$hoy}", 'recordatorio', now(), route('sustentos.index'),
                "Corrección pendiente · {$dias} días", "Los sustentos observados de {$s->propuesta->cliente->nombre_cliente} siguen sin corregirse.",
                'bi-arrow-repeat', $dias >= $this->diasAlerta() ? 'danger' : 'warn'));
        }

        // Recordatorio: propuestas observadas sin corregir
        $observadas = PropuestaFud::with('cliente')->where('enviado_por', $u->id)->vigentes()->where('estado_fud', 'Observado')
            ->where('decidido_en', '<=', now()->subDays($minimo))->get();
        foreach ($observadas as $f) {
            $dias = (int) $f->decidido_en->diffInDays(now());
            $n->push($this->item("rec-fud:{$f->id}:{$hoy}", 'recordatorio', now(), route('fud.edit', $f),
                "Propuesta por corregir · {$dias} días", $this->codigo($f)." de {$f->cliente->nombre_cliente} fue observada y aún no se corrige.",
                'bi-pencil-square', 'warn'));
        }

        $this->gobiernoTienda($u, $n, $hoy);

        return $n;
    }

    // ── Aprobador / revisor ──────────────────────────────────

    private function aprobador(User $u): Collection
    {
        $n = collect();
        $desde = now()->subDays($this->ventana());
        $hoy = now()->toDateString();
        $minimo = $this->diasRecordatorio();

        // Nuevas solicitudes y correcciones en mi bandeja
        $bandeja = PropuestaFud::with('cliente', 'enviador')->porAprobador($u->id)->enRevision()->get();
        foreach ($bandeja->where('created_at', '>=', $desde) as $f) {
            $correccion = $f->veces_observado > 0;
            $n->push($this->item("nueva:{$f->id}", 'solicitud', $f->created_at, route('bandeja.index'),
                $correccion ? 'Corrección recibida' : 'Nueva solicitud por aprobar',
                "{$f->enviador->name} envió ".($correccion ? 'la corrección de ' : '').$this->codigo($f)." · {$f->cliente->nombre_cliente} · {$f->tipo_negociacion_desc}.",
                $correccion ? 'bi-arrow-repeat' : 'bi-inbox', 'info'));
        }
        $atrasadas = $bandeja->filter(fn($f) => $f->created_at->lte(now()->subDays($minimo)))->count();
        if ($atrasadas) {
            $n->push($this->item("rec-bandeja:{$hoy}", 'recordatorio', now(), route('bandeja.index'),
                'Solicitudes sin atender', "Tiene {$atrasadas} solicitud(es) con más de {$minimo} días en su bandeja.", 'bi-alarm', $atrasadas >= 5 ? 'danger' : 'warn'));
        }

        // Sustentos cargados que esperan revisión (el CZR ve los de su zona)
        $porRevisar = Sustento::with('propuesta.cliente', 'cargador')->withMax('documentos', 'cargado_en')
            ->where('estado', 'En revisión')
            ->when($u->nivel() === 1 && $u->tienda, fn($q) => $q->whereHas('propuesta.cliente', fn($c) => $c->where('zona_id', $u->tienda->zona_id)))
            ->get();
        foreach ($porRevisar as $s) {
            $fecha = $s->documentos_max_cargado_en ? Carbon::parse($s->documentos_max_cargado_en) : ($s->fecha_carga ?? $s->updated_at);
            if ($fecha->lt($desde)) continue;
            $n->push($this->item("carga:{$s->id}:{$fecha->timestamp}", 'sustento', $fecha, route('revision.show', $s->id),
                'Sustentos cargados para revisión',
                ($s->cargador->name ?? 'El ejecutivo')." cargó los sustentos de {$s->propuesta->cliente->nombre_cliente}.",
                'bi-file-earmark-arrow-up', 'info'));
        }
        $antiguos = $porRevisar->filter(fn($s) => ($s->fecha_carga ?? $s->updated_at)->lte(now()->subDays($minimo)))->count();
        if ($antiguos) {
            $n->push($this->item("rec-revision:{$hoy}", 'recordatorio', now(), route('revision.index', ['estado' => 'En revisión']),
                'Sustentos por revisar', "{$antiguos} sustento(s) esperan revisión desde hace más de {$minimo} días.", 'bi-clipboard-check', 'warn'));
        }

        $this->gobiernoRevision($u, $n, $hoy);

        return $n;
    }

    // ── Programas de gobierno (expedientes de honramiento) ───

    private function gobiernoTienda(User $u, Collection $n, string $hoy): void
    {
        if (!$u->tienda_id) return;
        $exps = ExpedienteGobierno::with('lote', 'documentos.tipo')->where('tienda_id', $u->tienda_id)->get();

        // Observados: la tienda debe corregir los documentos
        foreach ($exps->where('estado', 'Observado') as $e) {
            $obs = $e->documentos->where('estado', 'observado');
            $cuando = $obs->max('revisado_en') ?? $e->updated_at;
            $n->push($this->item("gob-obs:{$e->id}:".Carbon::parse($cuando)->timestamp, 'gobierno', $cuando, route('gobierno.show', $e),
                'Expediente de honramiento observado',
                "{$e->cliente} (ID {$e->id_externo}): corrija ".$obs->map(fn($d) => $d->tipo->nombre_corto ?? $d->tipo_doc)->implode(', ').'.',
                'bi-bank', 'warn'));
        }

        // Recordatorio diario: expedientes sin completar y su plazo de 30 días
        $abiertos = $exps->whereIn('estado', ['Pendiente', 'Incompleto']);
        if ($abiertos->count()) {
            $vencidos = $abiertos->filter(fn($e) => $e->situacionPlazo() === 'vencido')->count();
            $porVencer = $abiertos->filter(fn($e) => $e->situacionPlazo() === 'por_vencer')->count();
            $detalle = collect([$vencidos ? "{$vencidos} con plazo vencido" : null, $porVencer ? "{$porVencer} vencen en 7 días o menos" : null])->filter()->implode(' y ');
            $n->push($this->item("rec-gob-tienda:{$hoy}", 'recordatorio', now(), route('gobierno.index'),
                'Expedientes de honramiento por completar',
                "Su tienda tiene {$abiertos->count()} expediente(s) sin los documentos exigidos por COFIDE".($detalle ? ": {$detalle}." : '.'),
                'bi-bank', $vencidos ? 'danger' : 'warn'));
        }
    }

    private function gobiernoRevision(User $u, Collection $n, string $hoy): void
    {
        $permisos = app(\App\Services\Honramiento\Permisos::class);
        $central = $permisos->puedeRevisarCentral($u);
        $visibles = ExpedienteGobierno::visiblePara($u);

        $paraZona = (clone $visibles)->where('estado', 'Completo')->count();
        if ($paraZona && (!$central || $u->nivel() >= 2)) {
            $n->push($this->item("gob-zona:{$hoy}:{$paraZona}", 'gobierno', now(), route('gobierno.index', ['estado' => 'Completo']),
                'Expedientes de honramiento por revisar',
                "{$paraZona} expediente(s) ".($central ? '' : 'de su zona ')."tienen los documentos exigidos por COFIDE y esperan la revisión por zona.",
                'bi-clipboard-check', 'info'));
        }
        if ($central) {
            $paraCentral = ExpedienteGobierno::where('estado', 'Revisado por zona')->count();
            if ($paraCentral) {
                $n->push($this->item("gob-central:{$hoy}:{$paraCentral}", 'gobierno', now(), route('gobierno.index', ['estado' => 'Revisado por zona']),
                    'Expedientes para revisión central', "{$paraCentral} expediente(s) revisados por zona esperan la conformidad central.",
                    'bi-patch-check', 'info'));
            }
            $vencidos = ExpedienteGobierno::with('lote')->whereIn('estado', ['Pendiente', 'Incompleto', 'Observado'])->get()
                ->filter(fn($e) => $e->situacionPlazo() === 'vencido')->count();
            if ($vencidos) {
                $n->push($this->item("rec-gob-vencidos:{$hoy}", 'recordatorio', now(), route('gobierno.index', ['plazo' => 'vencido']),
                    'Expedientes con plazo vencido', "{$vencidos} expediente(s) de honramiento superaron los 30 días de regularización sin completarse.",
                    'bi-alarm', 'danger'));
            }
        }
    }

    // ── Utilidades ───────────────────────────────────────────

    private function item(string $clave, string $categoria, $fecha, string $url, string $titulo, string $mensaje, string $icono, string $color): array
    {
        // Nunca se muestra una fecha futura (p. ej. registros guardados con otra zona horaria)
        $fecha = Carbon::parse($fecha);
        if ($fecha->isFuture()) $fecha = now();

        return compact('clave', 'categoria', 'url', 'titulo', 'mensaje', 'icono', 'color') + ['fecha' => $fecha];
    }

    private function codigo(PropuestaFud $f): string
    {
        return 'FUD-'.str_pad($f->id, 5, '0', STR_PAD_LEFT);
    }

    private function ventana(): int          { return (int) config('rct.notificaciones.ventana_dias', 30); }
    private function diasRecordatorio(): int { return (int) config('rct.notificaciones.dias_recordatorio', 2); }
    private function diasAlerta(): int       { return (int) config('rct.notificaciones.dias_alerta', 7); }
}
