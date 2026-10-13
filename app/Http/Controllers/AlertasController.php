<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\{AlertaControl, AlertaHistorial, Seguimiento, Zona};

/** Alertas de control (detección de anomalías) para Jefatura y Gerencia. */
class AlertasController extends Controller
{
    private const SEVERIDADES = ['Alta', 'Media', 'Baja'];

    public function index(Request $request)
    {
        $this->autorizar($request);
        $estado = $request->input('estado', 'abiertas');

        $base = AlertaControl::query()
            ->when($request->zona_id, fn($q, $v) => $q->where('zona_id', $v))
            ->when($request->tipo, fn($q, $v) => $q->where('tipo', $v))
            ->when($estado === 'abiertas', fn($q) => $q->abiertas())
            ->when(in_array($estado, AlertaControl::ESTADOS), fn($q) => $q->where('estado', $estado));

        $alertas = (clone $base)->with('zona')
            ->when(in_array($request->severidad, self::SEVERIDADES), fn($q) => $q->where('severidad', $request->severidad))
            ->orderByRaw("FIELD(severidad, 'Alta', 'Media', 'Baja')")->orderByRaw("FIELD(estado, 'Nueva', 'En revisión', 'Confirmada', 'Descartada')")
            ->orderByDesc('detectada_en')->get();

        return view('analitica.alertas', [
            'alertas'    => $alertas,
            'porSev'     => (clone $base)->selectRaw('severidad, count(*) n')->groupBy('severidad')->pluck('n', 'severidad'),
            'porEstado'  => AlertaControl::selectRaw('estado, count(*) n')->groupBy('estado')->pluck('n', 'estado'),
            'estado'     => $estado,
            'zonas'      => Zona::whereIn('id', AlertaControl::distinct()->pluck('zona_id'))->orderBy('nombre')->get(),
        ]);
    }

    public function show(Request $request, AlertaControl $alerta)
    {
        $this->autorizar($request);
        $alerta->load('zona', 'historial.usuario');
        return view('analitica.alerta', ['a' => $alerta]);
    }

    /** Cambio de estado con comentario (queda en el historial de la alerta y en la bitácora). */
    public function estado(Request $request, AlertaControl $alerta)
    {
        $this->autorizar($request);
        $datos = $request->validate([
            'estado'     => 'required|in:'.implode(',', AlertaControl::ESTADOS),
            'comentario' => 'required|string|min:10|max:1000',
        ], [
            'comentario.required' => 'Escriba un comentario que sustente el cambio.',
            'comentario.min'      => 'El comentario debe tener al menos 10 caracteres.',
        ]);

        $anterior = $alerta->estado;
        $alerta->update(['estado' => $datos['estado'], 'comentario' => $datos['comentario'], 'actualizado_por' => $request->user()->id]);
        AlertaHistorial::create([
            'alerta_id' => $alerta->id, 'user_id' => $request->user()->id, 'estado_anterior' => $anterior,
            'estado_nuevo' => $datos['estado'], 'comentario' => $datos['comentario'], 'created_at' => now(),
        ]);
        Seguimiento::registrar($request->user()->id, 'Alerta de control', $datos['estado'], null, null, null,
            "{$alerta->titulo} ({$alerta->entidad_nombre}): {$anterior} → {$datos['estado']}. {$datos['comentario']}");

        return redirect()->route('alertas.show', $alerta)->with('success', "Alerta marcada como «{$datos['estado']}».");
    }

    // Jefatura y Gerencia (nivel ≥ 2): las alertas pueden referirse a coordinadores zonales
    private function autorizar(Request $request): void
    {
        abort_unless($request->user()->esAprobador() && $request->user()->nivel() >= 2, 403,
            'Las alertas de control las revisa la Jefatura de Recuperaciones.');
    }
}
