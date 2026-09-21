<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\{PropuestaFud, Sustento, Seguimiento};

class BandejaController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $pendientes = PropuestaFud::with('cliente','enviador')
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
            'comentario' => 'nullable|string|max:500',
        ]);

        $user = $request->user();

        $fud->update([
            'estado_fud'           => $request->decision,
            'comentario_aprobador' => $request->comentario,
            'aprobado_por'         => $user->id,
            'fecha_decision'       => now()->toDateString(),
        ]);

        // Si aprobado, crear registro de sustentos pendiente
        if ($request->decision === 'Aprobado') {
            Sustento::create([
                'propuesta_fud_id' => $fud->id,
                'cargado_por'      => $fud->enviado_por,
                'estado'           => 'Pendiente',
            ]);

            // Incrementar contador de conciliaciones del cliente
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