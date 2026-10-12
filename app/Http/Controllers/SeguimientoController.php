<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Seguimiento;

class SeguimientoController extends Controller
{
    public function index(Request $request)
    {
        $base = Seguimiento::query()
            ->when(!$request->user()->esAprobador(), fn($q) =>
                $q->where('user_id', $request->user()->id)
            );

        $tipos = (clone $base)->distinct()->orderBy('tipo_operacion')->pluck('tipo_operacion');

        $registros = (clone $base)->with('user.rol')
            ->when($request->q, fn($q, $v) => $q->where(fn($w) =>
                $w->where('cliente', 'like', "%$v%")->orWhere('operacion', 'like', "%$v%")))
            ->when($request->tipo, fn($q, $v) => $q->where('tipo_operacion', $v))
            ->when($request->desde, fn($q, $v) => $q->whereDate('fecha_accion', '>=', $v))
            ->when($request->hasta, fn($q, $v) => $q->whereDate('fecha_accion', '<=', $v))
            ->orderByDesc('fecha_accion')->orderByDesc('created_at')->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('seguimiento.index', compact('registros', 'tipos'));
    }
}
