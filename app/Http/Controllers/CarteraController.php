<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\{ClienteCartera, ExpedienteGobierno, ExpedienteGobiernoDoc, Seguimiento};
use App\Services\Validacion\CargaDocumentos;
use Illuminate\Support\Facades\Storage;

class CarteraController extends Controller
{
    public function exclusiones(Request $request)
    {
        $expedientes = ExpedienteGobierno::with('cliente.tienda', 'registrador')
            ->withCount([
                'documentos',
                'documentos as observados_count' => fn($q) => $q->whereIn('estado_validacion', ['observado', 'rechazado'])
                                                              ->where('conforme_manual', false),
            ])
            ->when($request->estado, fn($q, $v) => $q->where('estado', $v))
            ->when($request->q, fn($q, $v) => $q->whereHas('cliente', fn($c) =>
                $c->where('nombre_cliente', 'like', "%$v%")->orWhere('operacion', 'like', "%$v%")->orWhere('cuenta', 'like', "%$v%")))
            ->latest('fecha_registro')->latest('id')
            ->paginate(10)
            ->withQueryString();

        $checklist = ExpedienteGobierno::CHECKLIST;

        return view('cartera.exclusiones', compact('expedientes', 'checklist'));
    }

    public function registrarGobierno(Request $request, CargaDocumentos $carga)
    {
        $request->validate([
            'cliente_ref'         => 'required|string|max:30',
            'programa'            => 'required|in:IMPULSA PERÚ,FAE-MYPE,Reactiva Perú,Otro',
            'motivo_habilitacion' => 'required|in:Garantía rechazada por Cía. Seguros,Pérdida de eficacia de la garantía',
            'responsable'         => 'required|string|max:120',
            'documentos'          => 'required|array|min:1',
            'documentos.*'        => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ], [
            'cliente_ref.required' => 'Indique la cuenta u operación del crédito.',
            'documentos.required'  => 'Cargue al menos un documento del checklist.',
            'documentos.*.mimes'   => 'Formato no admitido. Use PDF, JPG o PNG.',
            'documentos.*.max'     => 'Cada archivo puede pesar como máximo 10 MB.',
        ]);

        // El usuario conoce la cuenta o la operación, no el ID interno
        $ref     = trim($request->cliente_ref);
        $cliente = ClienteCartera::where('cuenta', $ref)->orWhere('operacion', $ref)->first();
        if (!$cliente) {
            return back()->withInput()
                ->withErrors(['cliente_ref' => "No se encontró un crédito con cuenta u operación {$ref}."]);
        }

        $expediente = ExpedienteGobierno::create([
            'cliente_cartera_id'  => $cliente->id,
            'registrado_por'      => $request->user()->id,
            'programa'            => $request->programa,
            'motivo_habilitacion' => $request->motivo_habilitacion,
            'confirmacion_dpto'   => $request->boolean('confirmacion_dpto'),
            'responsable'         => $request->responsable,
            'fecha_registro'      => now()->toDateString(),
            'estado'              => 'Incompleto',
        ]);

        $contexto = $carga->contextoGobierno($cliente);
        $lote = [];
        $cargados = $observados = 0;
        foreach ((array) $request->file('documentos') as $i => $archivo) {
            $tipo = ExpedienteGobierno::CHECKLIST[$i] ?? null;
            if (!$archivo || !$tipo) continue;
            $campos = $carga->guardar($archivo, $tipo, $contexto, "gobierno/{$expediente->id}", $lote);
            ExpedienteGobiernoDoc::create($campos + [
                'expediente_id' => $expediente->id,
                'tipo_doc'      => $tipo,
                'cargado_en'    => now(),
            ]);
            $cargados++;
            if ($campos['estado_validacion'] !== 'conforme') $observados++;
        }
        $expediente->recalcularEstado();

        Seguimiento::registrar($request->user()->id, 'Expediente programa de gobierno', $expediente->estado,
            null, $cliente->nombre_cliente, $cliente->operacion,
            "{$expediente->programa}: {$cargados} de 10 documentos cargados.");

        $mensaje = "Expediente registrado con {$cargados} de 10 documentos.";
        if ($observados) $mensaje .= " {$observados} documento(s) con observaciones de la validación automática.";

        return redirect()->route('cartera.gobierno.show', $expediente)->with('success', $mensaje);
    }

    public function show(ExpedienteGobierno $expediente)
    {
        $expediente->load('cliente.tienda', 'cliente.zona', 'registrador', 'documentos');
        $docs = $expediente->documentos->keyBy('tipo_doc');
        $checklist = ExpedienteGobierno::CHECKLIST;

        return view('cartera.gobierno-show', compact('expediente', 'docs', 'checklist'));
    }

    // Cargar un documento faltante o reemplazar uno observado (el anterior queda como historial)
    public function cargarDocumento(Request $request, ExpedienteGobierno $expediente, CargaDocumentos $carga)
    {
        $request->validate([
            'tipo'    => 'required|in:'.implode(',', ExpedienteGobierno::CHECKLIST),
            'archivo' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ], [
            'archivo.required' => 'Seleccione el archivo a cargar.',
            'archivo.mimes'    => 'Formato no admitido. Use PDF, JPG o PNG.',
            'archivo.max'      => 'El archivo puede pesar como máximo 10 MB.',
        ]);

        $contexto = $carga->contextoGobierno($expediente->cliente, $expediente);
        $expediente->documentos()->where('tipo_doc', $request->tipo)->update(['vigente' => false]);

        $campos = $carga->guardar($request->file('archivo'), $request->tipo, $contexto, "gobierno/{$expediente->id}");
        ExpedienteGobiernoDoc::create($campos + [
            'expediente_id' => $expediente->id,
            'tipo_doc'      => $request->tipo,
            'cargado_en'    => now(),
        ]);
        $expediente->recalcularEstado();

        return back()->with($campos['estado_validacion'] === 'conforme' ? 'success' : 'warning',
            $campos['estado_validacion'] === 'conforme'
                ? "«{$request->tipo}» cargado y conforme."
                : "«{$request->tipo}» cargado con observaciones de la validación automática.");
    }

    // Conformidad manual del revisor sobre un documento observado por la validación automática
    public function conformeManual(Request $request, ExpedienteGobiernoDoc $documento)
    {
        $request->validate(['comentario' => 'required|string|max:500'], [
            'comentario.required' => 'Indique el motivo de la conformidad manual.',
        ]);

        $documento->update(['conforme_manual' => true, 'comentario_revisor' => $request->comentario]);
        $documento->expediente->recalcularEstado();

        return back()->with('success', "«{$documento->tipo_doc}» marcado como conforme manual.");
    }

    public function descargar(ExpedienteGobiernoDoc $documento)
    {
        return Storage::disk('private')->download($documento->ruta_archivo, $documento->nombre_archivo);
    }
}
