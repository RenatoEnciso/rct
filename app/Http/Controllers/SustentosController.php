<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\{Sustento, SustentoDocumento, Seguimiento};
use App\Services\Validacion\CargaDocumentos;

class SustentosController extends Controller
{
    public function index(Request $request)
    {
        // Pendientes de carga, observados (para corregir) y en revisión (solo informativo)
        $sustentos = Sustento::with('propuesta.cliente', 'propuesta.aprobadoPor', 'propuesta.detalleCancelacion', 'documentos')
            ->where('cargado_por', $request->user()->id)
            ->whereIn('estado', ['Pendiente', 'Observado', 'En revisión'])
            ->orderByRaw("FIELD(estado, 'Observado', 'Pendiente', 'En revisión')")
            ->latest()
            ->get();

        return view('sustentos.index', compact('sustentos'));
    }

    public function cargar(Request $request, Sustento $sustento, CargaDocumentos $carga)
    {
        abort_unless($sustento->cargado_por === $request->user()->id, 403);
        if ($sustento->estado !== 'Pendiente') {
            return redirect()->route('sustentos.index')->with('error', 'Los sustentos de esta operación ya fueron cargados.');
        }

        $this->validarEnvio($request);
        $resumen = $this->registrarDocumentos($request, $sustento, $carga);

        $sustento->update(['estado' => 'En revisión', 'fecha_carga' => now()->toDateString()]);

        Seguimiento::registrar(
            $request->user()->id, 'Carga de sustentos', 'En revisión',
            $sustento->propuesta_fud_id,
            $sustento->propuesta->cliente->nombre_cliente,
            $sustento->propuesta->cliente->operacion,
            $resumen
        );

        return redirect()->route('sustentos.index')
            ->with('success', 'Sustentos enviados a revisión. '.$resumen);
    }

    public function recargar(Request $request, Sustento $sustento, CargaDocumentos $carga)
    {
        abort_unless($sustento->cargado_por === $request->user()->id, 403);
        if (!$sustento->estaObservado()) {
            return redirect()->route('sustentos.index')->with('error', 'Solo se pueden corregir sustentos observados.');
        }

        $this->validarEnvio($request);

        // Los documentos corregidos reemplazan a los anteriores del mismo tipo, que quedan en el historial
        $tipos = collect($request->input('documentos', []))->pluck('tipo')->all();
        $sustento->documentos()->whereIn('tipo_doc', $tipos)->update(['vigente' => false]);

        $resumen = $this->registrarDocumentos($request, $sustento, $carga);

        $sustento->update([
            'estado'              => 'En revisión',
            'fecha_carga'         => now()->toDateString(),
            'comentario_revision' => null,
        ]);

        Seguimiento::registrar(
            $request->user()->id, 'Recarga de sustentos', 'En revisión (corregido)',
            $sustento->propuesta_fud_id,
            $sustento->propuesta->cliente->nombre_cliente,
            $sustento->propuesta->cliente->operacion,
            $resumen
        );

        return redirect()->route('sustentos.index')
            ->with('success', 'Documentos corregidos enviados a revisión. '.$resumen);
    }

    private function validarEnvio(Request $request): void
    {
        $request->validate([
            'documentos'           => 'required|array|min:1',
            'documentos.*.tipo'    => 'required|in:Acuerdo de Pago,Nota de Abono,Comprobante de Pago,Hoja de Convenio,Informe-Propuesta SK>3UIT,Otro',
            'documentos.*.archivo' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ], [
            'documentos.required'           => 'Cargue al menos un documento.',
            'documentos.*.archivo.required' => 'Falta cargar uno de los documentos obligatorios.',
            'documentos.*.archivo.mimes'    => 'Formato no admitido. Use PDF, JPG o PNG.',
            'documentos.*.archivo.max'      => 'Cada archivo puede pesar como máximo 10 MB.',
        ]);
    }

    /** Valida en el servidor y guarda cada documento con su resultado. Devuelve un resumen. */
    private function registrarDocumentos(Request $request, Sustento $sustento, CargaDocumentos $carga): string
    {
        $sustento->loadMissing('propuesta.cliente', 'propuesta.detalleCancelacion');
        $contexto = $carga->contextoSustento($sustento);
        $lote = [];
        $conObservaciones = 0;

        foreach ($request->file('documentos') as $idx => $doc) {
            $tipo   = $request->input("documentos.$idx.tipo");
            $campos = $carga->guardar($doc['archivo'], $tipo, $contexto, "sustentos/{$sustento->id}", $lote);

            SustentoDocumento::create($campos + [
                'sustento_id'     => $sustento->id,
                'tipo_doc'        => $tipo,
                'size_bytes'      => $doc['archivo']->getSize(),
                'estado_revision' => 'pendiente',
                'cargado_en'      => now(),
            ]);
            if ($campos['estado_validacion'] !== 'conforme') $conObservaciones++;
        }

        $total = count($request->file('documentos'));
        return $conObservaciones
            ? "{$conObservaciones} de {$total} documento(s) con observaciones de la validación automática."
            : "Validación automática: {$total} documento(s) conforme(s).";
    }
}
