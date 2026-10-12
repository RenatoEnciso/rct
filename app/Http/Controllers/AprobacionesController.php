<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PropuestaFud;
use App\Services\TrazabilidadExpediente;
use Barryvdh\DomPDF\Facade\Pdf;

class AprobacionesController extends Controller
{
    // Decisiones tomadas por el aprobador (aprobadas, observadas y rechazadas)
    public function index(Request $request)
    {
        $user = $request->user();
        $base = fn() => PropuestaFud::where('aprobado_por', $user->id)->whereNotNull('fecha_decision');

        $propuestas = $base()->with('cliente.tienda', 'enviador', 'sustentos', 'versiones')
            ->when($request->decision, function ($q, $d) {
                // Una versión reemplazada por corrección fue una observación
                if ($d === 'Observado') return $q->where(fn($w) => $w->where('estado_fud', 'Observado')->orWhereHas('versiones'));
                if ($d === 'Rechazado') return $q->where('estado_fud', 'Rechazado')->whereDoesntHave('versiones');
                return $q->where('estado_fud', $d);
            })
            ->when($request->q, fn($q, $v) => $q->whereHas('cliente', fn($c) =>
                $c->where('nombre_cliente', 'like', "%$v%")->orWhere('operacion', 'like', "%$v%")->orWhere('nro_documento', 'like', "%$v%")))
            ->when($request->desde, fn($q, $v) => $q->whereDate('fecha_decision', '>=', $v))
            ->when($request->hasta, fn($q, $v) => $q->whereDate('fecha_decision', '<=', $v))
            ->orderByDesc('decidido_en')->orderByDesc('id')
            ->paginate(15)->withQueryString();

        $kpis = [
            'aprobados'  => $base()->where('estado_fud', 'Aprobado')->count(),
            'observados' => $base()->where(fn($w) => $w->where('estado_fud', 'Observado')->orWhereHas('versiones'))->count(),
            'rechazados' => $base()->where('estado_fud', 'Rechazado')->whereDoesntHave('versiones')->count(),
            'mes'        => $base()->where('fecha_decision', '>=', now()->startOfMonth())->count(),
        ];

        return view('aprobaciones.index', compact('propuestas', 'kpis'));
    }

    public function show(Request $request, PropuestaFud $fud, TrazabilidadExpediente $traza)
    {
        $this->autorizar($request, $fud);
        $t = $traza->construir($fud);

        return view('aprobaciones.show', $t);
    }

    public function pdf(Request $request, PropuestaFud $fud, TrazabilidadExpediente $traza)
    {
        $this->autorizar($request, $fud);
        $t = $traza->construir($fud);
        $t['usuario'] = $request->user();

        $nombre = 'Trazabilidad_FUD-'.str_pad($t['vigente']->id, 5, '0', STR_PAD_LEFT).'_'.$t['cliente']->operacion.'.pdf';

        return Pdf::loadView('aprobaciones.pdf', $t)
            ->setPaper('a4')
            ->download($nombre);
    }

    // Aprobadores: cualquier operación. Ejecutivos: solo las que enviaron.
    private function autorizar(Request $request, PropuestaFud $fud): void
    {
        $user = $request->user();
        if ($user->esAprobador()) return;

        $raiz = $fud;
        while ($raiz->parent_fud_id && ($p = PropuestaFud::find($raiz->parent_fud_id))) $raiz = $p;
        abort_unless($raiz->enviado_por === $user->id, 403);
    }
}
