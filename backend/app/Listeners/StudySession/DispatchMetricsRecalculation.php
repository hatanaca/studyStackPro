<?php

namespace App\Listeners\StudySession;

use App\Events\StudySession\StudySessionCreated;
use App\Events\StudySession\StudySessionDeleted;
use App\Events\StudySession\StudySessionUpdated;
use App\Jobs\RecalculateMetricsJob;
use App\Services\RedisLuaService;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Listener que agenda RecalculateMetricsJob após mudanças em sessões.
 * Deleted → full recalc; Updated → recalc se campos relevantes mudaram; Created → full recalc.
 * Delay de 2s para agrupar múltiplas alterações.
 */
class DispatchMetricsRecalculation
{
    public function __construct(
        private RedisLuaService $redisLuaService
    ) {}

    public function handle(StudySessionCreated|StudySessionUpdated|StudySessionDeleted $event): void
    {
        [$userId, $fullRecalc] = match (true) {
            $event instanceof StudySessionDeleted => [$event->userId, true],
            $event instanceof StudySessionUpdated => [
                $event->session->user_id,
                ! empty(array_intersect(
                    $event->changedFields ?? [],
                    ['started_at', 'ended_at', 'technology_id', 'duration_min']
                )),
            ],
            default => [$event->session->user_id, true],
        };

        try {
            $shouldDispatch = (int) $this->redisLuaService->callScript('job_dedup', [
                "job_lock:metrics:{$userId}",
            ], [10]);

            if ($shouldDispatch !== 1) {
                return;
            }
        } catch (Throwable $exception) {
            Log::warning('Deduplicação Lua indisponível; seguindo em fail-open para recálculo de métricas.', [
                'user_id' => $userId,
                'error' => $exception->getMessage(),
            ]);
        }

        RecalculateMetricsJob::dispatch($userId, $fullRecalc)
            ->onQueue('metrics')
            ->delay(now()->addSeconds(2));
    }
}
