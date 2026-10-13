<?php
namespace App\Console\Commands;

use App\Models\PagoPrograma;
use App\Services\Impulso\SimuladorCore;
use App\Support\Estilo;
use Illuminate\Console\Command;

/** Demo: simula que el core registra un pago de un crédito del programa y sincroniza (toast, campana y correo). */
class SimularPagoCore extends Command
{
    protected $signature = 'rct:simular-pago-core
        {--credito= : N° de crédito SICMACT (por defecto, uno honrado al azar)}
        {--capital= : Capital pagado en soles (por defecto 1 500 a 4 000)}
        {--no-honrado : Simula el pago de un crédito aún no honrado (sin reversión)}';
    protected $description = 'Inserta un pago realista en el core espejo y ejecuta la sincronización (demo)';

    public function handle(SimuladorCore $simulador): int
    {
        if (app()->environment('production')) {
            $this->error('No se permite en producción.');
            return self::FAILURE;
        }
        $r = $simulador->simularPago($this->option('credito'), $this->option('capital') ? (float) $this->option('capital') : null, !$this->option('no-honrado'));
        $p = PagoPrograma::with('expediente')->where('id_core', $r['pago']->id_core)->first();

        $this->info('Pago insertado en el core: '.$r['pago']->id_core);
        $this->line('  Cliente: '.($p?->expediente?->cliente ?? $r['pago']->credito_sicmact).' · crédito '.$r['pago']->credito_sicmact);
        $this->line('  Capital: '.Estilo::solesPe($r['pago']->capital).' · estado: '.($p?->estado ?? '—')
            .($p?->estado === 'Agregado' ? ' · reversión COFIDE '.Estilo::solesPe($p->reversion) : ' · '.($p?->motivo ?? '')));
        $this->line('  Sincronización '.$r['sincronizacion']->id.' completada. Aviso en pantalla (≤ 10 s), campana y correo en storage/logs/laravel.log.');
        return self::SUCCESS;
    }
}
