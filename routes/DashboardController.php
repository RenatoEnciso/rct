<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PropuestaFud;
use App\Models\Campana;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user    = $request->user()->load('rol');
        $campana = Campana::activa();

        if ($user->esGrupoFud()) {
            $propuestas = PropuestaFud::with('cliente')
                ->porEnviador($user->id)
                ->latest()
                ->take(6)
                ->get();

            $kpis = [
                'total'      => PropuestaFud::porEnviador($user->id)->count(),
                'revision'   => PropuestaFud::porEnviador($user->id)->enRevision()->count(),
                'aprobadas'  => PropuestaFud::porEnviador($user->id)->aprobados()->count(),
                'observadas' => PropuestaFud::porEnviador($user->id)
                                    ->whereIn('estado_fud', ['Observado','Rechazado'])->count(),
            ];

            return view('dashboard.index', compact('user', 'campana', 'propuestas', 'kpis'));
        }

        // Aprobador
        $pendientes = PropuestaFud::with('cliente','enviador')
            ->porAprobador($user->id)
            ->enRevision()
            ->latest()
            ->get();

        $kpis = [
            'pendientes' => $pendientes->count(),
            'aprobadas'  => PropuestaFud::aprobados()->count(),
            'revision'   => PropuestaFud::enRevision()->count(),
            'nivel'      => $user->nivel(),
        ];

        return view('dashboard.index', compact('user', 'campana', 'pendientes', 'kpis'));
    }
}