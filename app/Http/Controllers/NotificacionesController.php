<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\Notificaciones;

class NotificacionesController extends Controller
{
    public function index(Request $request, Notificaciones $servicio)
    {
        $todas = $servicio->paraUsuario($request->user());
        $notificaciones = $todas
            ->when($request->categoria, fn($c, $v) => $c->where('categoria', $v))
            ->when($request->estado === 'no-leidas', fn($c) => $c->where('leida', false))
            ->values();

        $conteo = $todas->groupBy('categoria')->map->count();

        return view('notificaciones.index', compact('notificaciones', 'conteo', 'todas'));
    }

    /** Avisos nuevos desde un momento dado (consulta periódica de la página, sin recargar). */
    public function recientes(Request $request, Notificaciones $servicio)
    {
        $u = $request->user();
        try { $desde = \Illuminate\Support\Carbon::parse((string) $request->input('desde')); } catch (\Throwable) { $desde = now()->subMinute(); }
        $lista = $u->notifications()->where('created_at', '>', $desde)->oldest()->limit(5)->get();
        $nuevas = $lista->map(fn($n) => [
                'clave' => 'n-'.$n->id, 'titulo' => $n->data['titulo'] ?? 'Aviso', 'mensaje' => $n->data['mensaje'] ?? '',
                'url' => $n->data['url'] ?? route('notificaciones.index'), 'icono' => $n->data['icono'] ?? 'bi-bell',
                'color' => $n->data['color'] ?? 'info', 'toast' => (bool) ($n->data['toast'] ?? false),
                'categoria' => $n->data['categoria'] ?? 'gobierno', 'fecha' => $n->created_at->format('d/m/Y H:i'),
            ]);
        return response()->json([
            'ahora' => ($lista->last()?->created_at ?? $desde)->toIso8601String(),
            'nuevas' => $nuevas->values(),
            'no_leidas' => $nuevas->isNotEmpty() ? $servicio->noLeidas($u) : null,
        ]);
    }

    // Marca como leída y devuelve el destino (tomado de la propia notificación, nunca del request)
    public function leer(Request $request, Notificaciones $servicio)
    {
        $request->validate(['clave' => 'required|string|max:120']);
        $n = $servicio->paraUsuario($request->user())->firstWhere('clave', $request->clave);
        if ($n) $servicio->marcarLeida($request->user(), $n['clave']);

        $destino = $n['url'] ?? route('notificaciones.index');
        return $request->expectsJson() ? response()->json(['url' => $destino]) : redirect($destino);
    }

    public function leerTodas(Request $request, Notificaciones $servicio)
    {
        $servicio->marcarTodas($request->user());
        return $request->expectsJson() ? response()->json(['ok' => true]) : back()->with('success', 'Notificaciones marcadas como leídas.');
    }
}
