<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PropuestaFud;
use App\Models\Sustento;
use App\Models\Campana;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user    = $request->user()->load('rol');
        $campana = Campana::activa();

        if ($user->esGrupoFud()) {
            $propuestas = PropuestaFud::with('cliente', 'aprobador')
                ->porEnviador($user->id)
                ->vigentes()
                ->latest('fecha_envio')->latest('id')
                ->take(6)
                ->get();

            $mios = fn() => PropuestaFud::porEnviador($user->id)->vigentes();
            $kpis = [
                'total'      => $mios()->count(),
                'revision'   => $mios()->enRevision()->count(),
                'aprobadas'  => $mios()->aprobados()->count(),
                'observadas' => $mios()->whereIn('estado_fud', ['Observado', 'Rechazado'])->count(),
                'sustentos'  => Sustento::where('cargado_por', $user->id)
                                    ->whereIn('estado', ['Pendiente', 'Observado'])->count(),
            ];

            return view('dashboard.index', compact('user', 'campana', 'propuestas', 'kpis'));
        }

        // Aprobador: indicadores de su propia gestión
        $pendientes = PropuestaFud::with('cliente', 'enviador')
            ->porAprobador($user->id)
            ->enRevision()
            ->latest('fecha_envio')->latest('id')
            ->get();

        $kpis = [
            'pendientes' => $pendientes->count(),
            'aprobadas'  => PropuestaFud::where('aprobado_por', $user->id)->aprobados()->count(),
            'observadas' => PropuestaFud::where('aprobado_por', $user->id)->where('estado_fud', 'Observado')->count(),
            'mes'        => PropuestaFud::where('aprobado_por', $user->id)
                                ->where('fecha_decision', '>=', now()->startOfMonth())->count(),
            'nivel'      => $user->nivel(),
        ];

        return view('dashboard.index', compact('user', 'campana', 'pendientes', 'kpis'));
    }
}
