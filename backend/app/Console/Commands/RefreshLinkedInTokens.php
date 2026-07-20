<?php

namespace App\Console\Commands;

use App\Jobs\RefreshLinkedInTokenJob;
use App\Models\User;
use Illuminate\Console\Command;

class RefreshLinkedInTokens extends Command
{
    protected $signature = 'linkedin:refresh-tokens';

    protected $description = 'Dispatch jobs to refresh LinkedIn tokens that are about to expire';

    public function handle(): int
    {
        $users = User::whereNotNull('linkedin_token')
            ->whereNotNull('linkedin_refresh_token')
            ->where('linkedin_token_expires_at', '<=', now()->addHours(2))
            ->get();

        $count = 0;
        foreach ($users as $user) {
            RefreshLinkedInTokenJob::dispatch($user->id);
            $count++;
        }

        $this->info("Dispatched {$count} LinkedIn token refresh jobs.");

        return self::SUCCESS;
    }
}
