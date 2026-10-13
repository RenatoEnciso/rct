<?php
namespace App\Http\Controllers\Impulso;

use App\Http\Controllers\Controller;
use App\Models\{Lote, PagoPrograma, SincronizacionCore, Zona};
use App\Services\Impulso\Calculos;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Etapa 5 — Pagos sincronizados desde el core (solo lectura: no hay registro manual de pagos).
 */
class PagosController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(CoreController::esCentral($request->user()), 403);
        $mes = match (true) {
            preg_match('/^\d{4}-\d{2}$/', (string) $request->mes) === 1 => $request->mes,
            $request->mes === 'todos' || filled($request->q) => null,
            default => now()->format('Y-m'),
        };

        $q = PagoPrograma::with('expediente.lote', 'expediente.zona', 'tienda')
            ->when($mes, fn($q) => $q->whereBetween('fecha_pago', [Carbon::parse($mes.'-01')->startOfMonth(), Carbon::parse($mes.'-01')->endOfMonth()]))
            ->when($request->estado, fn($q, $v) => $q->where('estado', $v))
            ->when($request->canal, fn($q, $v) => $q->where('canal', $v))
            ->when($request->lote_id, fn($q, $v) => $q->whereHas('expediente', fn($e) => $e->where('lote_id', $v)))
            ->when($request->zona_id, fn($q, $v) => $q->whereHas('expediente', fn($e) => $e->where('zona_id', $v)))
            ->when($request->q, fn($q, $v) => $q->where(fn($w) => $w->where('credito_sicmact', 'like', "%$v%")
                ->orWhereHas('expediente', fn($e) => $e->where('cliente', 'like', "%$v%")->orWhere('dni', 'like', "%$v%"))));

        $todos = (clone $q)->get(['capital', 'reversion', 'estado', 'monto_total']);
        $meses = PagoPrograma::selectRaw("DATE_FORMAT(fecha_pago, '%Y-%m') m")->distinct()->orderByDesc('m')->pluck('m');

        return view('impulso.pagos', [
            'pagos'   => (clone $q)->orderByDesc('fecha_pago')->paginate(25)->withQueryString(),
            'kpis'    => [
                'pagos'      => $todos->count(),
                'monto'      => Calculos::sumar($todos->pluck('monto_total')),
                'capital'    => Calculos::sumar($todos->pluck('capital')),
                'reversion'  => Calculos::sumar($todos->whereIn('estado', ['Agregado', 'Incluido en devolución', 'Devuelto'])->pluck('reversion')),
                'sin'        => $todos->where('estado', 'Sin reversión')->count(),
            ],
            'mes'     => $mes,
            'meses'   => $meses->isEmpty() ? collect([now()->format('Y-m')]) : $meses,
            'canales' => PagoPrograma::whereNotNull('canal')->distinct()->orderBy('canal')->pluck('canal'),
            'lotes'   => Lote::whereIn('estado', ['Honrado', 'Honrado parcial', 'Cargado en COFIDE'])->orderBy('codigo')->get(),
            'zonas'   => Zona::whereIn('id', \App\Models\ExpedienteGobierno::distinct()->pluck('zona_id'))->orderBy('nombre')->get(),
            'ultima'  => SincronizacionCore::ultima(),
        ]);
    }

    /** Demo: inserta un pago en el core y sincroniza; el aviso llega a la pantalla sin recargar. */
    public function simular(Request $request, \App\Services\Impulso\SimuladorCore $simulador)
    {
        abort_unless(self::esAdministrador($request->user()), 403);
        $r = $simulador->simularPago(null, null, $request->boolean('honrado', true), $request->user()->id);
        $p = PagoPrograma::with('expediente')->where('id_core', $r['pago']->id_core)->first();
        return response()->json(['ok' => true, 'mensaje' => 'Pago '.$r['pago']->id_core.' de '.($p?->expediente?->cliente ?? $r['pago']->credito_sicmact)
            .' sincronizado desde el core: '.$p?->estado.'.']);
    }

    /** Administrador de la demo (config rct.impulso.administradores): puede simular pagos del core. */
    public static function esAdministrador($u): bool
    {
        return $u && in_array($u->email, (array) config('rct.impulso.administradores', []), true);
    }
}
