<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FudController;
use App\Http\Controllers\BandejaController;
use App\Http\Controllers\SustentosController;
use App\Http\Controllers\SeguimientoController;
use App\Http\Controllers\IndicadoresController;
use App\Http\Controllers\GobiernoImportacionController;
use App\Http\Controllers\GobiernoController;
use App\Http\Controllers\GobiernoRevisionController;
use App\Http\Controllers\AsistenteController;
use App\Http\Controllers\ReportesController;
use App\Http\Controllers\RevisionSustentosController;
use App\Http\Controllers\DashboardSustentosController;
use App\Http\Controllers\ValidacionController;
use App\Http\Controllers\AprobacionesController;
use App\Http\Controllers\WhatsappController;
use App\Http\Controllers\PortalController;
use App\Http\Controllers\NotificacionesController;

require __DIR__.'/auth.php';

// ── Canal WhatsApp (demostración, sin login; se habilita con RCT_WHATSAPP_DEMO) ──
Route::prefix('whatsapp')->name('whatsapp.')->middleware('throttle:90,1')->group(function () {
    Route::get('/',           [WhatsappController::class, 'index'])->name('index');
    Route::get('/historial',  [WhatsappController::class, 'historial'])->name('historial');
    Route::post('/mensaje',   [WhatsappController::class, 'mensaje'])->name('mensaje');
    Route::post('/reiniciar', [WhatsappController::class, 'reiniciar'])->name('reiniciar');
});

Route::middleware('auth')->group(function () {

    // Inicio (portal) y panel de indicadores
    Route::get('/', [PortalController::class, 'inicio'])->name('inicio');
    Route::get('/panel', [DashboardController::class, 'index'])->name('dashboard');

    // Notificaciones
    Route::get('/notificaciones', [NotificacionesController::class, 'index'])->name('notificaciones.index');
    Route::post('/notificaciones/leer', [NotificacionesController::class, 'leer'])->name('notificaciones.leer');
    Route::get('/notificaciones/recientes', [NotificacionesController::class, 'recientes'])->name('notificaciones.recientes');
    Route::post('/notificaciones/leer-todas', [NotificacionesController::class, 'leerTodas'])->name('notificaciones.leer-todas');

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

    // ── Analítica ────────────────────────────────────────────
    Route::get('/centro-control', [\App\Http\Controllers\CentroControlController::class, 'index'])
        ->middleware('grupo:aprobador')->name('centro-control');
    Route::get('/priorizacion', [\App\Http\Controllers\PriorizacionController::class, 'index'])
        ->middleware('grupo:aprobador')->name('priorizacion.index');
    Route::middleware('grupo:aprobador')->group(function () {
        Route::get('/alertas',                  [\App\Http\Controllers\AlertasController::class, 'index'])->name('alertas.index');
        Route::get('/alertas/{alerta}',         [\App\Http\Controllers\AlertasController::class, 'show'])->name('alertas.show');
        Route::post('/alertas/{alerta}/estado', [\App\Http\Controllers\AlertasController::class, 'estado'])->name('alertas.estado');
    });
    Route::get('/recaida', [\App\Http\Controllers\RecaidaController::class, 'index'])
        ->middleware('grupo:aprobador')->name('recaida.index');
    Route::get('/mi-prioridad', [\App\Http\Controllers\PriorizacionController::class, 'mia'])
        ->middleware('grupo:fud')->name('prioridad.mia');

    // ── Programas de gobierno · Impulso MyPerú ───────────────
    Route::post('/impulso/core/sincronizar', [\App\Http\Controllers\Impulso\CoreController::class, 'sincronizar'])->name('impulso.core.sincronizar');
    Route::get('/gobierno/revision', [\App\Http\Controllers\Impulso\RevisionController::class, 'index'])->name('gobierno.revision');
    Route::prefix('impulso')->name('impulso.')->group(function () {
        $L = \App\Http\Controllers\Impulso\LotesController::class;
        Route::get('/candidatos',                    [$L, 'candidatos'])->name('candidatos');
        Route::get('/lotes',                         [$L, 'index'])->name('lotes');
        Route::post('/lotes',                        [$L, 'armar'])->name('lotes.armar');
        Route::get('/lotes/{lote}',                  [$L, 'show'])->name('lotes.show');
        Route::post('/lotes/{lote}/documentos',      [$L, 'documentos'])->name('lotes.documentos');
        Route::get('/lotes/{lote}/descargar/{tipo}', [$L, 'descargar'])->name('lotes.descargar');
        Route::post('/lotes/{lote}/firma',           [$L, 'firma'])->name('lotes.firma');
        Route::post('/lotes/{lote}/confirmar',       [$L, 'confirmar'])->name('lotes.confirmar');
        $D = \App\Http\Controllers\Impulso\DevolucionesController::class;
        Route::get('/devoluciones',                        [$D, 'index'])->name('devoluciones');
        Route::post('/devoluciones',                       [$D, 'generar'])->name('devoluciones.generar');
        Route::get('/devoluciones/{devolucion}',           [$D, 'show'])->name('devoluciones.show');
        Route::post('/devoluciones/{devolucion}/cruzar',   [$D, 'cruzar'])->name('devoluciones.cruzar');
        Route::post('/devoluciones/{devolucion}/cerrar',   [$D, 'cerrar'])->name('devoluciones.cerrar');
        Route::get('/devoluciones/{devolucion}/txt-ejemplo', [$D, 'txtEjemplo'])->name('devoluciones.txt-ejemplo');
        Route::get('/devoluciones/{devolucion}/txt',       [$D, 'txt'])->name('devoluciones.txt');
        Route::get('/pagos',           [\App\Http\Controllers\Impulso\PagosController::class, 'index'])->name('pagos');
        Route::post('/pagos/simular',  [\App\Http\Controllers\Impulso\PagosController::class, 'simular'])->name('pagos.simular');
        Route::get('/matriz',          [\App\Http\Controllers\Impulso\BaseMatrizController::class, 'index'])->name('matriz');
        Route::get('/matriz/exportar', [\App\Http\Controllers\Impulso\BaseMatrizController::class, 'exportar'])->name('matriz.exportar');
        $H = \App\Http\Controllers\Impulso\HonramientoController::class;
        Route::post('/lotes/{lote}/honramiento',           [$H, 'previsualizar'])->name('honramiento.previsualizar');
        Route::post('/lotes/{lote}/honramiento/confirmar', [$H, 'confirmar'])->name('honramiento.confirmar');
        Route::get('/lotes/{lote}/honramiento/ejemplo',    [$H, 'ejemplo'])->name('honramiento.ejemplo');
        Route::post('/lotes/{lote}/honramiento/avisar',    [$H, 'avisar'])->name('honramiento.avisar');
    });

    // ── Programas de gobierno: expedientes de honramiento ────
    Route::get('/gobierno',                       [GobiernoController::class, 'index'])->name('gobierno.index');
    Route::get('/gobierno/exportar',                [GobiernoController::class, 'exportar'])->name('gobierno.exportar');
    Route::get('/gobierno/lotes',                   [GobiernoController::class, 'lotes'])->name('gobierno.lotes');
    Route::get('/gobierno/indicadores',             [\App\Http\Controllers\GobiernoIndicadoresController::class, 'index'])->name('gobierno.indicadores');
    Route::put('/gobierno/lotes/{lote}',            [GobiernoController::class, 'actualizarLote'])->name('gobierno.lotes.actualizar');
    Route::get('/gobierno/expediente/{expediente}', [GobiernoController::class, 'show'])->name('gobierno.show');
    Route::post('/gobierno/expediente/{expediente}/documento',      [GobiernoController::class, 'cargarDocumento'])->name('gobierno.documento');
    Route::post('/gobierno/expediente/{expediente}/no-disponible',  [GobiernoController::class, 'noDisponible'])->name('gobierno.no-disponible');
    Route::get('/gobierno/documento/{documento}/ver',               [GobiernoController::class, 'verDocumento'])->name('gobierno.documento.ver');
    Route::get('/gobierno/documento/{documento}/descargar',         [GobiernoController::class, 'descargarDocumento'])->name('gobierno.documento.descargar');
    Route::post('/gobierno/documento/{documento}/revision',         [GobiernoRevisionController::class, 'documento'])->name('gobierno.revision.documento');
    Route::post('/gobierno/expediente/{expediente}/revision',       [GobiernoRevisionController::class, 'expediente'])->name('gobierno.revision.expediente');
    Route::get('/cartera/exclusiones', fn() => redirect()->route('gobierno.index'))->name('cartera.exclusiones');   // ruta anterior

    // ── Trazabilidad completa de una operación (aprobadores y ejecutivo que la envió) ──
    Route::get('/expediente/{fud}/trazabilidad',     [AprobacionesController::class, 'show'])->name('aprobaciones.show');
    Route::get('/expediente/{fud}/trazabilidad/pdf', [AprobacionesController::class, 'pdf'])->name('aprobaciones.pdf');

    // ── Seguimiento ──────────────────────────────────────────
    Route::get('/seguimiento', [SeguimientoController::class, 'index'])->name('seguimiento.index');

    // ── Indicadores y cartera (aprobadores) ─────────────────
    Route::middleware('grupo:aprobador')->group(function () {
        Route::get('/indicadores',         [IndicadoresController::class, 'index'])->name('indicadores.index');
        // Programas de gobierno: importación del Excel de revisión (revisión central)
        Route::get('/gobierno/importar',            [GobiernoImportacionController::class, 'formulario'])->name('gobierno.importar');
        Route::post('/gobierno/importar',           [GobiernoImportacionController::class, 'previsualizar'])->name('gobierno.importar.previsualizar');
        Route::post('/gobierno/importar/confirmar', [GobiernoImportacionController::class, 'confirmar'])->name('gobierno.importar.confirmar');
        Route::get('/gobierno/importar/plantilla',  [GobiernoImportacionController::class, 'plantilla'])->name('gobierno.importar.plantilla');
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