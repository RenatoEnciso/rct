<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckGrupo
{
    /**
     * Uso en rutas:
     *   ->middleware('grupo:fud')
     *   ->middleware('grupo:aprobador')
     *   ->middleware('grupo:fud,aprobador')   ← cualquiera de los dos
     *   ->middleware('nivel:2')               ← nivel mínimo
     */
    public function handle(Request $request, Closure $next, string ...$params): mixed
    {
        $user = $request->user();

        if (!$user) return redirect()->route('login');

        // Si el parámetro es numérico lo tratamos como nivel mínimo
        foreach ($params as $param) {
            if (is_numeric($param)) {
                if ($user->nivel() >= (int)$param) return $next($request);
            } else {
                if ($user->grupo() === $param) return $next($request);
            }
        }

        abort(403, 'No tienes permiso para acceder a esta sección.');
    }
}