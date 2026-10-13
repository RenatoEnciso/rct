<?php
namespace App\Http\Controllers\Impulso;

use App\Http\Controllers\Controller;
use App\Models\Lote;
use App\Services\Impulso\{Avisos, ReporteHonramiento};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/** Etapa 2 — Importación del reporte de honramiento de COFIDE y aviso a las agencias por zona. */
class HonramientoController extends Controller
{
    public function __construct(private ReporteHonramiento $reporte) {}

    public function previsualizar(Request $request, Lote $lote)
    {
        $this->autorizar($request);
        $request->validate(['archivo' => 'required|file|mimes:xlsx,xls|max:10240'],
            ['archivo.required' => 'Seleccione el reporte de honramiento descargado de COFIDE (Excel).', 'archivo.mimes' => 'El reporte debe ser un archivo Excel (.xlsx).']);
        $original = $request->file('archivo')->getClientOriginalName();
        $ruta = $request->file('archivo')->store('impulso/reportes', 'private');
        $analisis = $this->reporte->analizar($lote, Storage::disk('private')->path($ruta));
        if ($analisis['error']) return back()->with('error', $analisis['error']);

        $token = Str::random(24);
        Storage::disk('private')->put("impulso/reportes/{$token}.json", json_encode(['archivo' => $original] + $analisis));
        return view('impulso.honramiento-vista-previa', ['lote' => $lote, 'a' => $analisis, 'token' => $token, 'archivo' => $original]);
    }

    public function confirmar(Request $request, Lote $lote)
    {
        $this->autorizar($request);
        $token = preg_replace('/[^A-Za-z0-9]/', '', (string) $request->input('token'));
        $json = Storage::disk('private')->get("impulso/reportes/{$token}.json");
        abort_unless($json, 410, 'La vista previa expiró. Vuelva a cargar el reporte.');
        $datos = json_decode($json, true);
        try {
            $r = $this->reporte->importar($lote, $datos, $request->user(), $datos['archivo']);
        } catch (\DomainException $e) {
            return redirect()->route('impulso.lotes.show', $lote)->with('error', $e->getMessage());
        }
        Storage::disk('private')->delete("impulso/reportes/{$token}.json");
        return redirect()->route('impulso.lotes.show', $lote)
            ->with('success', "Reporte de COFIDE importado: {$r['honrados']} de {$r['total']} créditos honrados. Se avisó a las agencias de cada zona.");
    }

    /** Descarga un reporte con el formato de COFIDE generado con los datos del lote (para la demo). */
    public function ejemplo(Request $request, Lote $lote)
    {
        $this->autorizar($request);
        // En la demo, COFIDE observa 1 de cada 15 créditos
        $observados = $lote->expedientes()->orderBy('id')->pluck('credito_sicmact')->filter(fn($c, $i) => $i % 15 === 7)->values()->all();
        $ruta = $this->reporte->ejemplo($lote, now(), $observados);
        return response()->download($ruta, basename($ruta));
    }

    public function avisar(Request $request, Lote $lote, Avisos $avisos)
    {
        $this->autorizar($request);
        abort_unless($lote->esHonrado(), 422);
        $avisos->loteHonrado($lote);
        return back()->with('success', 'Se reenvió el aviso a las agencias de cada zona (campana y correo).');
    }

    private function autorizar(Request $request): void
    {
        abort_unless(CoreController::esCentral($request->user()), 403);
    }
}
