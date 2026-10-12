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
