<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use App\Models\ImportacionHonramiento;
use App\Services\Honramiento\{EscritorExcel, FormatoExcel, ImportadorExcel};

/** Importación del Excel de revisión de expedientes de honramiento (vista previa → confirmación). */
class GobiernoImportacionController extends Controller
{
    public function formulario(Request $request)
    {
        $this->autorizar($request);
        $importaciones = ImportacionHonramiento::with('usuario')->latest()->take(10)->get();
        return view('gobierno.importar', compact('importaciones'));
    }

    public function previsualizar(Request $request, ImportadorExcel $importador)
    {
        $this->autorizar($request);
        $request->validate(['archivo' => 'required|file|mimes:xlsx,xls,csv|max:20480'], [
            'archivo.required' => 'Seleccione el archivo Excel.',
            'archivo.mimes'    => 'El archivo debe ser Excel (.xlsx o .xls) o CSV.',
        ]);

        $token = (string) Str::uuid();
        $original = $request->file('archivo')->getClientOriginalName();
        $ruta = $request->file('archivo')->storeAs('importaciones', $token.'.'.$request->file('archivo')->getClientOriginalExtension(), 'private');

        try {
            $analisis = $importador->analizar(Storage::disk('private')->path($ruta));
        } catch (\Throwable $e) {
            report($e);
            return back()->with('error', 'No se pudo leer el archivo. Verifique que sea el Excel de revisión de expedientes.');
        }
        if ($analisis['error']) return back()->with('error', $analisis['error']);

        Storage::disk('private')->put("importaciones/{$token}.json", json_encode(['archivo' => $original, 'filas' => $analisis['filas']]));
        $filas = collect($analisis['filas']);

        return view('gobierno.importar-vista-previa', [
            'token' => $token, 'archivo' => $original, 'filas' => $filas,
            'resumen' => [
                'total'       => $filas->count(),
                'crear'       => $filas->where('accion', 'crear')->count(),
                'actualizar'  => $filas->where('accion', 'actualizar')->count(),
                'error'       => $filas->where('accion', 'error')->count(),
                'diferencias' => $filas->where('accion', '!=', 'error')->filter(fn($f) => $f['diferencias'])->count(),
            ],
        ]);
    }

    public function confirmar(Request $request, ImportadorExcel $importador)
    {
        $this->autorizar($request);
        $request->validate(['token' => 'required|uuid']);
        $json = "importaciones/{$request->token}.json";
        if (!Storage::disk('private')->exists($json)) {
            return redirect()->route('gobierno.importar')->with('error', 'La vista previa expiró. Vuelva a cargar el archivo.');
        }

        $datos = json_decode(Storage::disk('private')->get($json), true);
        $imp = $importador->importar($datos['filas'], $request->user(), $datos['archivo']);
        Storage::disk('private')->delete($json);

        return redirect()->route('gobierno.importar')->with('success',
            "Importación completada: {$imp->creados} expediente(s) creados, {$imp->actualizados} actualizados"
            .($imp->con_error ? ", {$imp->con_error} fila(s) omitidas por error" : '').'.');
    }

    // Plantilla con el formato exacto y filas de ejemplo
    public function plantilla(Request $request, EscritorExcel $escritor)
    {
        $this->autorizar($request);
        $filas = [
            ['1-TIENDAS', '24/09/2026', '24/09/2026', '63438', '41773160', '282261077330', '457647', '208137', 'LIMA 2', 'TIENDA MONTENEGRO',
             'CONDEZO ESCOBAR TEODORA', '32.605,88', '010 Lote', 'COMPLETO', 1, 1, 1, 1, 0, 1, 1, 1, 1, 0, 0, 'DJ Patrimonial | Evidencia'],
            ['1-TIENDAS', '24/09/2026', '24/09/2026', '194525', '44292089', '612271332381', '705303', '227986', 'NORTE 5', 'TIENDA SULLANA',
             'GARCIA CRUZ IRIS MARIA', '83.724,78', '010 Lote', 'COMPLETO', 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 0, ''],
        ];
        $ruta = Storage::disk('private')->path('importaciones/plantilla_'.Str::random(8).'.xlsx');
        @mkdir(dirname($ruta), 0775, true);
        $escritor->guardar($filas, $ruta, 'Revisión expedientes', [
            'Plantilla de revisión de expedientes de honramiento — RCT',
            'Una fila por crédito. El ID identifica el expediente: si ya existe se actualiza, si no se crea.',
            'Documentos: 1 = presentado, 0 = falta. La primera columna corresponde al DNI (persona natural) o al Reporte tributario con Ficha RUC (persona jurídica, RUC 20).',
            'ESTADO y N° DOCUMENTOS FALTANTES se calculan en RCT con los documentos que exige COFIDE (6 persona natural, 7 jurídica); si el Excel difiere, se informa en la vista previa.',
            'Estados: COMPLETO, INCOMPLETO, INCOMPLETO DEFINITIVO (la tienda no tiene el documento tras la búsqueda) y PENDIENTE (sin documentos).',
            'Monto honrado en formato peruano (32.605,88) o numérico. DNI, crédito, cuenta y operación se conservan como texto.',
        ]);
        return response()->download($ruta, 'Plantilla_revision_expedientes_honramiento.xlsx')->deleteFileAfterSend();
    }

    // Importa la Jefatura / Gerencia (revisión central)
    private function autorizar(Request $request): void
    {
        abort_unless($request->user()->esAprobador() && $request->user()->nivel() >= 2, 403,
            'La importación la realiza la revisión central (Jefatura de Recuperaciones).');
    }
}
