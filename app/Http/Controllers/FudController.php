<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\{
    PropuestaFud, ClienteCartera, FudDetalleCuotas,
    FudDetalleCancelacion, FudDetalleJudicial,
    FudAdjunto, Seguimiento, User
};
use Illuminate\Support\Facades\Storage;

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
        $zonas       = \App\Models\Zona::with('tiendas')->get();
        $aprobadores = User::whereHas('rol', fn($q) =>
            $q->where('grupo', 'aprobador')
        )->get();

        return view('fud.create', compact('zonas', 'aprobadores'));
    }

    // ── Guardar nuevo FUD ────────────────────────────────────
    public function store(Request $request)
    {
        $request->validate([
            'cliente_cartera_id' => 'required|exists:clientes_cartera,id',
            'tipo_negociacion'   => 'required|in:cuotas,cancelacion,extrajudicial,castigada,focmac',
            'motivo'             => 'required|min:20',
            'aprobador_id'       => 'required|exists:users,id',
        ]);

        $user    = $request->user();
        $cliente = ClienteCartera::findOrFail($request->cliente_cartera_id);

        if ($cliente->superaLimite()) {
            return back()->withErrors(['cliente_cartera_id' =>
                'Este cliente ya alcanzó el límite de 4 conciliaciones en 12 meses.']);
        }

        $nivelReq = ($cliente->esMayor3UIT() ||
                     in_array($cliente->estado_cartera, ['JUDICIAL','EXTRAJUDICIAL'])) ? 2 : 1;

        $fud = $this->crearFud($request, $user->id, $nivelReq);

        $this->guardarDetalle($request, $fud->id);
        $this->guardarAdjuntos($request, $fud->id, $user->id);

        Seguimiento::registrar(
            $user->id, 'FUD enviado',
            'En revisión', $fud->id,
            $cliente->nombre_cliente, $cliente->operacion
        );

        return redirect()->route('fud.show', $fud)
            ->with('success', 'FUD enviado correctamente al aprobador.');
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
        if ($fud->estaAprobado()) abort(403, 'No se puede editar un FUD aprobado.');

        $fud->load('cliente', 'adjuntos', 'detalleCuotas',
                    'detalleCancelacion', 'detalleJudicial');

        $aprobadores = User::whereHas('rol', fn($q) =>
            $q->where('grupo', 'aprobador')
        )->get();

        return view('fud.edit', compact('fud', 'aprobadores'));
    }

    // ── Guardar corrección (crea NUEVO FUD con parent_fud_id) ─
    public function update(Request $request, PropuestaFud $fud)
    {
        if ($fud->enviado_por !== auth()->id()) abort(403);
        if ($fud->estaAprobado()) abort(403, 'No se puede editar un FUD aprobado.');

        $request->validate([
            'motivo'       => 'required|min:20',
            'aprobador_id' => 'required|exists:users,id',
        ]);

        $user     = $request->user();
        $nivelReq = ($fud->cliente->esMayor3UIT() ||
                     in_array($fud->cliente->estado_cartera, ['JUDICIAL','EXTRAJUDICIAL'])) ? 2 : 1;

        // Crear nuevo FUD como corrección del observado
        $nuevoFud = $this->crearFud($request, $user->id, $nivelReq, [
            'parent_fud_id'      => $fud->id,
            'cliente_cartera_id' => $fud->cliente_cartera_id,
            'veces_observado'    => $fud->veces_observado + 1,
        ]);

        $this->guardarDetalle($request, $nuevoFud->id);
        $this->guardarAdjuntos($request, $nuevoFud->id, $user->id);

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
            ->when($request->zona_id,   fn($q) => $q->where('zona_id', $request->zona_id))
            ->when($request->tienda_id, fn($q) => $q->where('tienda_id', $request->tienda_id))
            ->when($request->estado,    fn($q) => $q->where('estado_cartera', $request->estado))
            ->when($request->cuenta,    fn($q) => $q->where('cuenta', 'like', "%{$request->cuenta}%"))
            ->when($request->operacion, fn($q) => $q->where('operacion', 'like', "%{$request->operacion}%"))
            ->when($request->nombre,    fn($q) => $q->where('nombre_cliente', 'like', "%{$request->nombre}%"))
            ->when($request->documento, fn($q) => $q->where('nro_documento', 'like', "%{$request->documento}%"))
            ->with('zona','tienda')
            ->limit(50)
            ->get();

        return response()->json($clientes);
    }

    // ── Helpers privados ─────────────────────────────────────
    private function crearFud(Request $request, int $userId, int $nivelReq, array $extra = []): PropuestaFud
    {
        return PropuestaFud::create(array_merge([
            'cliente_cartera_id'    => $request->cliente_cartera_id,
            'enviado_por'           => $userId,
            'aprobador_id'          => $request->aprobador_id,
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
        match($request->tipo_negociacion) {
            'cuotas' => FudDetalleCuotas::create([
                'propuesta_fud_id'   => $fudId,
                'cuota_desde'        => $request->cuota_desde,
                'cuota_hasta'        => $request->cuota_hasta,
                'desc_interes'       => $request->desc_interes,
                'desc_moratorio'     => $request->desc_moratorio ?? 100,
                'pone_al_dia'        => $request->boolean('pone_al_dia'),
                'cierra_mes_vigente' => $request->boolean('cierra_mes_vigente'),
            ]),
            'cancelacion' => FudDetalleCancelacion::create([
                'propuesta_fud_id' => $fudId,
                'monto_a_pagar'    => $request->monto_a_pagar,
                'desc_interes'     => $request->desc_interes_canc,
                'desc_capital'     => $request->desc_capital ?? 0,
                'fecha_limite'     => $request->fecha_limite,
                'mayor_tres_uit'   => $request->boolean('mayor_tres_uit'),
                'sunarp_positivo'  => $request->boolean('sunarp_positivo'),
            ]),
            default => FudDetalleJudicial::create([
                'propuesta_fud_id' => $fudId,
                'monto_gravamen'   => $request->monto_gravamen,
                'etapa_procesal'   => $request->etapa_procesal,
                'beneficio_pct_sk' => $request->beneficio_pct_sk,
            ]),
        };
    }

    private function guardarAdjuntos(Request $request, int $fudId, int $userId): void
    {
        if (!$request->hasFile('adjuntos')) return;

        foreach ($request->file('adjuntos') as $archivo) {
            if (!$archivo->isValid()) continue;
            $path = $archivo->store("fud/{$fudId}/adjuntos", 'private');
            FudAdjunto::create([
                'propuesta_fud_id' => $fudId,
                'subido_por'       => $userId,
                'nombre_archivo'   => $archivo->getClientOriginalName(),
                'ruta_archivo'     => $path,
                'tipo_adjunto'     => 'Evidencia negociación',
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