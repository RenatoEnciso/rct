<?php
namespace App\Http\Controllers\Impulso;

use App\Http\Controllers\Controller;
use App\Models\DevolucionCofide;
use App\Services\Impulso\{Calculos, Devoluciones};
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/** Etapa 6 — Devolución mensual a COFIDE y cruce con la notificación TXT. */
class DevolucionesController extends Controller
{
    public function __construct(private Devoluciones $devoluciones) {}

    public function index(Request $request)
    {
        $this->autorizar($request);
        $enCurso = $this->devoluciones->pendientesDelMes(now());
        $anterior = now()->subMonthNoOverflow()->startOfMonth();
        return view('impulso.devoluciones', [
            'devoluciones' => DevolucionCofide::orderByDesc('periodo')->get(),
            'enCurso'      => ['periodo' => now()->startOfMonth(), 'creditos' => $enCurso->count(), 'pagos' => $enCurso->flatten()->count(),
                               'monto' => Calculos::sumar($enCurso->flatten()->pluck('reversion'))],
            'pendienteAnterior' => DevolucionCofide::whereDate('periodo', $anterior)->doesntExist() && $this->devoluciones->pendientesDelMes($anterior)->isNotEmpty()
                ? $anterior : null,
        ]);
    }

    public function show(Request $request, DevolucionCofide $devolucion)
    {
        $this->autorizar($request);
        $orden = ['diferencia' => 0, 'solo_rct' => 1, 'solo_txt' => 2, 'pendiente' => 3, 'ok' => 4];
        return view('impulso.devolucion', [
            'dev'      => $devolucion,
            'detalles' => $devolucion->detalles()->with('expediente.tienda')->get()
                            ->sortBy(fn($d) => [$orden[$d->resultado] ?? 9, $d->cliente])->values(),
            'animar'   => session('recien_cruzado') || $request->boolean('animar'),
        ]);
    }

    public function generar(Request $request)
    {
        $this->autorizar($request);
        $periodo = Carbon::parse($request->validate(['periodo' => 'required|date_format:Y-m'])['periodo'].'-01');
        try {
            $dev = $this->devoluciones->generar($periodo, $request->user());
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
        return redirect()->route('impulso.devoluciones.show', $dev)->with('success', 'Devolución de '.$dev->nombrePeriodo().' calculada.');
    }

    public function cruzar(Request $request, DevolucionCofide $devolucion)
    {
        $this->autorizar($request);
        $request->validate(['txt' => 'required|file|max:5120'], ['txt.required' => 'Seleccione la notificación TXT de COFIDE.']);
        $archivo = $request->file('txt');
        if (!in_array(strtolower($archivo->getClientOriginalExtension()), ['txt', 'csv'])) {
            return back()->with('error', 'La notificación de COFIDE debe ser un archivo .txt.');
        }
        try {
            $r = $this->devoluciones->cruzar($devolucion, file_get_contents($archivo->getRealPath()), $archivo->getClientOriginalName(), $request->user());
        } catch (\InvalidArgumentException | \DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
        return redirect()->route('impulso.devoluciones.show', $devolucion)->with('recien_cruzado', true)
            ->with($r['cuadra'] ? 'success' : 'warning', $r['cuadra']
                ? 'Cruce completo: lo calculado por RCT cuadra al 100 % con COFIDE.'
                : "Cruce con {$r['n_diferencias']} diferencias. Resuélvalas antes de devolver.");
    }

    public function cerrar(Request $request, DevolucionCofide $devolucion)
    {
        $this->autorizar($request);
        try {
            $this->devoluciones->cerrar($devolucion, $request->user(), $request->input('justificacion'));
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }
        return back()->with('success', 'Devolución de '.$devolucion->nombrePeriodo().' cerrada: los pagos quedan como devueltos.');
    }

    /** TXT de ejemplo de COFIDE (demo): cuadra con lo calculado. */
    public function txtEjemplo(Request $request, DevolucionCofide $devolucion)
    {
        $this->autorizar($request);
        return response($this->devoluciones->txtEjemplo($devolucion), 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="NOTIFICACION_COFIDE_'.$devolucion->periodo->format('Ym').'.txt"',
        ]);
    }

    /** TXT importado (el último cruzado). */
    public function txt(Request $request, DevolucionCofide $devolucion)
    {
        $this->autorizar($request);
        abort_unless($devolucion->txt_ruta && Storage::disk('private')->exists($devolucion->txt_ruta), 404);
        return Storage::disk('private')->download($devolucion->txt_ruta, $devolucion->txt_archivo);
    }

    private function autorizar(Request $request): void
    {
        abort_unless(CoreController::esCentral($request->user()), 403);
    }
}
