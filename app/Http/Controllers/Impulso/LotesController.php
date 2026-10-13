<?php
namespace App\Http\Controllers\Impulso;

use App\Http\Controllers\Controller;
use App\Models\{CoreCredito, Lote};
use App\Services\Impulso\{DocumentosLote, Lotes};
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/** Etapa 1 — Candidatos a honramiento y lotes de solicitud ante COFIDE. */
class LotesController extends Controller
{
    public function __construct(private Lotes $lotes) {}

    public function candidatos(Request $request)
    {
        $this->autorizar($request);
        return view('impulso.candidatos', [
            'candidatos' => $this->lotes->candidatos()->load('zona', 'tienda'),
            'proximos'   => $this->lotes->proximos()->load('tienda'),
            'dias'       => (int) config('rct.impulso.dias_candidato', 90),
        ]);
    }

    public function armar(Request $request)
    {
        $this->autorizar($request);
        $datos = $request->validate(['creditos' => 'required|array|min:1', 'creditos.*' => 'string'],
            ['creditos.required' => 'Seleccione al menos un crédito para armar el lote.']);
        try {
            $lote = $this->lotes->armar($datos['creditos'], $request->user());
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
        return redirect()->route('impulso.lotes.show', $lote)->with('success', "{$lote->nombre()} armado con {$lote->registros_rct} créditos.");
    }

    public function index(Request $request)
    {
        $this->autorizar($request);
        $lotes = Lote::withCount('expedientes')->orderByDesc('fecha_corte')->orderByDesc('codigo')->get();
        return view('impulso.lotes', ['lotes' => $lotes, 'candidatos' => $this->lotes->candidatos()->count()]);
    }

    public function show(Request $request, Lote $lote)
    {
        $this->autorizar($request);
        $exp = $lote->expedientes()->with('zona', 'tienda')->orderBy('cliente')->get();
        return view('impulso.lote', [
            'lote' => $lote, 'expedientes' => $exp,
            'core' => CoreCredito::whereIn('credito_sicmact', $exp->pluck('credito_sicmact'))->get()->keyBy('credito_sicmact'),
        ]);
    }

    public function documentos(Request $request, Lote $lote, DocumentosLote $docs)
    {
        $this->autorizar($request);
        $docs->word($lote);
        $docs->excel($lote);
        $this->lotes->marcarDocumentos($lote);
        return back()->with('success', 'Se generaron la notificación (Word) y la plantilla de honramiento (Excel).');
    }

    public function descargar(Request $request, Lote $lote, string $tipo, DocumentosLote $docs)
    {
        $this->autorizar($request);
        abort_unless(in_array($tipo, ['word', 'excel']), 404);
        $ruta = $tipo === 'word' ? $docs->word($lote) : $docs->excel($lote);   // se regenera con las firmas vigentes
        return response()->download($ruta, basename($ruta));
    }

    public function firma(Request $request, Lote $lote)
    {
        $this->autorizar($request);
        try {
            $this->lotes->autorizarFirma($lote, $request->integer('representante'), $request->user());
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
        return back()->with('success', $lote->estado === 'Firmado' ? 'Firma digital autorizada por todos los representantes: el lote está firmado.' : 'Autorización de firma registrada.');
    }

    public function confirmar(Request $request, Lote $lote)
    {
        $this->autorizar($request);
        $d = $request->validate([
            'registros_cofide' => 'required|integer|min:0',
            'saldo_cofide'     => 'required|string',
            'fecha_carga'      => 'required|date|before_or_equal:today',
            'constancia'       => 'nullable|string|max:60',
        ]);
        $saldo = \App\Services\Honramiento\FormatoExcel::monto($d['saldo_cofide']);
        if ($saldo === null) return back()->withInput()->withErrors(['saldo_cofide' => 'Ingrese el saldo insoluto confirmado por COFIDE.']);
        try {
            $ok = $this->lotes->confirmar($lote, (int) $d['registros_cofide'], number_format($saldo, 2, '.', ''),
                Carbon::parse($d['fecha_carga']), $d['constancia'] ?? null, $request->user());
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
        return back()->with($ok ? 'success' : 'warning', $ok
            ? 'COFIDE confirmó el lote: el N° de registros y el saldo insoluto coinciden con RCT.'
            : 'La confirmación de COFIDE no coincide con lo calculado por RCT. El lote quedó observado.');
    }

    private function autorizar(Request $request): void
    {
        abort_unless(CoreController::esCentral($request->user()), 403, 'Los lotes los gestiona la revisión central (Jefatura de Recuperaciones).');
    }
}
