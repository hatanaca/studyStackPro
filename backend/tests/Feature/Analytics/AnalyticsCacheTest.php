<?php

namespace Tests\Feature\Analytics;

use App\Jobs\RecalculateMetricsJob;
use App\Listeners\StudySession\DispatchMetricsRecalculation;
use App\Models\StudySession;
use App\Models\Technology;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

/**
 * Documenta o comportamento de invalidação do cache de analytics.
 * Quando uma sessão é criada, atualizada ou deletada, o RecalculateMetricsJob
 * é disparado e invalida o cache (tags analytics + user:X) antes de recalcular.
 */
class AnalyticsCacheTest extends TestCase
{
    use RefreshDatabase;

    public function test_dispatch_metrics_listener_dispatches_recalculate_job(): void
    {
        Bus::fake([RecalculateMetricsJob::class]);

        $user = User::factory()->create();
        $tech = Technology::forceCreate([
            'user_id' => $user->id,
            'name' => 'PHP',
            'slug' => 'php',
            'color' => '#777BB4',
            'is_active' => true,
        ]);

        $session = StudySession::forceCreate([
            'user_id' => $user->id,
            'technology_id' => $tech->id,
            'started_at' => now(),
            'ended_at' => now()->addMinutes(30),
            'notes' => null,
            'mood' => null,
            'focus_score' => null,
        ]);

        $event = new \App\Events\StudySession\StudySessionCreated($session);
        app(DispatchMetricsRecalculation::class)->handle($event);

        Bus::assertDispatched(RecalculateMetricsJob::class, function (RecalculateMetricsJob $job) use ($user) {
            return $job->userId === $user->id;
        });
    }
}
