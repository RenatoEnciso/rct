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
            'documentos'          => 'required|array|min:1',
            'documentos.*.tipo'   => 'required|string',
            'documentos.*.archivo'=> 'required|file|max:10240', // 10MB máx
        ]);

        foreach ($request->file('documentos') as $doc) {
            $path = $doc['archivo']->store('sustentos', 'private');

            SustentoDocumento::create([
                'sustento_id'   => $sustento->id,
                'tipo_doc'      => $doc['tipo'],
                'nombre_archivo'=> $doc['archivo']->getClientOriginalName(),
                'ruta_archivo'  => $path,
                'size_bytes'    => $doc['archivo']->getSize(),
            ]);
        }

        $sustento->update([
            'estado'      => 'Completo',
            'fecha_carga' => now()->toDateString(),
        ]);

        Seguimiento::registrar(
            $request->user()->id,
            'Carga de sustentos',
            'Sustentos completos',
            $sustento->propuesta_fud_id,
            $sustento->propuesta->cliente->nombre_cliente,
            $sustento->propuesta->cliente->operacion
        );

        return redirect()->route('sustentos.index')
            ->with('success', 'Sustentos registrados correctamente.');
    }
}