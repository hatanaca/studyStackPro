<?php

namespace App\Jobs;

use App\Models\User;
use App\Modules\LinkedIn\DTOs\LinkedInPostDTO;
use App\Modules\LinkedIn\Services\LinkedInService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ShareLinkedInPostJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [30, 60];

    public int $timeout = 30;

    public function __construct(
        public readonly string $userId,
        public readonly string $text,
    ) {
        $this->onQueue('default');
    }

    public function handle(LinkedInService $linkedin): void
    {
        $user = User::find($this->userId);
        if (! $user) {
            Log::warning('ShareLinkedInPostJob: user not found', ['userId' => $this->userId]);

            return;
        }

        $dto = new LinkedInPostDTO(text: $this->text);
        $result = $linkedin->sharePost($user, $dto);

        Log::info('LinkedIn post shared via job', [
            'user_id' => $this->userId,
            'post_id' => $result['id'] ?? null,
        ]);
    }

    public function failed(\Throwable $e): void
    {
        Log::error('ShareLinkedInPostJob failed', [
            'userId' => $this->userId,
            'exception' => $e,
        ]);
    }
}
