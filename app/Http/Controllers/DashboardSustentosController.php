<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Sustento;
use App\Models\SustentoDocumento;
use Illuminate\Support\Facades\DB;

class DashboardSustentosController extends Controller
{
    public function index()
    {
        $conteos = Sustento::selectRaw('estado, count(*) as total')
            ->groupBy('estado')
            ->pluck('total', 'estado');

        $total      = $conteos->sum();
        $aprobados  = $conteos['Aprobado']    ?? 0;
        $enRevision = $conteos['En revisión'] ?? 0;
        $observados = $conteos['Observado']   ?? 0;
        $pendientes = $conteos['Pendiente']   ?? 0;
        $rechazados = $conteos['Rechazado']   ?? 0;
        $pct        = $total > 0 ? round($aprobados / $total * 100, 2) : 0;

        $docsFaltantes = SustentoDocumento::select('tipo_doc',
                DB::raw('count(*) as total'))
            ->where('estado_revision', 'pendiente')
            ->groupBy('tipo_doc')
            ->orderByDesc('total')
            ->get();

        $tendencia = Sustento::selectRaw(
                "DATE_FORMAT(fecha_carga, '%Y-%m') as mes,
                 estado,
                 count(*) as total")
            ->whereIn('estado', ['Aprobado', 'Observado'])
            ->where('fecha_carga', '>=', now()->subMonths(6)->startOfMonth())
            ->groupBy('mes', 'estado')
            ->orderBy('mes')
            ->get()
            ->groupBy('mes')
            // "2026-09" → "Sep 2026" para el eje del gráfico
            ->mapWithKeys(fn($g, $mes) => [
                ucfirst(\Carbon\Carbon::createFromFormat('Y-m', $mes)->locale('es')->translatedFormat('M Y')) => $g,
            ]);

        // Top analistas con más cargas
        $topAnalistas = Sustento::select('cargado_por',
                DB::raw('count(*) as total'),
                DB::raw('sum(case when estado="Aprobado" then 1 else 0 end) as aprobados'))
            ->with('cargador:id,name')
            ->groupBy('cargado_por')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        // Distribución por estado para el donut
        $distribucion = [
            'labels' => ['Aprobado','En revisión','Observado','Pendiente','Rechazado'],
            'data'   => [
                $aprobados, $enRevision, $observados, $pendientes, $rechazados
            ],
            'colors' => ['#1f7a4d','#1f4e8c','#d08a1e','#9aa3ae','#a3182c'],
        ];

        return view('revision.dashboard', compact(
            'total','aprobados','enRevision','observados','pendientes','rechazados','pct',
            'docsFaltantes','tendencia','topAnalistas','distribucion'
        ));
    }
}