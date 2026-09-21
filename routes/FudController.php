<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\{PropuestaFud, ClienteCartera, FudDetalleCuotas,
                FudDetalleCancelacion, FudDetalleJudicial, Seguimiento};
use App\Models\User;

class FudController extends Controller
{
    // Lista de propuestas del ejecutivo
    public function index(Request $request)
    {
        $propuestas = PropuestaFud::with('cliente')
            ->porEnviador($request->user()->id)
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

    // Formulario nuevo FUD
    public function create()
    {
        $zonas     = \App\Models\Zona::with('tiendas')->get();
        $aprobadores = User::whereHas('rol', fn($q) =>
            $q->where('grupo', 'aprobador')
        )->get();

        return view('fud.create', compact('zonas', 'aprobadores'));
    }

    // Guardar FUD
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

        // Validar límite de conciliaciones
        if ($cliente->superaLimite()) {
            return back()->withErrors(['cliente_cartera_id' =>
                'Este cliente ya alcanzó el límite de 4 conciliaciones en 12 meses.']);
        }

        // Calcular nivel de autonomía requerido
        $nivelReq = $cliente->esMayor3UIT() ||
                    in_array($cliente->estado_cartera, ['JUDICIAL','EXTRAJUDICIAL']) ? 2 : 1;

        $fud = PropuestaFud::create([
            'cliente_cartera_id'   => $cliente->id,
            'enviado_por'          => $user->id,
            'aprobador_id'         => $request->aprobador_id,
            'tipo_negociacion'     => $request->tipo_negociacion,
            'tipo_negociacion_desc'=> $request->tipo_negociacion_desc,
            'en_campana'           => true,
            'tipo_operacion'       => $request->tipo_operacion ?? 'CAMPAÑA',
            'motivo'               => $request->motivo,
            'nivel_autonomia_req'  => $nivelReq,
            'estado_fud'           => 'En revisión',
            'fecha_envio'          => now()->toDateString(),
        ]);

        // Guardar detalle según tipo
        match($request->tipo_negociacion) {
            'cuotas' => FudDetalleCuotas::create([
                'propuesta_fud_id' => $fud->id,
                'cuota_desde'      => $request->cuota_desde,
                'cuota_hasta'      => $request->cuota_hasta,
                'desc_interes'     => $request->desc_interes,
                'desc_moratorio'   => $request->desc_moratorio ?? 100,
                'pone_al_dia'      => $request->boolean('pone_al_dia'),
                'cierra_mes_vigente'=> $request->boolean('cierra_mes_vigente'),
            ]),
            'cancelacion' => FudDetalleCancelacion::create([
                'propuesta_fud_id' => $fud->id,
                'monto_a_pagar'    => $request->monto_a_pagar,
                'desc_interes'     => $request->desc_interes_canc,
                'desc_capital'     => $request->desc_capital ?? 0,
                'fecha_limite'     => $request->fecha_limite,
                'mayor_tres_uit'   => $request->boolean('mayor_tres_uit'),
                'sunarp_positivo'  => $request->boolean('sunarp_positivo'),
            ]),
            default => FudDetalleJudicial::create([
                'propuesta_fud_id' => $fud->id,
                'monto_gravamen'   => $request->monto_gravamen,
                'etapa_procesal'   => $request->etapa_procesal,
                'beneficio_pct_sk' => $request->beneficio_pct_sk,
            ]),
        };

        Seguimiento::registrar(
            $user->id, 'FUD – ' . $fud->tipo_negociacion_desc,
            'En revisión', $fud->id,
            $cliente->nombre_cliente, $cliente->operacion
        );

        return redirect()->route('fud.index')
            ->with('success', 'FUD enviado correctamente al aprobador.');
    }

    public function show(PropuestaFud $fud)
{
    // Verificar que el FUD pertenece al usuario actual
    // (o es aprobador que puede verlo)
    $user = auth()->user();

    if ($user->esGrupoFud() && $fud->enviado_por !== $user->id) {
        abort(403, 'No tienes permiso para ver esta propuesta.');
    }

    $fud->load('cliente','enviador','aprobador','detalleCuotas',
               'detalleCancelacion','detalleJudicial');

    return view('fud.show', compact('fud'));
}

    // API interna: buscar clientes (para AJAX en el formulario)
    public function buscarClientes(Request $request)
    {
        $clientes = ClienteCartera::disponibles()
            ->when($request->zona_id,    fn($q) => $q->where('zona_id', $request->zona_id))
            ->when($request->tienda_id,  fn($q) => $q->where('tienda_id', $request->tienda_id))
            ->when($request->estado,     fn($q) => $q->where('estado_cartera', $request->estado))
            ->when($request->cuenta,     fn($q) => $q->where('cuenta', 'like', "%{$request->cuenta}%"))
            ->when($request->operacion,  fn($q) => $q->where('operacion', 'like', "%{$request->operacion}%"))
            ->with('zona','tienda')
            ->limit(50)
            ->get();

        return response()->json($clientes);
    }
}