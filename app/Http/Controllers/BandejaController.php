<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\{PropuestaFud, Sustento, Seguimiento, FudAdjunto};

class BandejaController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $pendientes = PropuestaFud::with('cliente.tienda', 'enviador', 'adjuntos', 'padre',
                                         'detalleCuotas', 'detalleCancelacion', 'detalleJudicial')
            ->porAprobador($user->id)
            ->enRevision()
            ->latest()
            ->get();

        return view('bandeja.index', compact('pendientes', 'user'));
    }

    public function decidir(Request $request, PropuestaFud $fud)
    {
        $request->validate([
            'decision'   => 'required|in:Aprobado,Rechazado,Observado',
            'comentario' => 'nullable|required_unless:decision,Aprobado|string|max:1000',
        ], [
            'comentario.required_unless' => 'El comentario es obligatorio al observar o rechazar.',
        ]);

        // Evita decidir dos veces el mismo FUD (doble clic, pestaña vieja, etc.)
        if (!$fud->estaEnRevision()) {
            return redirect()->route('bandeja.index')
                ->with('error', "Este FUD ya fue {$fud->estado_fud} anteriormente; no se puede volver a decidir.");
        }

        $user = $request->user();

        $fud->update([
            'estado_fud'           => $request->decision,
            'comentario_aprobador' => $request->comentario,
            'aprobado_por'         => $user->id,
            'fecha_decision'       => now()->toDateString(),
            'decidido_en'          => now(),
        ]);

        // Guardar adjuntos del aprobador si los subió
        if ($request->hasFile('adjuntos_aprobador')) {
            foreach ($request->file('adjuntos_aprobador') as $archivo) {
                if (!$archivo->isValid()) continue;
                $path = $archivo->store("fud/{$fud->id}/aprobador", 'private');
                FudAdjunto::create([
                    'propuesta_fud_id' => $fud->id,
                    'subido_por'       => $user->id,
                    'nombre_archivo'   => $archivo->getClientOriginalName(),
                    'ruta_archivo'     => $path,
                    'tipo_adjunto'     => 'Adjunto aprobador',
                    'size_bytes'       => $archivo->getSize(),
                ]);
            }
        }

        // Si aprobado → crear sustento pendiente e incrementar contador cliente
        if ($request->decision === 'Aprobado') {
            Sustento::create([
                'propuesta_fud_id' => $fud->id,
                'cargado_por'      => $fud->enviado_por,
                'estado'           => 'Pendiente',
            ]);
            $fud->cliente->increment('veces_conciliado');
        }

        Seguimiento::registrar(
            $user->id,
            'Decisión FUD',
            $request->decision,
            $fud->id,
            $fud->cliente->nombre_cliente,
            $fud->cliente->operacion,
            $request->comentario
        );

        return redirect()->route('bandeja.index')
            ->with('success', "FUD {$request->decision} correctamente.");
    }
}