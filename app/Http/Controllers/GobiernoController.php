<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\{ExpedienteGobierno, ExpedienteGobiernoDoc, Gestion, GobTipoDocumento, Lote, Seguimiento, Tienda, Zona};
use App\Services\Honramiento\{Checklist, Permisos};
use App\Services\Validacion\CargaDocumentos;

/** Bandeja y ficha de expedientes de honramiento de Programas de Gobierno. */
class GobiernoController extends Controller
{
    public function __construct(private Permisos $permisos, private Checklist $checklist) {}

    public function index(Request $request)
    {
        $u = $request->user();
        $base = fn() => ExpedienteGobierno::visiblePara($u);

        $q = $base()->with(['gestion', 'lote', 'zona', 'tienda', 'documentos'])
            ->when($request->gestion_id, fn($q, $v) => $q->where('gestion_id', $v))
            ->when($request->zona_id,    fn($q, $v) => $q->where('zona_id', $v))
            ->when($request->tienda_id,  fn($q, $v) => $q->where('tienda_id', $v))
            ->when($request->lote_id,    fn($q, $v) => $q->where('lote_id', $v))
            ->when($request->estado,     fn($q, $v) => $q->where('estado', $v))
            ->when($request->tipo_persona, fn($q, $v) => $q->where('tipo_persona', $v))
            ->when($request->desde, fn($q, $v) => $q->whereRaw('DATE(COALESCE(revisado_central_en, revisado_zona_en)) >= ?', [$v]))
            ->when($request->hasta, fn($q, $v) => $q->whereRaw('DATE(COALESCE(revisado_central_en, revisado_zona_en)) <= ?', [$v]))
            ->when($request->q, fn($q, $v) => $q->where(fn($w) => $w->where('dni', 'like', "%$v%")->orWhere('cliente', 'like', "%$v%")
                ->orWhere('credito_sicmact', 'like', "%$v%")->orWhere('operacion', 'like', "%$v%")->orWhere('id_externo', $v)));

        if ($request->plazo) {
            $lotes = Lote::whereNotNull('fecha_conformidad')->get()->filter(function ($l) use ($request) {
                $d = $l->diasRestantes();
                return match ($request->plazo) { 'vencido' => $d < 0, 'por_vencer' => $d >= 0 && $d <= 7, default => $d > 7 };
            })->pluck('id');
            $q->whereIn('lote_id', $lotes)->whereNotIn('estado', ['Completo', 'Revisado por zona', 'Conforme']);
        }

        $expedientes = $q->orderByRaw("FIELD(estado, 'Observado', 'Completo', 'Revisado por zona', 'Incompleto', 'Pendiente', 'Incompleto definitivo', 'Conforme')")
            ->orderByDesc('id')->paginate(25)->withQueryString();

        $conteo = $base()->selectRaw('estado, count(*) c')->groupBy('estado')->pluck('c', 'estado');
        $zonasVisibles = $base()->distinct()->pluck('zona_id');

        return view('gobierno.index', [
            'expedientes' => $expedientes,
            'conteo'      => $conteo,
            'gestiones'   => Gestion::orderBy('nombre')->get(),
            'zonas'       => Zona::whereIn('id', $zonasVisibles)->orderBy('nombre')->get(),
            'tiendas'     => Tienda::whereIn('id', $base()->distinct()->pluck('tienda_id'))
                                ->when($request->zona_id, fn($q, $v) => $q->where('zona_id', $v))->orderBy('nombre')->get(),
            'lotes'       => Lote::orderBy('codigo')->get(),
            'checklist'   => $this->checklist,
            'esCentral'   => $this->permisos->puedeRevisarCentral($u),
        ]);
    }

    public function show(Request $request, ExpedienteGobierno $expediente)
    {
        abort_unless($this->permisos->puedeVer($request->user(), $expediente), 403);
        $expediente->load('gestion', 'lote', 'zona', 'tienda', 'registrador', 'documentos.cargador', 'documentos.revisor',
                          'revisiones.usuario.rol', 'historialDocumentos.tipo');

        $items = $this->checklist->documentos($expediente);

        return view('gobierno.show', [
            'e'              => $expediente,
            'exigidos'       => $items->where('exigido', true),
            'complementarios'=> $items->where('exigido', false),
            'observacionesAuto' => $this->checklist->observaciones($expediente),
            'puedeCargar'    => $this->permisos->puedeCargar($request->user(), $expediente),
            'nivelRevision'  => $this->permisos->nivelRevision($request->user(), $expediente),
            'puedeRevisarDocs' => $this->permisos->puedeRevisarZona($request->user(), $expediente),
        ]);
    }

    // Carga o reemplazo de un documento (PDF, validación automática). El anterior queda como historial.
    public function cargarDocumento(Request $request, ExpedienteGobierno $expediente, CargaDocumentos $carga)
    {
        abort_unless($this->permisos->puedeCargar($request->user(), $expediente), 403);
        $request->validate([
            'tipo_documento_id' => 'required|exists:gob_tipos_documento,id',
            'archivo'           => 'required|file|mimes:pdf|max:10240',
        ], [
            'archivo.required' => 'Seleccione el archivo.',
            'archivo.mimes'    => 'Los documentos de honramiento deben cargarse en PDF, un documento por archivo.',
            'archivo.max'      => 'El archivo puede pesar como máximo 10 MB.',
        ]);

        $tipo = GobTipoDocumento::findOrFail($request->tipo_documento_id);
        $campos = $carga->guardar($request->file('archivo'), $tipo->nombre, $carga->contextoGobierno($expediente), "gobierno/{$expediente->id}");

        $expediente->documentos()->where('tipo_documento_id', $tipo->id)->update(['vigente' => false]);
        ExpedienteGobiernoDoc::create($campos + [
            'expediente_id' => $expediente->id, 'tipo_documento_id' => $tipo->id, 'tipo_doc' => $tipo->nombre,
            'estado' => 'cargado', 'origen' => 'rct', 'cargado_por' => $request->user()->id, 'cargado_en' => now(),
        ]);

        // Un documento nuevo deja sin efecto la conformidad anterior: requiere nueva revisión
        $expediente->forceFill(['revisado_zona_en' => null, 'revisado_central_en' => null])->saveQuietly();
        $expediente->recalcularEstado();
        $this->bitacora($request, $expediente, 'Documento cargado', $tipo->nombre_corto);

        return back()->with($campos['estado_validacion'] === 'conforme' ? 'success' : 'warning',
            $campos['estado_validacion'] === 'conforme'
                ? "«{$tipo->nombre_corto}» cargado. Validación automática conforme."
                : "«{$tipo->nombre_corto}» cargado con observaciones de la validación automática.");
    }

    // La tienda declara que no cuenta con el documento tras la búsqueda → Incompleto definitivo
    public function noDisponible(Request $request, ExpedienteGobierno $expediente)
    {
        abort_unless($this->permisos->puedeCargar($request->user(), $expediente), 403);
        $request->validate([
            'tipo_documento_id' => 'required|exists:gob_tipos_documento,id',
            'motivo'            => 'required|string|min:10|max:500',
        ], ['motivo.required' => 'Indique las búsquedas realizadas y por qué no se cuenta con el documento.', 'motivo.min' => 'Detalle el motivo (mínimo 10 caracteres).']);

        $tipo = GobTipoDocumento::findOrFail($request->tipo_documento_id);
        $expediente->documentos()->where('tipo_documento_id', $tipo->id)->update(['vigente' => false]);
        ExpedienteGobiernoDoc::create([
            'expediente_id' => $expediente->id, 'tipo_documento_id' => $tipo->id, 'tipo_doc' => $tipo->nombre,
            'estado' => 'no_disponible', 'origen' => 'rct', 'cargado_por' => $request->user()->id, 'cargado_en' => now(),
            'motivo_no_disponible' => $request->motivo,
        ]);
        $expediente->recalcularEstado();
        $this->bitacora($request, $expediente, 'Documento declarado no disponible', $tipo->nombre_corto.': '.$request->motivo);

        return back()->with('warning', "«{$tipo->nombre_corto}» declarado como no disponible.");
    }

    public function verDocumento(Request $request, ExpedienteGobiernoDoc $documento)
    {
        abort_unless($documento->ruta_archivo && $this->permisos->puedeVer($request->user(), $documento->expediente), 404);
        // Se muestra en el navegador (inline)
        return Storage::disk('private')->response($documento->ruta_archivo, $documento->nombre_archivo);
    }

    public function descargarDocumento(Request $request, ExpedienteGobiernoDoc $documento)
    {
        abort_unless($documento->ruta_archivo && $this->permisos->puedeVer($request->user(), $documento->expediente), 404);
        return Storage::disk('private')->download($documento->ruta_archivo, $documento->nombre_archivo);
    }

    // ── Lotes (fecha de conformidad → plazo de 30 días) ──────

    public function lotes(Request $request)
    {
        $lotes = Lote::withCount(['expedientes',
            'expedientes as pendientes_count' => fn($q) => $q->whereNotIn('estado', ['Completo', 'Revisado por zona', 'Conforme'])])
            ->orderByDesc('codigo')->get();
        return view('gobierno.lotes', ['lotes' => $lotes, 'puedeEditar' => $this->permisos->puedeRevisarCentral($request->user())]);
    }

    public function actualizarLote(Request $request, Lote $lote)
    {
        abort_unless($this->permisos->puedeRevisarCentral($request->user()), 403);
        $datos = $request->validate([
            'programa'          => 'nullable|string|max:60',
            'fecha_solicitud'   => 'nullable|date',
            'fecha_conformidad' => 'nullable|date|after_or_equal:fecha_solicitud',
        ], ['fecha_conformidad.after_or_equal' => 'La conformidad no puede ser anterior a la solicitud.']);
        $lote->update($datos);
        return back()->with('success', "Lote «{$lote->codigo}» actualizado.");
    }

    private function bitacora(Request $request, ExpedienteGobierno $e, string $accion, string $detalle): void
    {
        Seguimiento::registrar($request->user()->id, $accion, $e->estado, null, $e->cliente, $e->id_externo, "Expediente de honramiento {$e->id_externo}. {$detalle}");
    }
}
