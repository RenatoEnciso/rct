<?php
namespace App\Services\Impulso;

use App\Models\{CoreCredito, CorePago, ExpedienteGobierno, SincronizacionCore};

/**
 * Demo: inserta en el core (tabla espejo) un pago realista de un crédito del programa
 * y dispara la sincronización, como haría el core real a los 30 minutos.
 */
class SimuladorCore
{
    public function __construct(private SincronizadorCore $sincronizador) {}

    /** @return array{pago: CorePago, sincronizacion: SincronizacionCore} */
    public function simularPago(?string $credito = null, ?float $capital = null, bool $honrado = true, ?int $userId = null): array
    {
        $e = $credito
            ? ExpedienteGobierno::where('credito_sicmact', $credito)->firstOrFail()
            : ExpedienteGobierno::where('estado_honramiento', $honrado ? 'Honrado' : 'Solicitado')
                ->where('saldo_insoluto', '>', 6000)->inRandomOrder()->firstOrFail();

        $capital ??= (float) collect([1500, 2000, 2500, 3000, 3500, 4000])->filter(fn($m) => $m < (float) $e->saldo_insoluto * 0.5)->random();
        $interes = round($capital * 0.08, 2);
        $mora = round($capital * 0.01, 2);
        $ahora = now();

        $pago = CorePago::create([
            'id_core' => 'CORE-'.$ahora->format('ymd-His').'-'.random_int(100, 999),
            'credito_sicmact' => $e->credito_sicmact, 'fecha_pago' => $ahora,
            'monto_total' => round($capital + $interes + $mora, 2), 'capital' => $capital, 'interes' => $interes, 'mora' => $mora,
            'tienda_id' => $e->tienda_id, 'canal' => 'Ventanilla', 'registrado_en' => $ahora,
        ]);
        // El core refleja el pago en el saldo del crédito
        CoreCredito::where('credito_sicmact', $e->credito_sicmact)->first()?->update([
            'saldo_capital' => max(0, (float) CoreCredito::where('credito_sicmact', $e->credito_sicmact)->value('saldo_capital') - $capital),
            'actualizado_en' => $ahora,
        ]);

        return ['pago' => $pago, 'sincronizacion' => $this->sincronizador->ejecutar('simulacion', $userId)];
    }
}
