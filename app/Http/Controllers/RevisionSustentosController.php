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
            'cargador',
        ]);

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }
        if ($request->filled('q')) {
            $query->whereHas('propuesta.cliente', function ($q) use ($request) {
                $q->where('cuenta', 'like', '%' . $request->q . '%')
                  ->orWhere('operacion', 'like', '%' . $request->q . '%')
                  ->orWhere('nombre_cliente', 'like', '%' . $request->q . '%');
            });
        }
        if ($request->filled('desde')) {
            $query->whereDate('fecha_carga', '>=', $request->desde);
        }
        if ($request->filled('hasta')) {
            $query->whereDate('fecha_carga', '<=', $request->hasta);
        }

        // Primero lo que espera revisión
        $sustentos = $query->orderByRaw("FIELD(estado, 'En revisión', 'Observado', 'Pendiente', 'Aprobado', 'Rechazado')")
            ->latest('fecha_carga')->latest('id')->paginate(15)->withQueryString();

        // Conteos para badges de estado en la barra de filtros
        $conteos = Sustento::selectRaw('estado, count(*) as total')
            ->groupBy('estado')
            ->pluck('total', 'estado');

        return view('revision.index', compact('sustentos', 'conteos'));
    }

    // Vista de detalle para revisar un sustento específico
    public function show(Sustento $sustento)
    {
        $sustento->load(['propuesta.cliente.tienda', 'propuesta.detalleCancelacion', 'documentos', 'historial',
                         'propuesta.aprobadoPor', 'cargador', 'revisador']);
        return view('revision.show', compact('sustento'));
    }

    // Aprobar todo el sustento
    public function aprobar(Request $request, Sustento $sustento)
    {
        if (!$sustento->puedeRevisarse()) {
            return back()->with('error', "Este sustento ya fue revisado (estado: {$sustento->estado}).");
        }

        // Si la validación automática observó algún documento, aprobar exige sustentar la conformidad
        $conHallazgos = $sustento->documentos()->whereIn('estado_validacion', ['observado', 'rechazado'])->exists();
        $request->validate([
            'comentario' => ($conHallazgos ? 'required' : 'nullable').'|string|max:500',
        ], [
            'comentario.required' => 'Hay documentos con observaciones de la validación automática: indique el motivo de la conformidad manual.',
        ]);

        // Documentos con hallazgos automáticos quedan como "Conforme manual"
        foreach ($sustento->documentos as $doc) {
            $manual = $doc->tieneObservacionesCarga();
            $doc->update([
                'estado_revision'    => 'aprobado',
                'conforme_manual'    => $manual,
                'comentario_revisor' => $manual ? ($doc->comentario_revisor ?: $request->comentario) : $doc->comentario_revisor,
            ]);
        }

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
            'docs.*.estado_revision'             => 'nullable|in:aprobado,observado',
            'docs.*.comentario_revisor'          => 'nullable|string|max:500',
        ]);

        if (!$sustento->puedeRevisarse()) {
            return back()->with('error', "Este sustento ya fue revisado (estado: {$sustento->estado}).");
        }

        // Actualizar estado individual por documento
        if ($request->filled('docs')) {
            foreach ($request->docs as $docData) {
                // Documento sin marcar: queda observado si tiene comentario, si no se deja igual
                if (empty($docData['estado_revision'])) {
                    if (empty($docData['comentario_revisor'])) continue;
                    $docData['estado_revision'] = 'observado';
                }
                $doc = SustentoDocumento::where('id', $docData['id'])
                    ->where('sustento_id', $sustento->id) // seguridad extra
                    ->first();
                if (!$doc) continue;

                // Aprobar un documento que la validación automática observó = conformidad manual (con comentario)
                $manual = $docData['estado_revision'] === 'aprobado' && $doc->tieneObservacionesCarga();
                if ($manual && empty($docData['comentario_revisor'])) {
                    return back()->withInput()->withErrors(['comentario' =>
                        "Indique el motivo de la conformidad manual del documento «{$doc->tipo_doc}»."]);
                }
                $doc->update([
                    'estado_revision'    => $docData['estado_revision'],
                    'conforme_manual'    => $manual,
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

        if (!$sustento->puedeRevisarse()) {
            return back()->with('error', "Este sustento ya fue revisado (estado: {$sustento->estado}).");
        }

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