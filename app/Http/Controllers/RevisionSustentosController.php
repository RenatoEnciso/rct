<?php
// app/Http/Controllers/RevisionSustentosController.php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\{Sustento, SustentoDocumento, Seguimiento};

class RevisionSustentosController extends Controller
{
    
    // Tabla principal: todos los sustentos filtrables
    public function index(Request $request)
    {
        $query = Sustento::with([
            'propuesta.cliente',
            'documentos',
            'propuesta.aprobadoPor',
        ]);

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }
        if ($request->filled('cuenta')) {
            $query->whereHas('propuesta.cliente', function ($q) use ($request) {
                $q->where('cuenta', 'like', '%' . $request->cuenta . '%');
            });
        }
        if ($request->filled('desde')) {
            $query->whereDate('fecha_carga', '>=', $request->desde);
        }
        if ($request->filled('hasta')) {
            $query->whereDate('fecha_carga', '<=', $request->hasta);
        }

        $sustentos = $query->latest()->paginate(20)->withQueryString();

        // Conteos para badges de estado en la barra de filtros
        $conteos = Sustento::selectRaw('estado, count(*) as total')
            ->groupBy('estado')
            ->pluck('total', 'estado');

        return view('revision.index', compact('sustentos', 'conteos'));
    }

    // Vista de detalle para revisar un sustento específico
    public function show(Sustento $sustento)
    {
        $sustento->load(['propuesta.cliente', 'documentos', 'propuesta.aprobadoPor']);
        return view('revision.show', compact('sustento'));
    }

    // Aprobar todo el sustento
    public function aprobar(Request $request, Sustento $sustento)
    {
        $request->validate([
            'comentario' => 'nullable|string|max:500',
        ]);

        abort_unless($sustento->puedeRevisarse(), 422, 'Este sustento ya fue procesado.');

        // Marcar todos los documentos como aprobados
        $sustento->documentos()->update(['estado_revision' => 'aprobado']);

        $sustento->update([
            'estado'             => 'Aprobado',
            'revisado_por'       => $request->user()->id,
            'fecha_revision'     => now(),
            'comentario_revision'=> $request->comentario,
        ]);

        Seguimiento::registrar(
            $request->user()->id,
            'Revisión de sustentos',
            'Aprobado',
            $sustento->propuesta_fud_id,
            $sustento->propuesta->cliente->nombre_cliente,
            $sustento->propuesta->cliente->operacion
        );

        return back()->with('success', 'Sustento aprobado correctamente.');
    }

    // Observar: regresa al analista con comentarios
    public function observar(Request $request, Sustento $sustento)
    {
        $request->validate([
            'comentario'                         => 'required|string|max:1000',
            'docs'                               => 'nullable|array',
            'docs.*.id'                          => 'required|exists:sustento_documentos,id',
            'docs.*.estado_revision'             => 'required|in:aprobado,observado',
            'docs.*.comentario_revisor'          => 'nullable|string|max:500',
        ]);

        abort_unless($sustento->puedeRevisarse(), 422, 'Este sustento ya fue procesado.');

        // Actualizar estado individual por documento
        if ($request->filled('docs')) {
            foreach ($request->docs as $docData) {
                SustentoDocumento::where('id', $docData['id'])
                    ->where('sustento_id', $sustento->id) // seguridad extra
                    ->update([
                        'estado_revision'    => $docData['estado_revision'],
                        'comentario_revisor' => $docData['comentario_revisor'] ?? null,
                    ]);
            }
        }

        $sustento->update([
            'estado'             => 'Observado',
            'revisado_por'       => $request->user()->id,
            'fecha_revision'     => now(),
            'comentario_revision'=> $request->comentario,
        ]);

        Seguimiento::registrar(
            $request->user()->id,
            'Revisión de sustentos',
            'Observado',
            $sustento->propuesta_fud_id,
            $sustento->propuesta->cliente->nombre_cliente,
            $sustento->propuesta->cliente->operacion
        );

        return back()->with('warning', 'Sustento marcado como observado. El analista deberá corregir y recargar.');
    }

    // Rechazar definitivamente
    public function rechazar(Request $request, Sustento $sustento)
    {
        $request->validate([
            'comentario' => 'required|string|max:1000',
        ]);

        abort_unless($sustento->puedeRevisarse(), 422, 'Este sustento ya fue procesado.');

        $sustento->update([
            'estado'             => 'Rechazado',
            'revisado_por'       => $request->user()->id,
            'fecha_revision'     => now(),
            'comentario_revision'=> $request->comentario,
        ]);

        Seguimiento::registrar(
            $request->user()->id,
            'Revisión de sustentos',
            'Rechazado',
            $sustento->propuesta_fud_id,
            $sustento->propuesta->cliente->nombre_cliente,
            $sustento->propuesta->cliente->operacion
        );

        return back()->with('error', 'Sustento rechazado.');
    }
}