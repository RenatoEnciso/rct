<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\{ClienteCartera, ExpedienteGobierno, ExpedienteGobiernoDoc};

class CarteraController extends Controller
{
    public function exclusiones()
    {
        return view('cartera.exclusiones');
    }

    public function registrarGobierno(Request $request)
    {
        $request->validate([
            'cliente_cartera_id'  => 'required|exists:clientes_cartera,id',
            'programa'            => 'required|string',
            'motivo_habilitacion' => 'required|string',
            'responsable'         => 'required|string',
            'documentos'          => 'required|array|size:10',
            'documentos.*'        => 'required|file|max:10240',
        ]);

        $expediente = ExpedienteGobierno::create([
            'cliente_cartera_id'  => $request->cliente_cartera_id,
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