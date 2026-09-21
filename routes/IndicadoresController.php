<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\{PropuestaFud, Campana};

class IndicadoresController extends Controller
{
    public function index()
    {
        $total     = PropuestaFud::count();
        $revision  = PropuestaFud::enRevision()->count();
        $aprobadas = PropuestaFud::aprobados()->count();
        $tasa      = $total > 0 ? round($aprobadas / $total * 100) : 0;
        $campana   = Campana::activa();

        $porTipo = PropuestaFud::selectRaw('tipo_negociacion_desc, count(*) as total')
            ->groupBy('tipo_negociacion_desc')->get();

        $porEstadoCartera = PropuestaFud::with('cliente')
            ->selectRaw('count(*) as total')
            ->join('clientes_cartera','clientes_cartera.id','=','propuestas_fud.cliente_cartera_id')
            ->selectRaw('clientes_cartera.estado_cartera, count(*) as total')
            ->groupBy('clientes_cartera.estado_cartera')->get();

        return view('indicadores.index', compact(
            'total','revision','aprobadas','tasa','campana','porTipo','porEstadoCartera'
        ));
    }
}