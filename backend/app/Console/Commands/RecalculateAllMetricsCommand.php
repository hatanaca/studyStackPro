<?php

namespace App\Console\Commands;

use App\Jobs\RecalculateMetricsJob;
use App\Models\User;
use Illuminate\Console\Command;

class RecalculateAllMetricsCommand extends Command
{
    protected $signature = 'metrics:recalculate-all';

    protected $description = 'Despacha RecalculateMetricsJob para todos os usuários';

    public function handle(): int
    {
        $count = User::count();
        if ($count === 0) {
            $this->info('Nenhum usuário no sistema.');

            return self::SUCCESS;
        }

        User::query()->pluck('id')->each(fn (string $id) => RecalculateMetricsJob::dispatch($id));

        $this->info("{$count} job(s) enfileirado(s).");

        return self::SUCCESS;
    }
}
