<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\{Sustento, SustentoDocumento, Seguimiento};

class SustentosController extends Controller
{
    public function index(Request $request)
    {
        $sustentos = Sustento::with('propuesta.cliente')
            ->where('cargado_por', $request->user()->id)
            ->where('estado', 'Pendiente')
            ->get();

        return view('sustentos.index', compact('sustentos'));
    }

public function cargar(Request $request, Sustento $sustento)
{
    $request->validate([
        'documentos'                       => 'required|array|min:1',
        'documentos.*.tipo'                => 'required|string',
        'documentos.*.archivo'             => 'required|file|max:10240',
        'documentos.*.estado_validacion'   => 'nullable|in:ok,warn,error',
        'documentos.*.observaciones'       => 'nullable|string', // JSON stringificado
    ]);

    foreach ($request->file('documentos') as $idx => $doc) {
        $archivo = $doc['archivo'];
        $input   = $request->input('documentos.' . $idx); // tiene tipo, estado_validacion, observaciones

        $path = $archivo->store('sustentos', 'private');

        SustentoDocumento::create([
            'sustento_id'          => $sustento->id,
            'tipo_doc'             => $input['tipo'],
            'nombre_archivo'       => $archivo->getClientOriginalName(),
            'ruta_archivo'         => $path,
            'size_bytes'           => $archivo->getSize(),
            'estado_validacion'    => $input['estado_validacion'] ?? null,
            'observaciones_carga'  => $input['observaciones']     ?? null,
        ]);
    }

    // Estado pasa a "En revisión", no a "Completo" directamente
    $sustento->update([
        'estado'      => 'En revisión',
        'fecha_carga' => now()->toDateString(),
    ]);

    Seguimiento::registrar(
        $request->user()->id,
        'Carga de sustentos',
        'En revisión',
        $sustento->propuesta_fud_id,
        $sustento->propuesta->cliente->nombre_cliente,
        $sustento->propuesta->cliente->operacion
    );

    return redirect()->route('sustentos.index')
        ->with('success', 'Sustentos enviados a revisión.');
}
// SustentosController@recargar
public function recargar(Request $request, Sustento $sustento)
{
    abort_unless($sustento->estado === 'Observado', 422);
    abort_unless($sustento->cargado_por === $request->user()->id, 403);

    $request->validate([
        'documentos'                       => 'required|array|min:1',
        'documentos.*.tipo'                => 'required|string',
        'documentos.*.archivo'             => 'required|file|max:10240',
        'documentos.*.estado_validacion'   => 'nullable|in:ok,warn,error',
        'documentos.*.observaciones'       => 'nullable|string',
    ]);

    // Solo reemplaza los documentos observados
    foreach ($request->file('documentos') as $idx => $doc) {
        $tipo  = $doc['tipo'];
        $extra = $request->input('documentos.' . $idx);

        // Elimina el documento anterior del mismo tipo (el observado)
        $anterior = $sustento->documentos()->where('tipo_doc', $tipo)->first();
        if ($anterior) {
            \Storage::disk('private')->delete($anterior->ruta_archivo);
            $anterior->delete();
        }

        $path = $doc['archivo']->store('sustentos', 'private');
        SustentoDocumento::create([
            'sustento_id'         => $sustento->id,
            'tipo_doc'            => $tipo,
            'nombre_archivo'      => $doc['archivo']->getClientOriginalName(),
            'ruta_archivo'        => $path,
            'size_bytes'          => $doc['archivo']->getSize(),
            'estado_validacion'   => $extra['estado_validacion'] ?? null,
            'observaciones_carga' => $extra['observaciones'] ?? null,
            'estado_revision'     => 'pendiente', // resetea la revisión anterior
        ]);
    }

    $sustento->update([
        'estado'              => 'En revisión',
        'fecha_carga'         => now()->toDateString(),
        'comentario_revision' => null, // limpia el comentario anterior del revisor
    ]);

    Seguimiento::registrar(
        $request->user()->id,
        'Recarga de sustentos',
        'En revisión (corregido)',
        $sustento->propuesta_fud_id,
        $sustento->propuesta->cliente->nombre_cliente,
        $sustento->propuesta->cliente->operacion
    );

    return redirect()->route('sustentos.index')
        ->with('success', 'Documentos corregidos enviados a revisión nuevamente.');
}
}