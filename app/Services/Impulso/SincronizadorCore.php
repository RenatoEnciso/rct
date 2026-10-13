<?php
namespace App\Services\Impulso;

use App\Contracts\FuenteCore;
use App\Models\{PagoPrograma, SincronizacionCore};
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Sincronización con el core (cada 30 minutos): lee los créditos del programa y los pagos nuevos.
 * Idempotente: un pago (id_core) se procesa una sola vez.
 */
class SincronizadorCore
{
    public function __construct(private FuenteCore $core) {}

    public function ejecutar(string $origen = 'programado', ?int $userId = null, bool $notificar = true): SincronizacionCore
    {
        $reg = SincronizacionCore::create(['origen' => $origen, 'iniciado_en' => now(), 'user_id' => $userId]);
        try {
            $programa = config('rct.impulso.programa');
            $creditos = $this->core->creditosPrograma($programa)->keyBy('credito_sicmact');
            $reg->creditos_leidos = $creditos->count();

            // Relee un margen antes de la última sincronización: los pagos ya procesados se descartan por id_core
            $ultima = SincronizacionCore::where('id', '<', $reg->id)->where('estado', 'ok')->latest('iniciado_en')->first();
            $desde = ($ultima?->iniciado_en ?? now()->subDays(400))->copy()->subMinutes((int) config('rct.impulso.margen_minutos', 60));
            $leidos = $this->core->pagosDesde($desde);
            $reg->pagos_leidos = $leidos->count();

            $procesados = PagoPrograma::whereIn('id_core', $leidos->pluck('id_core'))->pluck('id_core')->all();
            $nuevos = self::filtrarNuevos($leidos, $procesados);
            $reg->pagos_nuevos = count($nuevos);

            DB::transaction(function () use ($nuevos, $creditos, $reg) {
                foreach ($nuevos as $p) {
                    if (!$creditos->has($p->credito_sicmact)) continue;   // no es del programa
                    PagoPrograma::create([
                        'id_core' => $p->id_core, 'credito_sicmact' => $p->credito_sicmact, 'fecha_pago' => $p->fecha_pago,
                        'monto_total' => $p->monto_total, 'capital' => $p->capital, 'interes' => $p->interes, 'mora' => $p->mora,
                        'tienda_id' => $p->tienda_id, 'canal' => $p->canal, 'estado' => 'Sincronizado',
                        'sincronizado_en' => now(), 'sincronizacion_id' => $reg->id,
                    ]);
                    $reg->pagos_programa++;
                }
            });

            $reg->fill(['finalizado_en' => now(), 'estado' => 'ok'])->save();
        } catch (\Throwable $e) {
            $reg->fill(['finalizado_en' => now(), 'estado' => 'error', 'mensaje' => $e->getMessage()])->save();
            throw $e;
        }
        return $reg;
    }

    /**
     * Pagos que aún no se procesaron (idempotencia por id_core), sin repetidos dentro del mismo lote leído.
     * @param iterable $pagos objetos con id_core
     * @param string[] $idsProcesados
     */
    public static function filtrarNuevos(iterable $pagos, array $idsProcesados): array
    {
        $vistos = array_flip($idsProcesados);
        $nuevos = [];
        foreach ($pagos as $p) {
            if (isset($vistos[$p->id_core])) continue;
            $vistos[$p->id_core] = true;
            $nuevos[] = $p;
        }
        return $nuevos;
    }

    /** Texto para pantalla: "hace 12 min · 12/10/2026 14:30". */
    public static function textoUltima(): ?string
    {
        $u = SincronizacionCore::ultima();
        if (!$u) return null;
        $min = (int) $u->finalizado_en->diffInMinutes(now());
        $hace = $min < 1 ? 'hace instantes' : ($min < 60 ? "hace {$min} min" : $u->finalizado_en->diffForHumans());
        return $hace.' · '.$u->finalizado_en->format('d/m/Y H:i');
    }
}
