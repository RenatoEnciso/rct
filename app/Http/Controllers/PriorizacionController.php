<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use App\Models\{PriorizacionCartera, Tienda, Zona};

/** Modelo de priorización de cartera: "Mi prioridad de hoy" (asesor) y vista por zona (Jefatura / CZR). */
class PriorizacionController extends Controller
{
    private const SEGMENTOS = ['Alta', 'Media', 'Baja'];

    /** Top 20 de la cartera pendiente de la tienda del asesor. */
    public function mia(Request $request)
    {
        $u = $request->user();
        $segmento = in_array($request->segmento, self::SEGMENTOS) ? $request->segmento : null;

        $base = PriorizacionCartera::pendientes()->whereHas('cliente', fn($c) => $c->where('tienda_id', $u->tienda_id));
        $todos = (clone $base)->get(['segmento', 'monto_recuperable', 'probabilidad_pago']);

        $top = (clone $base)->with('cliente')
            ->when($segmento, fn($q) => $q->where('segmento', $segmento))
            ->orderByDesc('score')->orderByDesc('monto_recuperable')->limit(20)->get();

        return view('analitica.mi-prioridad', [
            'top'      => $top,
            'segmento' => $segmento,
            'tienda'   => $u->tienda,
            'resumen'  => [
                'pendientes' => $todos->count(),
                'alta'       => $todos->where('segmento', 'Alta')->count(),
                'media'      => $todos->where('segmento', 'Media')->count(),
                'baja'       => $todos->where('segmento', 'Baja')->count(),
                'esperado'   => $top->sum(fn($p) => $p->recupero_esperado),
                'provision'  => $top->sum(fn($p) => (float) $p->provision_liberable * (float) $p->probabilidad_pago / 100),
            ],
            'hoy'      => now(),
        ]);
    }

    /** Mapa de calor (zona o tienda × segmento), avance de la cartera prioritaria y casos de prioridad alta. */
    public function index(Request $request)
    {
        $u = $request->user();
        $zonaId = $u->nivel() === 1 ? $u->tienda?->zona_id : ($request->integer('zona_id') ?: null);

        $todas = PriorizacionCartera::with('cliente:id,zona_id,tienda_id')->deZona($zonaId)->get();
        $gestionados = PriorizacionCartera::gestionadas()->deZona($zonaId)->pluck('cliente_cartera_id')->flip();
        foreach ($todas as $p) $p->gestionado = isset($gestionados[$p->cliente_cartera_id]);
        $pendientes = $todas->where('gestionado', false);

        // Filas: zonas (vista nacional) o tiendas de la zona
        $porZona = !$zonaId;
        $nombres = $porZona ? Zona::pluck('nombre', 'id') : Tienda::where('zona_id', $zonaId)->pluck('nombre', 'id');
        $clave = fn($p) => $porZona ? $p->cliente->zona_id : $p->cliente->tienda_id;

        $filas = $todas->groupBy($clave)->map(function (Collection $g, $id) use ($nombres) {
            $pend = $g->where('gestionado', false);
            $prioritaria = $g->whereIn('segmento', ['Alta', 'Media']);
            $celdas = [];
            foreach (self::SEGMENTOS as $s) {
                $c = $pend->where('segmento', $s);
                $celdas[$s] = ['n' => $c->count(), 'esperado' => $c->sum(fn($p) => $p->recupero_esperado)];
            }
            return [
                'nombre'      => $nombres[$id] ?? '—',
                'celdas'      => $celdas,
                'pendientes'  => $pend->count(),
                'esperado'    => $pend->sum(fn($p) => $p->recupero_esperado),
                'gestionada'  => $prioritaria->count() ? round($prioritaria->where('gestionado', true)->count() / $prioritaria->count() * 100, 1) : 0,
            ];
        })->sortByDesc('esperado')->values();

        $prioritaria = $todas->whereIn('segmento', ['Alta', 'Media']);
        return view('analitica.priorizacion', [
            'filas'     => $filas,
            'maxCelda'  => $filas->flatMap(fn($f) => collect($f['celdas'])->pluck('esperado'))->max() ?: 1,
            'porZona'   => $porZona,
            'zonaId'    => $zonaId,
            'zonaFija'  => $u->nivel() === 1,
            'zonas'     => Zona::whereIn('id', PriorizacionCartera::join('clientes_cartera as c', 'c.id', '=', 'cliente_cartera_id')->distinct()->pluck('c.zona_id'))->orderBy('nombre')->get(),
            'resumen'   => [
                'pendientes'  => $pendientes->count(),
                'alta'        => $pendientes->where('segmento', 'Alta')->count(),
                'esperado'    => $pendientes->whereIn('segmento', ['Alta', 'Media'])->sum(fn($p) => $p->recupero_esperado),
                'provision'   => $pendientes->whereIn('segmento', ['Alta', 'Media'])->sum(fn($p) => (float) $p->provision_liberable),
                'gestionada'  => $prioritaria->count() ? round($prioritaria->where('gestionado', true)->count() / $prioritaria->count() * 100, 1) : 0,
                'gestionados' => $prioritaria->where('gestionado', true)->count(),
                'prioritaria' => $prioritaria->count(),
            ],
            'casos'     => PriorizacionCartera::with('cliente.tienda')->pendientes()->deZona($zonaId)->where('segmento', 'Alta')
                            ->orderByDesc('score')->limit(12)->get(),
        ]);
    }
}
