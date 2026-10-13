<?php
namespace App\Console\Commands;

use App\Services\Analitica\GeneradorAnalitica;
use Illuminate\Console\Command;

class AnaliticaRecalcular extends Command
{
    protected $signature = 'rct:analitica-recalcular';
    protected $description = 'Recalcula recuperos, metas, priorización de cartera, recaída y alertas de control';

    public function handle(GeneradorAnalitica $generador): int
    {
        $r = $generador->generar();
        $this->table(['Recuperos', 'Metas', 'Priorizados', 'En seguimiento', 'Alertas'], [array_values($r)]);
        return self::SUCCESS;
    }
}
