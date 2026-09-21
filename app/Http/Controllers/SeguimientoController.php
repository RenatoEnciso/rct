<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Seguimiento;

class SeguimientoController extends Controller
{
    public function index(Request $request)
    {
        $registros = Seguimiento::with('user', 'propuesta.cliente')
            ->when(!$request->user()->esAprobador(), fn($q) =>
                $q->where('user_id', $request->user()->id)
            )
            ->latest('fecha_accion')
            ->paginate(20);

        return view('seguimiento.index', compact('registros'));
    }
}