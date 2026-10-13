<?php
namespace App\Http\Controllers\Impulso;

use App\Http\Controllers\Controller;
use App\Services\Impulso\SincronizadorCore;
use Illuminate\Http\Request;

/** Sincronización manual con el core (además de la programada cada 30 minutos). */
class CoreController extends Controller
{
    public function sincronizar(Request $request, SincronizadorCore $sincronizador)
    {
        abort_unless(self::esCentral($request->user()), 403);
        $r = $sincronizador->ejecutar('manual', $request->user()->id);
        return back()->with('success', "Sincronización con el core completada: {$r->pagos_nuevos} pagos nuevos leídos, {$r->pagos_programa} del programa.");
    }

    /** Revisión central del programa: Jefatura y Gerencia. */
    public static function esCentral($u): bool
    {
        return $u && $u->esAprobador() && $u->nivel() >= 2;
    }
}
