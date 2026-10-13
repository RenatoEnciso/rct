<?php
namespace App\Console\Commands;

use App\Services\Impulso\SincronizadorCore;
use Illuminate\Console\Command;

class SincronizarPagosCore extends Command
{
    protected $signature = 'rct:sincronizar-pagos-core {--origen=programado : programado | manual | simulacion}';
    protected $description = 'Lee créditos y pagos nuevos del core (programado cada 30 minutos; idempotente por id_core)';

    public function handle(SincronizadorCore $sincronizador): int
    {
        $r = $sincronizador->ejecutar($this->option('origen'));
        $this->table(['Créditos del programa', 'Pagos leídos', 'Nuevos', 'Del programa', 'Agregados', 'Sin reversión', 'Candidatos nuevos'],
            [[$r->creditos_leidos, $r->pagos_leidos, $r->pagos_nuevos, $r->pagos_programa, $r->agregados, $r->sin_reversion, $r->candidatos_nuevos]]);
        $this->info('Sincronización '.$r->id.' finalizada '.$r->finalizado_en->format('d/m/Y H:i:s'));
        return self::SUCCESS;
    }
}
