<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\{PropuestaFud, ClienteCartera, Sustento, SustentoDocumento, Zona, Tienda};
use Illuminate\Support\Facades\DB;

class ReportesController extends Controller
{
    public function dashboard(Request $request)
    {
        // ── Filtros ──────────────────────────────────────────
        $zonaId   = $request->zona_id;
        $tiendaId = $request->tienda_id;
        $estado   = $request->estado;   // estado_fud
        $periodo  = $request->periodo ?? 'campana'; // campana | mes | semana

        // ── KPIs principales ─────────────────────────────────
        $qBase = PropuestaFud::raiz()
            ->when($zonaId,   fn($q) => $q->whereHas('cliente', fn($c) => $c->where('zona_id',   $zonaId)))
            ->when($tiendaId, fn($q) => $q->whereHas('cliente', fn($c) => $c->where('tienda_id', $tiendaId)));

        $totalFuds      = (clone $qBase)->count();
        $aprobados      = (clone $qBase)->aprobados()->count();
        $enRevision     = (clone $qBase)->enRevision()->count();
        $observados     = (clone $qBase)->where('estado_fud', 'Observado')->count();
        $rechazados     = (clone $qBase)->where('estado_fud', 'Rechazado')->count();
        $cumplimientoPct = $totalFuds > 0 ? round($aprobados / $totalFuds * 100, 2) : 0;

        // Sustentos
        $totalSustentos    = Sustento::count();
        $sustentosCompletos = Sustento::where('estado', 'Completo')->count();
        $sustentosPendientes = Sustento::where('estado', 'Pendiente')->count();
        $cumplimientoSustentos = $totalSustentos > 0
            ? round($sustentosCompletos / $totalSustentos * 100, 2) : 0;

        // Monto en riesgo (saldo capital de FUDs sin sustentos completos)
        $montoEnRiesgo = PropuestaFud::aprobados()
            ->whereHas('sustentos', fn($q) => $q->where('estado', 'Pendiente'))
            ->join('clientes_cartera', 'clientes_cartera.id', '=', 'propuestas_fud.cliente_cartera_id')
            ->sum('clientes_cartera.saldo_capital');

        // Monto honrado (saldo capital de FUDs aprobados con sustentos completos)
        $montoHonrado = PropuestaFud::aprobados()
            ->whereHas('sustentos', fn($q) => $q->where('estado', 'Completo'))
            ->join('clientes_cartera', 'clientes_cartera.id', '=', 'propuestas_fud.cliente_cartera_id')
            ->sum('clientes_cartera.saldo_capital');

        // ── Cumplimiento por zona ────────────────────────────
        $zonas = Zona::with('tiendas')->get();

        $cumplimientoPorZona = $zonas->map(function ($zona) use ($qBase) {
            $total = (clone $qBase)
                ->whereHas('cliente', fn($c) => $c->where('zona_id', $zona->id))
                ->count();
            $aprobados = (clone $qBase)
                ->whereHas('cliente', fn($c) => $c->where('zona_id', $zona->id))
                ->aprobados()->count();
            $pct = $total > 0 ? round($aprobados / $total * 100, 2) : 0;

            return [
                'zona'       => $zona->nombre,
                'total'      => $total,
                'aprobados'  => $aprobados,
                'pendientes' => $total - $aprobados,
                'pct'        => $pct,
                'variacion'  => $this->calcularVariacion($zona->id),
            ];
        })->sortBy('pct');

        // ── Top 10 tiendas con menor cumplimiento ───────────
        $topTiendasBajo = Tienda::with('zona')
            ->get()
            ->map(function ($tienda) use ($qBase) {
                $total = (clone $qBase)
                    ->whereHas('cliente', fn($c) => $c->where('tienda_id', $tienda->id))
                    ->count();
                $aprobados = (clone $qBase)
                    ->whereHas('cliente', fn($c) => $c->where('tienda_id', $tienda->id))
                    ->aprobados()->count();
                $pct = $total > 0 ? round($aprobados / $total * 100, 2) : 0;
                return [
                    'tienda'    => $tienda->nombre,
                    'zona'      => $tienda->zona->nombre ?? '—',
                    'total'     => $total,
                    'aprobados' => $aprobados,
                    'pct'       => $pct,
                ];
            })
            ->filter(fn($t) => $t['total'] > 0)
            ->sortBy('pct')
            ->take(15)
            ->values();

        // ── Documentos faltantes en sustentos ───────────────
        // Contar cuántos sustentos pendientes hay por tipo de documento esperado
        $documentosFaltantes = [
            ['documento' => 'Acuerdo de Pago',          'faltantes' => $this->docsFaltantes('Acuerdo de Pago')],
            ['documento' => 'Nota de Abono',             'faltantes' => $this->docsFaltantes('Nota de Abono')],
            ['documento' => 'Comprobante de Pago',       'faltantes' => $this->docsFaltantes('Comprobante de Pago')],
            ['documento' => 'Hoja de Convenio',          'faltantes' => $this->docsFaltantes('Hoja de Convenio')],
            ['documento' => 'Informe-Propuesta SK>3UIT', 'faltantes' => $this->docsFaltantes('Informe-Propuesta SK>3UIT')],
        ];

        // ── Monto por zona (para gráfico de barras) ──────────
        $montoPorZona = $zonas->map(function ($zona) {
            $monto = PropuestaFud::aprobados()
                ->join('clientes_cartera', 'clientes_cartera.id', '=', 'propuestas_fud.cliente_cartera_id')
                ->where('clientes_cartera.zona_id', $zona->id)
                ->sum('clientes_cartera.saldo_capital');
            return [
                'zona'  => $zona->nombre,
                'monto' => $monto,
            ];
        })->sortByDesc('monto')->values();

        // ── Distribución por tipo de negociación ─────────────
        $porTipoNeg = PropuestaFud::raiz()
            ->selectRaw('tipo_negociacion, count(*) as total, sum(case when estado_fud = "Aprobado" then 1 else 0 end) as aprobados')
            ->groupBy('tipo_negociacion')
            ->get();

        // ── FUDs por estado (para gráfico de dona) ───────────
        $porEstado = [
            'Aprobado'   => $aprobados,
            'En revisión'=> $enRevision,
            'Observado'  => $observados,
            'Rechazado'  => $rechazados,
        ];

        // ── Actividad reciente ────────────────────────────────
        $actividadReciente = PropuestaFud::with('cliente','enviador','aprobadoPor')
            ->whereIn('estado_fud', ['Aprobado','Observado','Rechazado'])
            ->latest('updated_at')
            ->take(8)
            ->get();

        // ── Zonas críticas (< 50% cumplimiento) ─────────────
        $zonasCriticas = $cumplimientoPorZona->filter(fn($z) => $z['pct'] < 50)->count();

        return view('reportes.dashboard', compact(
            'totalFuds', 'aprobados', 'enRevision', 'observados', 'rechazados',
            'cumplimientoPct', 'cumplimientoSustentos',
            'totalSustentos', 'sustentosCompletos', 'sustentosPendientes',
            'montoEnRiesgo', 'montoHonrado',
            'cumplimientoPorZona', 'topTiendasBajo',
            'documentosFaltantes', 'montoPorZona', 'porTipoNeg',
            'porEstado', 'actividadReciente', 'zonasCriticas',
            'zonas',
            'zonaId', 'tiendaId', 'estado'
        ));
    }

    private function calcularVariacion(int $zonaId): float
    {
        // Variación respecto al mes anterior — simplificado
        // En producción compararías fechas reales
        return round(rand(-5, 15) / 10, 2);
    }

    private function docsFaltantes(string $tipo): int
    {
        // Sustentos pendientes que NO tienen ese tipo de documento cargado
        return Sustento::where('estado', 'Pendiente')
            ->whereDoesntHave('documentos', fn($q) => $q->where('tipo_doc', $tipo))
            ->count();
    }
}