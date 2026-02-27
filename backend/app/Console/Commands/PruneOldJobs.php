<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class PruneOldJobs extends Command
{
    protected $signature = 'queue:prune-old
                            {--hours=24 : Número de horas para considerar jobs como antigos}';

    protected $description = 'Remove jobs falhos e completados antigos das filas';

    public function handle(): int
    {
        $hours = (int) $this->option('hours');

        $this->info("Removendo jobs falhos com mais de {$hours} horas...");

        Artisan::call('queue:prune-failed', ['--hours' => $hours]);

        $this->info('Jobs antigos removidos com sucesso.');

        return self::SUCCESS;
    }
}
