<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\{ClienteCartera, ExpedienteGobierno, ExpedienteGobiernoDoc};

class CarteraController extends Controller
{
    public function exclusiones()
    {
        $expedientes = ExpedienteGobierno::with('cliente.tienda', 'registrador')
            ->withCount('documentos')
            ->latest('fecha_registro')->latest('id')
            ->paginate(10);

        return view('cartera.exclusiones', compact('expedientes'));
    }

    public function registrarGobierno(Request $request)
    {
        $request->validate([
            'cliente_ref'         => 'required|string|max:30',
            'programa'            => 'required|in:IMPULSA PERÚ,FAE-MYPE,Reactiva Perú,Otro',
            'motivo_habilitacion' => 'required|in:Garantía rechazada por Cía. Seguros,Pérdida de eficacia de la garantía',
            'responsable'         => 'required|string|max:120',
            'documentos'          => 'required|array|size:10',
            'documentos.*'        => 'required|file|max:10240',
        ], [
            'documentos.size'     => 'Debe adjuntar los 10 documentos obligatorios.',
        ]);

        // El usuario conoce la cuenta o la operación, no el ID interno
        $ref     = trim($request->cliente_ref);
        $cliente = ClienteCartera::where('cuenta', $ref)->orWhere('operacion', $ref)->first();
        if (!$cliente) {
            return back()->withInput()
                ->withErrors(['cliente_ref' => "No se encontró un crédito con cuenta u operación {$ref}."]);
        }

        $expediente = ExpedienteGobierno::create([
            'cliente_cartera_id'  => $cliente->id,
            'registrado_por'      => $request->user()->id,
            'programa'            => $request->programa,
            'motivo_habilitacion' => $request->motivo_habilitacion,
            'confirmacion_dpto'   => $request->boolean('confirmacion_dpto'),
            'responsable'         => $request->responsable,
            'fecha_registro'      => now()->toDateString(),
            'estado'              => 'Completo',
        ]);

        $tipos = [
            'DNI / Reporte tributario con Ficha RUC',
            'Hoja Resumen', 'Formulario Conocimiento del Cliente',
            'DJ del Programa – Anexo 02', 'DJ Patrimonial',
            'Cargo del Contrato de Crédito', 'Cronograma de Pagos',
            'Pagaré', 'Clasificación Regulatoria (RCC, EXPERIAN)',
            'Evidencia de Ingresos',
        ];

        foreach ($request->file('documentos') as $i => $archivo) {
            $path = $archivo->store('gobierno', 'private');
            ExpedienteGobiernoDoc::create([
                'expediente_id'  => $expediente->id,
                'tipo_doc'       => $tipos[$i],
                'nombre_archivo' => $archivo->getClientOriginalName(),
                'ruta_archivo'   => $path,
            ]);
        }

        return redirect()->route('cartera.exclusiones')
            ->with('success', 'Expediente registrado con los 10 documentos.');
    }
}