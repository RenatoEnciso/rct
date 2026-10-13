<?php
namespace App\Http\Controllers\Impulso;

use App\Http\Controllers\Controller;
use App\Models\{Lote, Seguimiento, Zona};
use App\Services\Impulso\BaseMatriz;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/** Etapa 4 — Base matriz IMPULSO MYPERÚ (vista consolidada y exportación). */
class BaseMatrizController extends Controller
{
    public function __construct(private BaseMatriz $matriz) {}

    public function index(Request $request)
    {
        abort_unless(CoreController::esCentral($request->user()), 403);
        $filas = $this->matriz->filas($request->only('lote_id', 'zona_id', 'documentacion', 'recupero', 'q'));
        $pagina = LengthAwarePaginator::resolveCurrentPage();
        return view('impulso.matriz', [
            'filas'   => new LengthAwarePaginator($filas->forPage($pagina, 25)->values(), $filas->count(), 25, $pagina,
                            ['path' => $request->url(), 'query' => $request->query()]),
            'totales' => $this->matriz->totales($filas),
            'lotes'   => Lote::whereIn('estado', ['Honrado', 'Honrado parcial'])->orderBy('codigo')->get(),
            'zonas'   => Zona::whereIn('id', \App\Models\ExpedienteGobierno::distinct()->pluck('zona_id'))->orderBy('nombre')->get(),
        ]);
    }

    public function exportar(Request $request)
    {
        abort_unless(CoreController::esCentral($request->user()), 403);
        $filas = $this->matriz->filas($request->only('lote_id', 'zona_id', 'documentacion', 'recupero', 'q'));
        $ruta = Storage::disk('private')->path('exportaciones/matriz_'.Str::random(8).'.xlsx');
        @mkdir(dirname($ruta), 0775, true);
        $this->matriz->exportar($filas, $ruta);
        Seguimiento::registrar($request->user()->id, 'Exportación base matriz', 'Completada', null, null, null,
            'Base matriz IMPULSO MYPERÚ exportada: '.$filas->count().' créditos.');
        return response()->download($ruta, 'Base_Matriz_IMPULSO_MYPERU_'.now()->format('Ymd_Hi').'.xlsx')->deleteFileAfterSend();
    }
}
