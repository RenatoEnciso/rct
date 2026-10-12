<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FudController;
use App\Http\Controllers\BandejaController;
use App\Http\Controllers\SustentosController;
use App\Http\Controllers\SeguimientoController;
use App\Http\Controllers\IndicadoresController;
use App\Http\Controllers\CarteraController;
use App\Http\Controllers\AsistenteController;
use App\Http\Controllers\ReportesController;
use App\Http\Controllers\RevisionSustentosController;
use App\Http\Controllers\DashboardSustentosController;
use App\Http\Controllers\ValidacionController;
use App\Http\Controllers\AprobacionesController;
use App\Http\Controllers\WhatsappController;

require __DIR__.'/auth.php';

// ── Canal WhatsApp (demostración, sin login; se habilita con RCT_WHATSAPP_DEMO) ──
Route::prefix('whatsapp')->name('whatsapp.')->middleware('throttle:90,1')->group(function () {
    Route::get('/',           [WhatsappController::class, 'index'])->name('index');
    Route::get('/historial',  [WhatsappController::class, 'historial'])->name('historial');
    Route::post('/mensaje',   [WhatsappController::class, 'mensaje'])->name('mensaje');
    Route::post('/reiniciar', [WhatsappController::class, 'reiniciar'])->name('reiniciar');
});

Route::middleware('auth')->group(function () {

    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // ── FUD (grupo fud) ──────────────────────────────────────
    Route::middleware('grupo:fud')->group(function () {
        Route::get('/fud/crear',                 [FudController::class, 'create'])->name('fud.create');
        Route::post('/fud',                      [FudController::class, 'store'])->name('fud.store');
        Route::get('/fud/mis-propuestas',        [FudController::class, 'index'])->name('fud.index');
        Route::get('/fud/{fud}',                 [FudController::class, 'show'])->name('fud.show');
        Route::get('/fud/{fud}/corregir',        [FudController::class, 'edit'])->name('fud.edit');
        Route::put('/fud/{fud}/corregir',        [FudController::class, 'update'])->name('fud.update');
        Route::post('/fud/{fud}/adjuntos',       [FudController::class, 'subirAdjunto'])->name('fud.adjunto');
        Route::delete('/fud/adjuntos/{adjunto}', [FudController::class, 'eliminarAdjunto'])->name('fud.adjunto.destroy');
    });

    // ── Bandeja (aprobadores) ────────────────────────────────
    Route::middleware('grupo:aprobador')->group(function () {
        Route::get('/aprobaciones', [AprobacionesController::class, 'index'])->name('aprobaciones.index');
        Route::get('/bandeja',               [BandejaController::class, 'index'])->name('bandeja.index');
        Route::post('/bandeja/{fud}/decidir',[BandejaController::class, 'decidir'])->name('bandeja.decidir');
    });

    // ── Sustentos ────────────────────────────────────────────
    Route::get('/sustentos/documento/{documento}/descargar', function (\App\Models\SustentoDocumento $documento) {
        abort_unless(
            auth()->user()->grupo() === 'aprobador'
            || $documento->sustento->cargado_por === auth()->id(),
            403
        );
        return \Illuminate\Support\Facades\Storage::disk('private')->download(
            $documento->ruta_archivo,
            $documento->nombre_archivo
        );
    })->name('sustentos.documento.descargar');

    Route::get('/sustentos',                      [SustentosController::class, 'index'])->name('sustentos.index');
    Route::post('/sustentos/{sustento}',          [SustentosController::class, 'cargar'])->name('sustentos.cargar');
    Route::post('/sustentos/{sustento}/recargar', [SustentosController::class, 'recargar'])->name('sustentos.recargar');

    // ── Revisión (aprobadores) ───────────────────────────────
    Route::middleware('grupo:aprobador')->prefix('revision')->name('revision.')->group(function () {
        Route::get('/dashboard', [DashboardSustentosController::class, 'index'])->name('dashboard'); // ← nueva
        Route::get('/',                     [RevisionSustentosController::class, 'index'])->name('index');
        Route::get('/{sustento}',           [RevisionSustentosController::class, 'show'])->name('show');
        Route::post('/{sustento}/aprobar',  [RevisionSustentosController::class, 'aprobar'])->name('aprobar');
        Route::post('/{sustento}/observar', [RevisionSustentosController::class, 'observar'])->name('observar');
        Route::post('/{sustento}/rechazar', [RevisionSustentosController::class, 'rechazar'])->name('rechazar');
    });

    // ── Trazabilidad completa de una operación (aprobadores y ejecutivo que la envió) ──
    Route::get('/expediente/{fud}/trazabilidad',     [AprobacionesController::class, 'show'])->name('aprobaciones.show');
    Route::get('/expediente/{fud}/trazabilidad/pdf', [AprobacionesController::class, 'pdf'])->name('aprobaciones.pdf');

    // ── Seguimiento ──────────────────────────────────────────
    Route::get('/seguimiento', [SeguimientoController::class, 'index'])->name('seguimiento.index');

    // ── Indicadores y cartera (aprobadores) ─────────────────
    Route::middleware('grupo:aprobador')->group(function () {
        Route::get('/indicadores',         [IndicadoresController::class, 'index'])->name('indicadores.index');
        Route::get('/cartera/exclusiones', [CarteraController::class, 'exclusiones'])->name('cartera.exclusiones');
        Route::post('/cartera/gobierno',   [CarteraController::class, 'registrarGobierno'])->name('cartera.gobierno');
        Route::get('/cartera/gobierno/{expediente}',            [CarteraController::class, 'show'])->name('cartera.gobierno.show');
        Route::post('/cartera/gobierno/{expediente}/documento', [CarteraController::class, 'cargarDocumento'])->name('cartera.gobierno.documento');
        Route::post('/cartera/gobierno/documento/{documento}/conforme', [CarteraController::class, 'conformeManual'])->name('cartera.gobierno.conforme');
        Route::get('/cartera/gobierno/documento/{documento}/descargar', [CarteraController::class, 'descargar'])->name('cartera.gobierno.descargar');
        Route::get('/reportes/dashboard',  [ReportesController::class, 'dashboard'])->name('reportes.dashboard');
    });

    // ── API interna ──────────────────────────────────────────
    Route::get('/api/clientes', [FudController::class, 'buscarClientes'])->name('api.clientes');

    Route::get('/lineamientos', function () {
        return view('lineamientos');
    })->name('lineamientos');

    Route::get('fud/adjunto/{adjunto}/download', [FudController::class, 'download'])->name('fud.download');

    Route::post('/asistente/chat', [AsistenteController::class, 'chat'])->name('asistente.chat');

    // Validación automática de documentos antes de registrarlos
    Route::post('/validacion/documento', [ValidacionController::class, 'documento'])->name('validacion.documento');

    
});