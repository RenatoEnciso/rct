<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\{
    PropuestaFud, ClienteCartera, FudDetalleCuotas,
    FudDetalleCancelacion, FudDetalleJudicial,
    FudAdjunto, Seguimiento, User
};
use Illuminate\Support\Facades\Storage;
use App\Services\AsignadorAprobador;
use App\Services\RegistroFud;

class FudController extends Controller
{
    // ── Listado de propuestas del ejecutivo ──────────────────
    public function index(Request $request)
    {
        $propuestas = PropuestaFud::with('cliente','aprobador','versiones')
            ->porEnviador($request->user()->id)
            ->raiz()
            ->when($request->estado, fn($q) => $q->where('estado_fud', $request->estado))
            ->when($request->q, fn($q, $v) =>
                $q->whereHas('cliente', fn($c) =>
                    $c->where('nombre_cliente', 'like', "%$v%")
                      ->orWhere('operacion', 'like', "%$v%")
                ))
            ->latest()
            ->paginate(15);

        return view('fud.index', compact('propuestas'));
    }

    // ── Formulario nuevo FUD ─────────────────────────────────
    public function create()
    {
        $zonas = \App\Models\Zona::with('tiendas')->get();
        // Desde "Mi prioridad de hoy": el cliente llega seleccionado
        $clienteInicial = request()->integer('cliente') ?: null;

        return view('fud.create', compact('zonas', 'clienteInicial'));
    }

    // ── Guardar nuevo FUD ────────────────────────────────────
    public function store(Request $request, RegistroFud $registro)
    {
        $request->validate([
            'cliente_cartera_id' => 'required|exists:clientes_cartera,id',
            'tipo_negociacion'   => 'required|in:cuotas,cancelacion,extrajudicial,castigada,focmac',
            'motivo'             => 'required|min:20',
        ], [
            'cliente_cartera_id.required' => 'Seleccione un cliente de la cartera.',
            'motivo.required'             => 'El motivo es obligatorio.',
            'motivo.min'                  => 'El motivo debe tener al menos 20 caracteres.',
        ]);

        $cliente = ClienteCartera::findOrFail($request->cliente_cartera_id);

        // Mismo registro que el canal de mensajería: límite de usos, aprobador automático, detalle y bitácora
        try {
            $fud = $registro->registrar($cliente, $request->user(), $request->all(), (array) $request->file('adjuntos', []));
        } catch (\DomainException $e) {
            return back()->withInput()->withErrors(['cliente_cartera_id' => $e->getMessage()]);
        }
        $aprobador = $fud->aprobador;

        return redirect()->route('fud.show', $fud)
            ->with('success', "FUD enviado a {$aprobador->name} ({$aprobador->rol->nombre}).");
    }

    // ── Ver detalle + hilo de versiones ─────────────────────
    public function show(PropuestaFud $fud)
    {
        $fud->load([
            'cliente','enviador','aprobador','aprobadoPor',
            'detalleCuotas','detalleCancelacion','detalleJudicial',
            'adjuntos.subidor','sustentos.documentos',
        ]);

        // Hilo completo de versiones
        $hilo = $fud->hilo();

        return view('fud.show', compact('fud', 'hilo'));
    }

    // ── Formulario corrección (FUD observado) ────────────────
    public function edit(PropuestaFud $fud)
    {
        if ($fud->enviado_por !== auth()->id()) abort(403);
        if (!$fud->estaObservado()) {
            return redirect()->route('fud.show', $fud)
                ->with('error', 'Solo se puede corregir un FUD observado.');
        }

        $fud->load('cliente', 'adjuntos', 'aprobador.rol', 'aprobadoPor.rol', 'detalleCuotas',
                    'detalleCancelacion', 'detalleJudicial');

        return view('fud.edit', compact('fud'));
    }

    // ── Guardar corrección (crea NUEVO FUD con parent_fud_id) ─
    public function update(Request $request, PropuestaFud $fud)
    {
        if ($fud->enviado_por !== auth()->id()) abort(403);
        if (!$fud->estaObservado()) {
            return redirect()->route('fud.show', $fud)
                ->with('error', 'Solo se puede corregir un FUD observado.');
        }

        $request->validate([
            'motivo' => 'required|min:20',
        ], [
            'motivo.required' => 'El motivo es obligatorio.',
            'motivo.min'      => 'El motivo debe tener al menos 20 caracteres.',
        ]);

        $user = $request->user();

        // La corrección vuelve al mismo aprobador que observó el FUD
        $nuevoFud = $this->crearFud($request, $user->id, $fud->nivel_autonomia_req, [
            'parent_fud_id'      => $fud->id,
            'cliente_cartera_id' => $fud->cliente_cartera_id,
            'aprobador_id'       => $fud->aprobado_por ?? $fud->aprobador_id,
            'veces_observado'    => $fud->veces_observado + 1,
        ]);

        $this->guardarDetalle($request, $nuevoFud->id);

        // Los adjuntos de la versión anterior pasan a la nueva, salvo los que el ejecutivo quitó
        $quitar = array_filter((array) $request->input('adjuntos_eliminar', []));
        foreach ($fud->adjuntos()->whereNotIn('id', $quitar)->get() as $adj) {
            FudAdjunto::create([
                'propuesta_fud_id' => $nuevoFud->id,
                'subido_por'       => $adj->subido_por,
                'nombre_archivo'   => $adj->nombre_archivo,
                'ruta_archivo'     => $adj->ruta_archivo,
                'tipo_adjunto'     => $adj->tipo_adjunto,
                'size_bytes'       => $adj->size_bytes,
            ]);
        }
        $this->guardarAdjuntos($request, $nuevoFud->id, $user->id, $request->input('tipo_adjunto_nuevo'));

        // Marcar el FUD anterior como "Corregido" para que no aparezca activo
        $fud->update(['estado_fud' => 'Rechazado']);

        Seguimiento::registrar(
            $user->id,
            'Corrección de FUD',
            'En revisión',
            $nuevoFud->id,
            $fud->cliente->nombre_cliente,
            $fud->cliente->operacion,
            "Corrección #{$nuevoFud->veces_observado} enviada tras observación."
        );

        return redirect()->route('fud.show', $nuevoFud)
            ->with('success', 'Corrección enviada. El aprobador revisará tu nuevo FUD.');
    }

    // ── Subir adjunto a un FUD existente ────────────────────
    public function subirAdjunto(Request $request, PropuestaFud $fud)
    {
        if ($fud->enviado_por !== auth()->id()) abort(403);

        $request->validate([
            'archivo'      => 'required|file|mimes:pdf,jpg,jpeg,png,xlsx,docx|max:10240',
            'tipo_adjunto' => 'required|string',
        ]);

        $archivo = $request->file('archivo');
        $path    = $archivo->store("fud/{$fud->id}/adjuntos", 'private');

        FudAdjunto::create([
            'propuesta_fud_id' => $fud->id,
            'subido_por'       => auth()->id(),
            'nombre_archivo'   => $archivo->getClientOriginalName(),
            'ruta_archivo'     => $path,
            'tipo_adjunto'     => $request->tipo_adjunto,
            'size_bytes'       => $archivo->getSize(),
        ]);

        return back()->with('success', 'Archivo adjuntado correctamente.');
    }

    // ── Eliminar adjunto ────────────────────────────────────
    public function eliminarAdjunto(FudAdjunto $adjunto)
    {
        if ($adjunto->subido_por !== auth()->id()) abort(403);
        \Storage::disk('private')->delete($adjunto->ruta_archivo);
        $adjunto->delete();
        return back()->with('success', 'Adjunto eliminado.');
    }

    // ── API: buscar clientes ─────────────────────────────────
    public function buscarClientes(Request $request)
    {
        $clientes = ClienteCartera::disponibles()
            ->when($request->id,        fn($q) => $q->where('id', $request->id))
            ->when($request->zona_id,   fn($q) => $q->where('zona_id', $request->zona_id))
            ->when($request->tienda_id, fn($q) => $q->where('tienda_id', $request->tienda_id))
            ->when($request->estado,    fn($q) => $q->where('estado_cartera', $request->estado))
            ->when($request->cuenta,    fn($q) => $q->where('cuenta', 'like', "%{$request->cuenta}%"))
            ->when($request->operacion, fn($q) => $q->where('operacion', 'like', "%{$request->operacion}%"))
            ->when($request->nombre,    fn($q) => $q->where('nombre_cliente', 'like', "%{$request->nombre}%"))
            ->when($request->documento, fn($q) => $q->where('nro_documento', 'like', "%{$request->documento}%"))
            ->with('zona', 'tienda', 'priorizacion')
            ->limit(50)
            ->get();

        // Vista previa del aprobador que asignará el sistema
        $asignador = app(AsignadorAprobador::class);
        $clientes->each(function ($c) use ($asignador, $request) {
            $apr = $asignador->asignar($c, $request->user()->id);
            $c->setAttribute('aprobador_asignado', $apr ? "{$apr->name} — {$apr->rol->nombre}" : null);
        });

        return response()->json($clientes);
    }

    // ── Helpers privados ─────────────────────────────────────
    private function crearFud(Request $request, int $userId, int $nivelReq, array $extra = []): PropuestaFud
    {
        return PropuestaFud::create(array_merge([
            'cliente_cartera_id'    => $request->cliente_cartera_id,
            'enviado_por'           => $userId,
            'tipo_negociacion'      => $request->tipo_negociacion,
            'tipo_negociacion_desc' => $request->tipo_negociacion_desc,
            'en_campana'            => true,
            'tipo_operacion'        => $request->tipo_operacion ?? 'CAMPAÑA',
            'motivo'                => $request->motivo,
            'nivel_autonomia_req'   => $nivelReq,
            'estado_fud'            => 'En revisión',
            'fecha_envio'           => now()->toDateString(),
        ], $extra));
    }

    private function guardarDetalle(Request $request, int $fudId): void
    {
        RegistroFud::guardarDetalle($request->tipo_negociacion, $fudId, $request->all());
    }

    private function guardarAdjuntos(Request $request, int $fudId, int $userId, ?string $tipo = null): void
    {
        $tipos = ['Evidencia negociación', 'Captura sistema', 'Documento cliente', 'Otro'];
        $tipo  = in_array($tipo, $tipos) ? $tipo : 'Evidencia negociación';

        if (!$request->hasFile('adjuntos')) return;

        foreach ($request->file('adjuntos') as $archivo) {
            if (!$archivo->isValid()) continue;
            $path = $archivo->store("fud/{$fudId}/adjuntos", 'private');
            FudAdjunto::create([
                'propuesta_fud_id' => $fudId,
                'subido_por'       => $userId,
                'nombre_archivo'   => $archivo->getClientOriginalName(),
                'ruta_archivo'     => $path,
                'tipo_adjunto'     => $tipo,
                'size_bytes'       => $archivo->getSize(),
            ]);
        }
    }
    public function download(FudAdjunto $adjunto)
{
    // Verificar que el usuario tenga acceso al FUD de este adjunto
    $fud = $adjunto->propuesta; // ajusta según tu relación
    abort_unless(
        auth()->id() === $fud->enviado_por || auth()->user()->puedeAprobar(),
        403
    );

    return Storage::disk('private')->download(
        $adjunto->ruta_archivo,
        $adjunto->nombre_archivo
    );
}
}