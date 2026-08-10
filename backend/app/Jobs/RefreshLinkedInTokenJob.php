<?php

namespace App\Jobs;

use App\Models\User;
use App\Modules\LinkedIn\Services\LinkedInService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RefreshLinkedInTokenJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public array $backoff = [60];

    public int $timeout = 20;

    public function __construct(
        public readonly string $userId,
    ) {
        $this->onQueue('default');
    }

    public function handle(LinkedInService $linkedin): void
    {
        $user = User::find($this->userId);
        if (! $user) {
            Log::warning('RefreshLinkedInTokenJob: user not found', ['userId' => $this->userId]);

            return;
        }

        $linkedin->refreshToken($user);

        Log::info('LinkedIn token refreshed via job', ['user_id' => $this->userId]);
    }

    public function failed(\Throwable $e): void
    {
        Log::error('RefreshLinkedInTokenJob failed', [
            'userId' => $this->userId,
            'exception' => $e,
        ]);
    }
}
