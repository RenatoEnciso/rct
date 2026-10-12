<?php
namespace App\Http\Controllers;

use App\Models\{PropuestaFud, Campana, ExpedienteGobierno};

class IndicadoresController extends Controller
{
    private const ETIQUETAS_NEG = [
        'cuotas'        => 'Pago de Cuotas',
        'cancelacion'   => 'Cancelación Total',
        'extrajudicial' => 'Cartera Extrajudicial / Judicial',
        'castigada'     => 'Cartera Castigada',
        'focmac'        => 'Cartera FOCMAC',
    ];

    public function index()
    {
        // Indicadores sobre expedientes vigentes (última versión de cada FUD)
        $total     = PropuestaFud::vigentes()->count();
        $revision  = PropuestaFud::vigentes()->enRevision()->count();
        $aprobadas = PropuestaFud::vigentes()->aprobados()->count();
        $tasa      = $total > 0 ? round($aprobadas / $total * 100) : 0;
        $campana   = Campana::activa();

        $porTipo = PropuestaFud::vigentes()
            ->selectRaw('tipo_negociacion, count(*) as total')
            ->groupBy('tipo_negociacion')->orderByDesc('total')->get()
            ->map(fn($r) => (object) [
                'etiqueta' => self::ETIQUETAS_NEG[$r->tipo_negociacion] ?? $r->tipo_negociacion,
                'total'    => $r->total,
            ]);

        $porEstadoCartera = PropuestaFud::vigentes()
            ->join('clientes_cartera', 'clientes_cartera.id', '=', 'propuestas_fud.cliente_cartera_id')
            ->selectRaw("clientes_cartera.estado_cartera, count(*) as total, sum(case when estado_fud = 'Aprobado' then 1 else 0 end) as aprobados")
            ->groupBy('clientes_cartera.estado_cartera')->orderByDesc('total')->get();

        // Evolución mensual: FUD enviados y aprobados en los últimos 6 meses
        $evolucion = PropuestaFud::vigentes()
            ->where('fecha_envio', '>=', now()->subMonths(5)->startOfMonth())
            ->selectRaw("DATE_FORMAT(fecha_envio, '%Y-%m') as mes, count(*) as enviados, sum(case when estado_fud = 'Aprobado' then 1 else 0 end) as aprobados")
            ->groupBy('mes')->orderBy('mes')->get();

        $gobTotal     = ExpedienteGobierno::count();
        $gobCompletos = ExpedienteGobierno::where('estado', 'Completo')->count();

        return view('indicadores.index', compact(
            'total', 'revision', 'aprobadas', 'tasa', 'campana',
            'porTipo', 'porEstadoCartera', 'evolucion', 'gobTotal', 'gobCompletos'
        ));
    }
}
