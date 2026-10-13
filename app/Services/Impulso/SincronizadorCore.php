<?php
namespace App\Services\Impulso;

use App\Contracts\FuenteCore;
use App\Models\{ExpedienteGobierno, PagoPrograma, Seguimiento, SincronizacionCore};
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Sincronización con el core (cada 30 minutos): lee los créditos del programa y los pagos nuevos.
 * - Detecta los créditos que llegan a 90 días (candidatos a honramiento).
 * - Cada pago del programa se registra con su reversión: capital × factor de cobertura si el crédito
 *   estaba honrado a la fecha del pago (queda AGREGADO); si no, reversión 0 con el motivo.
 * Idempotente: un pago (id_core) se procesa una sola vez.
 */
class SincronizadorCore
{
    /** @var PagoPrograma[] pagos procesados en la última ejecución (para los avisos) */
    public array $procesados = [];

    /** Registrar cada pago en la bitácora (el seeder de la demo lo desactiva y registra con la fecha real). */
    public bool $registrarBitacora = true;

    public function __construct(private FuenteCore $core, private Lotes $lotes, private Avisos $avisos) {}

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

            $this->procesados = [];
            DB::transaction(function () use ($nuevos, $creditos, $reg) {
                $expedientes = ExpedienteGobierno::whereIn('credito_sicmact', collect($nuevos)->pluck('credito_sicmact'))->get()->keyBy('credito_sicmact');
                foreach ($nuevos as $p) {
                    if (!$creditos->has($p->credito_sicmact)) continue;   // no es del programa
                    $pago = $this->procesarPago($p, $expedientes->get($p->credito_sicmact), $reg);
                    $pago->estado === 'Agregado' ? $reg->agregados++ : $reg->sin_reversion++;
                    $reg->pagos_programa++;
                    $this->procesados[] = $pago;
                }
            });

            $candidatos = $this->lotes->detectarCandidatos();
            $reg->candidatos_nuevos = $candidatos->count();
            $reg->fill(['finalizado_en' => now(), 'estado' => 'ok'])->save();

            if ($notificar) {
                $this->avisos->candidatos($candidatos->map(fn($c) => (object) ['credito_sicmact' => $c->credito_sicmact, 'dias_atraso' => $c->dias_atraso]));
                $candidatos->each->update(['notificado_en' => now()]);
                foreach ($this->procesados as $pago) $this->avisos->pagoSincronizado($pago);
            }
        } catch (\Throwable $e) {
            $reg->fill(['finalizado_en' => now(), 'estado' => 'error', 'mensaje' => $e->getMessage()])->save();
            throw $e;
        }
        return $reg;
    }

    /** Registra un pago del core con su reversión (regla del honramiento a la fecha del pago). */
    public function procesarPago(object $p, ?ExpedienteGobierno $e, SincronizacionCore $reg): PagoPrograma
    {
        $fecha = \Illuminate\Support\Carbon::parse($p->fecha_pago);
        $honrado = $e && $e->estado_honramiento === 'Honrado' && Calculos::honradoALaFecha($e->fecha_honramiento, $fecha);
        $motivo = match (true) {
            $honrado => null,
            !$e => 'Crédito no solicitado al programa a la fecha del pago',
            $e->estado_honramiento === 'No honrado' => 'Crédito no honrado por COFIDE',
            default => 'Crédito no honrado a la fecha del pago',
        };
        $pago = PagoPrograma::create([
            'id_core' => $p->id_core, 'credito_sicmact' => $p->credito_sicmact, 'expediente_id' => $e?->id, 'fecha_pago' => $fecha,
            'monto_total' => $p->monto_total, 'capital' => $p->capital, 'interes' => $p->interes, 'mora' => $p->mora,
            'tienda_id' => $p->tienda_id, 'canal' => $p->canal, 'honrado' => $honrado,
            'factor_cobertura' => $honrado ? $e->factor_cobertura : null,
            'reversion' => $honrado ? Calculos::reversion($p->capital, $e->factor_cobertura) : '0.00',
            'estado' => $honrado ? 'Agregado' : 'Sin reversión', 'motivo' => $motivo, 'agregado_en' => $honrado ? now() : null,
            'sincronizado_en' => now(), 'sincronizacion_id' => $reg->id,
        ]);
        if ($this->registrarBitacora) Seguimiento::registrar($reg->user_id ?? $this->usuarioSistema(), 'Pago sincronizado', $pago->estado, null, $e?->cliente, $p->credito_sicmact,
            'Pago sincronizado desde core el '.now()->format('d/m/Y H:i').': capital '.\App\Support\Estilo::solesPe($p->capital)
            .($honrado ? ' → reversión COFIDE '.\App\Support\Estilo::solesPe($pago->reversion).' (agregado).' : ' → sin reversión: '.mb_strtolower($motivo).'.'));
        return $pago;
    }

    /** La bitácora exige un usuario: los procesos automáticos se registran a nombre del revisor de programas. */
    private function usuarioSistema(): int
    {
        static $id = null;
        return $id ??= (int) (\App\Models\User::whereIn('email', (array) config('rct.impulso.revisores'))->value('id')
            ?? \App\Models\User::value('id'));
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
