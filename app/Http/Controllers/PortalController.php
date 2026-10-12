<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Models\{Campana, ExpedienteGobierno, PropuestaFud, Sustento, User};
use App\Services\Notificaciones;

/** Página de inicio (portal) organizada según el rol de quien ingresa. */
class PortalController extends Controller
{
    public function inicio(Request $request, Notificaciones $notificaciones)
    {
        $user = $request->user()->load('rol', 'tienda.zona');
        $campana = Campana::activa();

        return view('portal.inicio', [
            'user'        => $user,
            'campana'     => $campana,
            'vigencia'    => $this->vigencia($campana),
            'tareas'      => $user->esGrupoFud() ? $this->tareasEjecutivo($user) : $this->tareasAprobador($user),
            'indicadores' => $user->esGrupoFud() ? $this->indicadoresEjecutivo($user) : $this->indicadoresCampana(),
            'ranking'     => $user->esGrupoFud() ? $this->ranking($user) : null,
            'fechas'      => $this->fechasClave($campana),
            'equipo'      => $this->equipo($user),
            'actividad'   => $notificaciones->paraUsuario($user)->where('categoria', '!=', 'recordatorio')->take(6),
            'accesos'     => $this->accesos($user),
            'comunicados' => $this->comunicados(),
            'hoja'        => $this->hojaDeRuta(),
        ]);
    }

    private function vigencia(?Campana $c): ?array
    {
        if (!$c) return null;
        $total = max(1, $c->fecha_inicio->diffInDays($c->fecha_fin));
        $pasados = min($total, max(0, $c->fecha_inicio->diffInDays(now(), false)));
        return ['pct' => round($pasados / $total * 100), 'restantes' => max(0, (int) now()->startOfDay()->diffInDays($c->fecha_fin, false))];
    }

    private function tareasEjecutivo(User $u): array
    {
        $sinCargar = Sustento::where('cargado_por', $u->id)->where('estado', 'Pendiente')->count();
        $obsSust   = Sustento::where('cargado_por', $u->id)->where('estado', 'Observado')->count();
        $obsFud    = PropuestaFud::porEnviador($u->id)->vigentes()->where('estado_fud', 'Observado')->count();
        $gob       = $u->tienda_id ? ExpedienteGobierno::where('tienda_id', $u->tienda_id)->whereIn('estado', ['Pendiente', 'Incompleto', 'Observado'])->count() : 0;

        return [
            ['Sustentos por cargar', $sinCargar, 'Operaciones aprobadas sin documentación', 'bi-paperclip', route('sustentos.index'), $sinCargar ? 'danger' : 'ok'],
            ['Sustentos observados', $obsSust, 'Documentos que debe corregir', 'bi-exclamation-triangle', route('sustentos.index'), $obsSust ? 'warn' : 'ok'],
            ['Propuestas por corregir', $obsFud, 'Observadas por el aprobador', 'bi-pencil-square', route('fud.index', ['estado' => 'Observado']), $obsFud ? 'warn' : 'ok'],
            ['Expedientes de gobierno', $gob, 'Por completar o corregir (honramiento)', 'bi-bank', route('gobierno.index'), $gob ? 'warn' : 'ok'],
        ];
    }

    private function tareasAprobador(User $u): array
    {
        $bandeja = PropuestaFud::porAprobador($u->id)->enRevision()->count();
        $revisar = Sustento::where('estado', 'En revisión')
            ->when($u->nivel() === 1 && $u->tienda, fn($q) => $q->whereHas('propuesta.cliente', fn($c) => $c->where('zona_id', $u->tienda->zona_id)))
            ->count();
        $central = $u->nivel() >= 2;
        $gob = ExpedienteGobierno::visiblePara($u)->where('estado', $central ? 'Revisado por zona' : 'Completo')->count();
        $mes = PropuestaFud::where('aprobado_por', $u->id)->where('fecha_decision', '>=', now()->startOfMonth())->count();

        return [
            ['Solicitudes por decidir', $bandeja, 'En su bandeja de aprobación', 'bi-inbox', route('bandeja.index'), $bandeja ? 'danger' : 'ok'],
            ['Sustentos por revisar', $revisar, $u->nivel() === 1 ? 'Cargados en su zona' : 'Cargados en todas las zonas', 'bi-clipboard-check', route('revision.index', ['estado' => 'En revisión']), $revisar ? 'warn' : 'ok'],
            ['Expedientes de gobierno por revisar', $gob, $central ? 'Esperan la revisión central' : 'Completos en su zona', 'bi-bank',
             route('gobierno.index', ['estado' => $central ? 'Revisado por zona' : 'Completo']), $gob ? 'warn' : 'ok'],
            ['Decisiones del mes', $mes, 'Aprobaciones, observaciones y rechazos', 'bi-patch-check', route('aprobaciones.index'), 'info'],
        ];
    }

    private function indicadoresEjecutivo(User $u): array
    {
        $mios = PropuestaFud::porEnviador($u->id)->vigentes();
        $total = (clone $mios)->count();
        $apr = (clone $mios)->aprobados()->count();
        $conformes = Sustento::where('cargado_por', $u->id)->where('estado', 'Aprobado')->count();
        $sust = Sustento::where('cargado_por', $u->id)->count();
        $mes = PropuestaFud::porEnviador($u->id)->where('fecha_envio', '>=', now()->startOfMonth())->count();

        return [
            ['Propuestas del mes', $mes, 'enviadas desde el día 1'],
            ['Tasa de aprobación', $total ? round($apr / $total * 100).'%' : '—', "{$apr} de {$total} propuestas"],
            ['Sustentos conformes', $sust ? round($conformes / $sust * 100).'%' : '—', "{$conformes} de {$sust} expedientes"],
        ];
    }

    private function indicadoresCampana(): array
    {
        $total = PropuestaFud::vigentes()->count();
        $apr = PropuestaFud::vigentes()->aprobados()->count();
        $sust = Sustento::count();
        $conf = Sustento::where('estado', 'Aprobado')->count();
        $capital = PropuestaFud::vigentes()->aprobados()->whereHas('sustentos', fn($q) => $q->where('estado', 'Aprobado'))
            ->join('clientes_cartera', 'clientes_cartera.id', '=', 'propuestas_fud.cliente_cartera_id')->sum('clientes_cartera.saldo_capital');

        return [
            ['Tasa de aprobación', $total ? round($apr / $total * 100, 1).'%' : '—', "{$apr} de {$total} expedientes"],
            ['Sustentos conformes', $sust ? round($conf / $sust * 100, 1).'%' : '—', "{$conf} de {$sust} sustentos"],
            ['Capital normalizado', \App\Support\Estilo::solesCorto($capital), 'con sustento validado'],
        ];
    }

    // Posición del ejecutivo en su zona por propuestas aprobadas en el mes
    private function ranking(User $u): ?array
    {
        $zona = $u->tienda?->zona_id;
        if (!$zona) return null;

        $tabla = User::whereHas('rol', fn($q) => $q->where('grupo', 'fud'))
            ->whereHas('tienda', fn($q) => $q->where('zona_id', $zona))
            ->get()
            ->map(fn($e) => [
                'id' => $e->id, 'nombre' => $e->name,
                'aprobadas' => PropuestaFud::porEnviador($e->id)->aprobados()->where('fecha_decision', '>=', now()->startOfMonth())->count(),
            ])
            ->sortByDesc('aprobadas')->values();

        $pos = $tabla->search(fn($e) => $e['id'] === $u->id);
        return ['posicion' => $pos + 1, 'total' => $tabla->count(), 'zona' => $u->tienda->zona->nombre, 'tabla' => $tabla->take(5)];
    }

    private function fechasClave(?Campana $c): array
    {
        $cierre = $this->ultimoHabil(now()->endOfMonth());
        $reporte = $this->habil(now()->addMonthNoOverflow()->startOfMonth(), 2);
        $f = [
            ['fecha' => $cierre, 'titulo' => 'Cierre de mes', 'detalle' => 'Último día hábil para ejecutar conciliaciones y cerrar la mora del mes.'],
            ['fecha' => $this->habil(now()->addMonthNoOverflow()->startOfMonth(), 1), 'titulo' => 'Nueva base de aptos', 'detalle' => 'Recuperaciones remite la base mensual de clientes aptos.'],
            ['fecha' => $reporte, 'titulo' => 'Reporte mensual de conciliaciones', 'detalle' => 'Dentro de los 2 primeros días hábiles del mes.'],
        ];
        if ($c) $f[] = ['fecha' => $c->fecha_fin, 'titulo' => 'Fin de la campaña', 'detalle' => "Cierre de «{$c->nombre}»."];

        return collect($f)->filter(fn($x) => $x['fecha']->gte(now()->startOfDay()))->sortBy(fn($x) => $x['fecha']->timestamp)->values()
            ->map(fn($x) => $x + ['dias' => (int) now()->startOfDay()->diffInDays($x['fecha']->copy()->startOfDay())])->all();
    }

    private function ultimoHabil(Carbon $d): Carbon
    {
        $d = $d->copy()->startOfDay();
        while ($d->isWeekend()) $d->subDay();
        return $d;
    }

    private function habil(Carbon $desde, int $n): Carbon
    {
        $d = $desde->copy()->startOfDay();
        $c = 0;
        while (true) {
            if (!$d->isWeekend() && ++$c === $n) return $d;
            $d->addDay();
        }
    }

    private function equipo(User $u): array
    {
        $porRol = fn(string $nombre, ?int $zona = null) => User::with('rol', 'tienda')
            ->whereHas('rol', fn($q) => $q->where('nombre', $nombre))
            ->when($zona, fn($q) => $q->whereHas('tienda', fn($t) => $t->where('zona_id', $zona)))
            ->first();

        $zona = $u->tienda?->zona_id;
        return collect([
            $porRol('Coordinador Zonal de Recuperaciones', $zona),
            $porRol('Jefe de Recuperaciones'),
            $porRol('Gerente de División de Negocios'),
        ])->filter()->reject(fn($x) => $x->id === $u->id)->values()->all();
    }

    private function accesos(User $u): array
    {
        $disp = [];
        if ($u->esGrupoFud()) {
            $disp[] = ['Nueva propuesta FUD', 'Registrar una conciliación', 'bi-file-earmark-plus', route('fud.create')];
            $disp[] = ['Mis propuestas', 'Estado y correcciones', 'bi-folder2-open', route('fud.index')];
            $disp[] = ['Carga de sustentos', 'Con validación automática', 'bi-paperclip', route('sustentos.index')];
        } else {
            $disp[] = ['Bandeja de aprobación', 'Decidir solicitudes', 'bi-inbox', route('bandeja.index')];
            $disp[] = ['Revisión de sustentos', 'Validar documentación', 'bi-clipboard-check', route('revision.index')];
            $disp[] = ['Mis aprobaciones', 'Trazabilidad y PDF', 'bi-patch-check', route('aprobaciones.index')];
            $disp[] = ['Programas de gobierno', 'Expedientes y checklist', 'bi-bank', route('gobierno.index')];
            $disp[] = ['Reporte de campaña', 'Indicadores por zona', 'bi-bar-chart-line', route('reportes.dashboard')];
        }
        $disp[] = ['Lineamientos', 'Reglas y calculadora', 'bi-journal-text', route('lineamientos')];
        $disp[] = ['Seguimiento', 'Bitácora de operaciones', 'bi-clock-history', route('seguimiento.index')];
        if (config('rct.whatsapp.habilitado') && $u->esGrupoFud()) {
            $disp[] = ['Canal WhatsApp', 'Consultas y solicitudes desde el celular', 'bi-whatsapp', route('whatsapp.index')];
        }

        $futuros = [
            ['Simulador de descuento', 'Tope máximo, monto a pagar y aprobador al instante', 'bi-calculator', 'Próxima versión'],
            ['Devoluciones COFIDE', 'Cálculo de devoluciones tras el honramiento', 'bi-arrow-left-right', 'Fase 2'],
            ['Pagos posteriores', 'Pagos en operaciones honradas y alertas', 'bi-cash-coin', 'Fase 2'],
            ['Base de aptos', 'Importación mensual desde Excel', 'bi-file-earmark-spreadsheet', 'Próxima versión'],
            ['Reporte mensual', 'Conciliaciones del mes en Excel y PDF', 'bi-file-earmark-excel', 'Próxima versión'],
            ['Agenda de gestión', 'Visitas, llamadas y promesas de pago', 'bi-calendar-week', 'Fase 4'],
            ['Firma digital', 'Acuerdo de pago firmado en línea', 'bi-pen', 'Fase 5'],
            ['Asistente IA avanzado', 'Resúmenes y consultas sobre la cartera', 'bi-stars', 'Fase 5'],
            ['Metas y ranking', 'Avance por zona, tienda y asesor', 'bi-trophy', 'Fase 4'],
            ['Analítica predictiva', 'Priorización de cartera y recaída', 'bi-graph-up-arrow', 'Fase 6'],
        ];
        if (!config('rct.whatsapp.habilitado') || !$u->esGrupoFud()) {
            array_splice($futuros, 7, 0, [['Canal WhatsApp', 'Consultas, sustentos y resumen diario', 'bi-whatsapp', 'Fase 5']]);
        }

        return ['disponibles' => $disp, 'futuros' => $futuros];
    }

    private function comunicados(): array
    {
        return [
            ['Campaña', 'Campaña «Sin atrasos, que atrasan» en marcha',
             'Vigente del 01/09/2026 al 28/02/2027. Prioridad en cartera vencida, refinanciada, judicial y castigada para reducir provisiones.', '01/09/2026', 'bi-megaphone'],
            ['Novedad', 'Validación automática de sustentos',
             'Cada documento se analiza al cargarlo: firmas, formato, montos y duplicados. Los observados quedan registrados para la revisión de Recuperaciones.', '10/10/2026', 'bi-shield-check'],
            ['Novedad', 'Trazabilidad completa en PDF',
             'Los aprobadores pueden descargar la historia completa de cada operación: decisiones, documentos y revisiones con fecha y hora.', '12/10/2026', 'bi-file-earmark-pdf'],
            ['Procedimiento', 'Recuerde cargar los sustentos el mismo día',
             'El sistema enviará recordatorios cuando una conciliación aprobada pase más de 2 días sin documentación.', '12/10/2026', 'bi-alarm'],
        ];
    }

    private function hojaDeRuta(): array
    {
        return [
            ['Núcleo de campañas', 'Expediente, aprobación por autonomía, sustentos con validación, trazabilidad', 'Disponible'],
            ['Programas de gobierno', 'Honramiento, pagos posteriores, devoluciones a COFIDE y cuadre', 'En desarrollo'],
            ['Validación con IA', 'Lectura de montos, DNI y firmas en los documentos', 'Planificado'],
            ['Notificaciones y tableros', 'Alertas por correo, resumen diario por rol, metas', 'En desarrollo'],
            ['Asistente y WhatsApp', 'Consultas por voz y texto, carga de sustentos por WhatsApp', 'Piloto'],
            ['Analítica avanzada', 'Priorización de cartera, recaída y detección de anomalías', 'Planificado'],
        ];
    }
}
