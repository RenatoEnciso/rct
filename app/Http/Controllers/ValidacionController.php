<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\{ClienteCartera, ExpedienteGobierno, Sustento};
use App\Services\Validacion\CargaDocumentos;

/**
 * Validación automática previa a la carga (no guarda el archivo).
 * La validación definitiva se repite en el servidor al registrar.
 */
class ValidacionController extends Controller
{
    public function documento(Request $request, CargaDocumentos $carga)
    {
        $request->validate([
            'archivo'       => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'tipo'          => 'required|string|max:120',
            'contexto'      => 'required|in:sustento,gobierno',
            'sustento_id'   => 'nullable|integer',
            'expediente_id' => 'nullable|integer',
            'cliente_ref'   => 'nullable|string|max:30',
        ], [
            'archivo.mimes' => 'Formato no admitido. Cargue el documento en PDF, JPG o PNG.',
            'archivo.max'   => 'El archivo supera el tamaño máximo de 10 MB.',
        ]);

        if ($request->contexto === 'sustento') {
            $sustento = Sustento::with('propuesta.cliente', 'propuesta.detalleCancelacion')
                ->findOrFail($request->sustento_id);
            $contexto = $carga->contextoSustento($sustento);
        } else {
            $expediente = $request->expediente_id ? ExpedienteGobierno::findOrFail($request->expediente_id) : null;
            $cliente    = $expediente?->cliente ?? $this->clientePorReferencia($request->cliente_ref);
            $contexto   = $cliente
                ? $carga->contextoGobierno($cliente, $expediente)
                : ['monto_aprobado' => 0, 'hashes_existentes' => [], 'programa' => true];
        }

        return response()->json($carga->previsualizar($request->file('archivo'), $request->tipo, $contexto));
    }

    private function clientePorReferencia(?string $ref): ?ClienteCartera
    {
        if (!$ref) return null;
        return ClienteCartera::where('cuenta', trim($ref))->orWhere('operacion', trim($ref))->first();
    }
}
