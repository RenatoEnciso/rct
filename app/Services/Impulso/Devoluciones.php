<?php
namespace App\Services\Impulso;

use App\Models\{DevolucionCofide, DevolucionDetalle, PagoPrograma, Seguimiento, User};
use App\Support\Estilo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\{DB, Storage};

/**
 * Etapa 6 — Devolución a COFIDE (inicio del mes siguiente):
 * devolución del mes = Σ reversiones AGREGADAS del mes que no se devolvieron antes;
 * se cruza crédito por crédito con el TXT de COFIDE y solo se cierra con diferencia S/ 0,00
 * (o con justificación del Jefe de Recuperaciones).
 */
class Devoluciones
{
    public function __construct(private Avisos $avisos) {}

    /** Reversiones agregadas del mes aún no incluidas en una devolución, agrupadas por crédito. */
    public function pendientesDelMes(Carbon $periodo): Collection
    {
        return PagoPrograma::with('expediente.lote')->where('estado', 'Agregado')->whereNull('devolucion_id')
            ->whereBetween('fecha_pago', [$periodo->copy()->startOfMonth(), $periodo->copy()->endOfMonth()])
            ->orderBy('fecha_pago')->get()->groupBy('credito_sicmact');
    }

    public function fechaLimite(Carbon $periodo): Carbon
    {
        return $periodo->copy()->startOfMonth()->addMonth()->day((int) config('rct.impulso.dia_limite_devolucion', 15));
    }

    /** Calcula (o recalcula, si no está cerrada) la devolución del mes. */
    public function generar(Carbon $periodo, User $usuario): DevolucionCofide
    {
        $periodo = $periodo->copy()->startOfMonth();
        return DB::transaction(function () use ($periodo, $usuario) {
            $dev = DevolucionCofide::firstOrCreate(['periodo' => $periodo->toDateString()], ['fecha_limite' => $this->fechaLimite($periodo), 'estado' => 'Calculada']);
            throw_if($dev->cerrada(), new \DomainException('La devolución de '.$dev->nombrePeriodo().' ya fue devuelta.'));

            foreach ($this->pendientesDelMes($periodo) as $credito => $pagos) {
                $e = $pagos->first()->expediente;
                $det = $dev->detalles()->firstOrNew(['credito_sicmact' => $credito]);
                $det->fill([
                    'expediente_id' => $e?->id, 'cliente' => $e?->cliente, 'documento' => $e?->dni, 'lote' => $e?->lote?->codigo,
                    'pagos' => $det->pagos + $pagos->count(),
                    'capital' => bcadd((string) ($det->capital ?? '0'), Calculos::sumar($pagos->pluck('capital')), 2),
                    'factor_cobertura' => $pagos->first()->factor_cobertura,
                    'monto_rct' => bcadd((string) ($det->monto_rct ?? '0'), Calculos::sumar($pagos->pluck('reversion')), 2),
                ])->save();
                PagoPrograma::whereIn('id', $pagos->pluck('id'))->update(['estado' => 'Incluido en devolución', 'devolucion_id' => $dev->id]);
            }
            $this->totales($dev);
            Seguimiento::registrar($usuario->id, 'Devolución COFIDE', $dev->estado, null, null, null,
                'Devolución de '.$dev->nombrePeriodo().' calculada: '.Estilo::solesPe($dev->monto_calculado).' de '.$dev->creditos.' créditos.');
            return $dev;
        });
    }

    private function totales(DevolucionCofide $dev): void
    {
        $d = $dev->detalles()->where('resultado', '!=', 'solo_txt')->get();
        $dev->update(['creditos' => $d->count(), 'pagos' => $d->sum('pagos'), 'monto_calculado' => Calculos::sumar($d->pluck('monto_rct'))]);
    }

    /** Importa el TXT de COFIDE y cruza crédito por crédito. Devuelve el resultado del cruce. */
    public function cruzar(DevolucionCofide $dev, string $contenido, string $archivo, User $usuario, bool $avisar = true): array
    {
        throw_if($dev->cerrada(), new \DomainException('La devolución ya fue devuelta.'));
        $txt = CruceTxt::leer($contenido, config('rct.impulso.txt.separador', '|'), config('rct.impulso.txt.decimal', '.'));

        return DB::transaction(function () use ($dev, $txt, $contenido, $archivo, $usuario, $avisar) {
            $dev->detalles()->where('resultado', 'solo_txt')->delete();
            $detalles = $dev->detalles()->get()->keyBy('credito_sicmact');
            $r = CruceTxt::cruzar($detalles->map(fn($d) => (string) $d->monto_rct)->all(), array_map(fn($f) => $f['monto'], $txt));

            foreach ($r['filas'] as $credito => $f) {
                if ($f['resultado'] === 'solo_txt') {
                    $dev->detalles()->create(['credito_sicmact' => $credito, 'documento' => $txt[$credito]['documento'] ?? null,
                        'lote' => $txt[$credito]['lote'] ?? null, 'monto_rct' => 0, 'monto_txt' => $f['txt'], 'diferencia' => $f['diferencia'], 'resultado' => 'solo_txt']);
                } else {
                    $detalles[$credito]->update(['monto_txt' => $f['txt'], 'diferencia' => $f['diferencia'], 'resultado' => $f['resultado']]);
                }
            }
            $ruta = 'impulso/txt/'.$dev->periodo->format('Ym').'_'.now()->format('His').'_'.preg_replace('/[^\w.\-]/', '_', $archivo);
            Storage::disk('private')->put($ruta, $contenido);
            $dev->update([
                'monto_notificado' => $r['total_txt'], 'diferencia' => $r['diferencia'], 'n_diferencias' => $r['n_diferencias'],
                'estado' => $r['cuadra'] ? 'Cuadrada' : 'Con diferencias', 'txt_archivo' => $archivo, 'txt_ruta' => $ruta,
                'txt_importado_en' => now(), 'cruzado_por' => $usuario->id,
            ]);
            Seguimiento::registrar($usuario->id, 'Cruce TXT COFIDE', $dev->estado, null, null, null,
                "Cruce de {$archivo} con la devolución de {$dev->nombrePeriodo()}: diferencia ".Estilo::solesPe($r['diferencia'])
                ." ({$r['n_diferencias']} créditos con diferencia).");
            if ($avisar && !$r['cuadra']) $this->avisos->cruceConDiferencias($dev);
            return $r;
        });
    }

    /** Cierra la devolución: con diferencia S/ 0,00, o con justificación del Jefe de Recuperaciones. */
    public function cerrar(DevolucionCofide $dev, User $usuario, ?string $justificacion = null): void
    {
        throw_if($dev->cerrada(), new \DomainException('La devolución ya fue devuelta.'));
        throw_unless($dev->txt_importado_en, new \DomainException('Importe primero el TXT de COFIDE para cruzar la devolución.'));
        if (!$dev->cuadra()) {
            throw_unless($usuario->rol?->nombre === 'Jefe de Recuperaciones',
                new \DomainException('Con diferencias, solo el Jefe de Recuperaciones puede cerrar la devolución.'));
            throw_unless(mb_strlen(trim((string) $justificacion)) >= 20,
                new \DomainException('Escriba una justificación de al menos 20 caracteres para cerrar con diferencias.'));
        }
        DB::transaction(function () use ($dev, $usuario, $justificacion) {
            $dev->update(['estado' => 'Devuelta', 'cerrado_en' => now(), 'cerrado_por' => $usuario->id,
                'justificacion' => $dev->cuadra() ? null : trim((string) $justificacion)]);
            $dev->pagos()->update(['estado' => 'Devuelto']);
            Seguimiento::registrar($usuario->id, 'Devolución COFIDE', 'Devuelta', null, null, null,
                'Devolución de '.$dev->nombrePeriodo().' cerrada por '.Estilo::solesPe($dev->monto_calculado)
                .($dev->justificacion ? ' con justificación: '.$dev->justificacion : ' con diferencia S/ 0,00.'));
        });
    }

    /** TXT de ejemplo con el formato de COFIDE (demo): igual a lo calculado, salvo los ajustes indicados [credito => monto | null]. */
    public function txtEjemplo(DevolucionCofide $dev, array $ajustes = []): string
    {
        $lineas = [];
        foreach ($dev->detalles()->where('resultado', '!=', 'solo_txt')->orderBy('credito_sicmact')->get() as $d) {
            if (array_key_exists($d->credito_sicmact, $ajustes) && $ajustes[$d->credito_sicmact] === null) continue;
            $monto = $ajustes[$d->credito_sicmact] ?? (string) $d->monto_rct;
            $lineas[] = ['CODIGO_IFI' => config('rct.impulso.codigo_ifi'), 'PERIODO' => $dev->periodo->format('Ym'),
                'NRO_LOTE' => preg_replace('/\D/', '', (string) $d->lote), 'NRO_CREDITO' => $d->credito_sicmact,
                'TIPO_DOC' => strlen((string) $d->documento) === 11 ? 'RUC' : 'DNI', 'NRO_DOC' => $d->documento,
                'CAPITAL_RECUPERADO' => number_format((float) $d->capital, 2, '.', ''), 'FACTOR_COBERTURA' => number_format((float) $d->factor_cobertura, 4, '.', ''),
                'MONTO_A_DEVOLVER' => number_format((float) $monto, 2, '.', '')];
        }
        return CruceTxt::escribir($lineas);
    }

    /** Aviso de devolución próxima a vencer (una sola vez por devolución). */
    public function avisarPorVencer(): int
    {
        $dias = (int) config('rct.impulso.dias_aviso_vencimiento', 5);
        $n = 0;
        foreach (DevolucionCofide::where('estado', '!=', 'Devuelta')->whereNull('aviso_vencimiento_en')
                     ->whereDate('fecha_limite', '<=', now()->addDays($dias))->get() as $dev) {
            $this->avisos->devolucionPorVencer($dev);
            $dev->update(['aviso_vencimiento_en' => now()]);
            $n++;
        }
        return $n;
    }
}
