<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\{Artisan, Storage};
use Symfony\Component\Process\Process;

/**
 * Regenera la demo desde cero con los mismos datos (seeders deterministas).
 * Antes respalda la base en storage/backups. Borra todo lo registrado a mano.
 */
class DemoReset extends Command
{
    protected $signature = 'rct:demo-reset {--force : No pedir confirmación} {--sin-respaldo : Omitir el respaldo de la base}';
    protected $description = 'Respalda la base y regenera los datos de demostración (migrate:fresh --seed)';

    private const CARPETAS = ['fud', 'sustentos', 'gobierno', 'importaciones', 'exportaciones', 'impulso'];

    public function handle(): int
    {
        if (app()->environment('production')) {
            $this->error('No se permite en producción.');
            return self::FAILURE;
        }
        if (!$this->option('force') && !$this->confirm('Se borrarán todos los datos y se regenerará la demo. ¿Continuar?')) {
            return self::SUCCESS;
        }

        if (!$this->option('sin-respaldo') && !$this->respaldar()) {
            return self::FAILURE;
        }

        $this->info('Limpiando archivos de la demo…');
        foreach (self::CARPETAS as $c) Storage::disk('private')->deleteDirectory($c);

        $this->info('Regenerando base de datos…');
        Artisan::call('migrate:fresh', ['--seed' => true, '--force' => true], $this->output);
        Artisan::call('optimize:clear', [], $this->output);

        $this->info('Demo regenerada.');
        return self::SUCCESS;
    }

    private function respaldar(): bool
    {
        $db = config('database.connections.'.config('database.default'));
        $dir = storage_path('backups');
        if (!is_dir($dir)) mkdir($dir, 0775, true);
        $archivo = $dir.'/rct_'.now()->format('Ymd_His').'.sql';

        $args = [config('rct.demo.mysqldump'), '-h', $db['host'], '-P', (string) $db['port'], '-u', $db['username'],
                 '--single-transaction', '--routines', '--result-file='.$archivo, $db['database']];
        if ($db['password']) array_splice($args, 7, 0, ['-p'.$db['password']]);

        $p = new Process($args);
        $p->setTimeout(300)->run();
        if (!$p->isSuccessful() || !filesize($archivo)) {
            $this->error('No se pudo respaldar la base: '.trim($p->getErrorOutput()));
            $this->line('Use --sin-respaldo para continuar sin respaldo.');
            return false;
        }
        $this->info('Respaldo: '.$archivo.' ('.round(filesize($archivo) / 1024).' KB)');
        return true;
    }
}
