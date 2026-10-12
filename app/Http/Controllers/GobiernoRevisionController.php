<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\{ExpedienteGobierno, ExpedienteGobiernoDoc, ExpedienteGobiernoRevision, Seguimiento};
use App\Services\Honramiento\{Checklist, Permisos};

/**
 * Revisión de expedientes de honramiento en dos niveles:
 * zona (Coordinador Zonal) y central (Jefatura de Recuperaciones).
 * Cada decisión queda en el historial (quién, cuándo, nivel, resultado, comentario).
 */
class GobiernoRevisionController extends Controller
{
    public function __construct(private Permisos $permisos, private Checklist $checklist) {}

    // Conforme / Observado de un documento
    public function documento(Request $request, ExpedienteGobiernoDoc $documento)
    {
        $e = $documento->expediente;
        abort_unless($documento->vigente && $this->permisos->puedeRevisarZona($request->user(), $e), 403);
        $request->validate([
            'resultado'  => 'required|in:conforme,observado',
            'comentario' => 'nullable|required_if:resultado,observado|string|max:500',
        ], ['comentario.required_if' => 'Indique qué debe corregir la tienda.']);

        if (!in_array($documento->estado, ['cargado', 'conforme', 'observado'])) {
            return back()->with('error', 'Solo se revisan documentos presentados.');
        }
        if ($e->estado === 'Conforme') {
            return back()->with('error', 'El expediente ya tiene conformidad central.');
        }

        $documento->update([
            'estado' => $request->resultado, 'comentario_revisor' => $request->comentario,
            'revisado_por' => $request->user()->id, 'revisado_en' => now(),
        ]);
        if ($request->resultado === 'observado') {
            // Una observación anula la conformidad de zona obtenida antes
            $e->forceFill(['revisado_zona_en' => null, 'revisado_central_en' => null])->saveQuietly();
        }
        $e->recalcularEstado();

        $nombre = $documento->tipo->nombre_corto ?? $documento->tipo_doc;
        return back()->with($request->resultado === 'conforme' ? 'success' : 'warning',
            $request->resultado === 'conforme' ? "«{$nombre}» conforme." : "«{$nombre}» observado. Se notificará a la tienda.");
    }

    // Decisión sobre el expediente en el nivel que corresponde al usuario
    public function expediente(Request $request, ExpedienteGobierno $expediente)
    {
        $u = $request->user();
        $nivel = $this->permisos->nivelRevision($u, $expediente);
        abort_unless($nivel, 403, 'El expediente no está pendiente de su revisión.');

        $request->validate([
            'resultado'  => 'required|in:conforme,observado',
            'comentario' => 'nullable|required_if:resultado,observado|string|max:1000',
        ], ['comentario.required_if' => 'Indique el motivo de la observación.']);

        $items = $this->checklist->documentos($expediente);
        $exigidos = $items->where('exigido', true);

        if ($request->resultado === 'conforme') {
            if ($exigidos->contains('estado', 'observado') || $exigidos->whereIn('estado', ['falta', 'no_disponible'])->count()) {
                return back()->with('error', 'No se puede dar conformidad: hay documentos exigidos por COFIDE faltantes u observados.');
            }
            // Los documentos exigidos presentados quedan conformes
            foreach ($exigidos as $it) {
                if ($it['doc'] && $it['estado'] === 'cargado') {
                    $it['doc']->update(['estado' => 'conforme', 'revisado_por' => $u->id, 'revisado_en' => now()]);
                }
            }
            $expediente->forceFill([$nivel === 'zona' ? 'revisado_zona_en' : 'revisado_central_en' => now()])->saveQuietly();
        } elseif (!$exigidos->contains('estado', 'observado') && !$items->contains('estado', 'observado')) {
            return back()->with('error', 'Para observar el expediente, marque como «Observado» el o los documentos que debe corregir la tienda.');
        }

        ExpedienteGobiernoRevision::create([
            'expediente_id' => $expediente->id, 'nivel' => $nivel, 'resultado' => $request->resultado,
            'comentario' => $request->comentario, 'user_id' => $u->id, 'origen' => 'rct', 'revisado_en' => now(),
        ]);
        if ($request->comentario && $request->resultado === 'observado') {
            $expediente->forceFill(['observaciones' => $request->comentario])->saveQuietly();
        }
        $expediente->recalcularEstado();

        Seguimiento::registrar($u->id, 'Revisión de expediente de honramiento', $expediente->estado, null, $expediente->cliente,
            $expediente->id_externo, 'Nivel '.($nivel === 'zona' ? 'zona' : 'central').': '.$request->resultado.($request->comentario ? '. '.$request->comentario : ''));

        $msg = $request->resultado === 'conforme'
            ? ($nivel === 'zona' ? 'Revisión por zona conforme. El expediente pasa a revisión central.' : 'Conformidad central registrada. Expediente conforme.')
            : 'Expediente observado. La tienda recibirá el aviso para corregir.';
        return redirect()->route('gobierno.show', $expediente)->with($request->resultado === 'conforme' ? 'success' : 'warning', $msg);
    }
}
