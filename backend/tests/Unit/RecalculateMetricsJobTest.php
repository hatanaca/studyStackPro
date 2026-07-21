<?php

namespace Tests\Unit;

use App\Events\Analytics\MetricsRecalculated;
use App\Jobs\RecalculateMetricsJob;
use App\Models\User;
use App\Modules\Analytics\Aggregators\MetricsAggregator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

class RecalculateMetricsJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_calls_aggregator_and_dispatches_event(): void
    {
        Event::fake([MetricsRecalculated::class]);

        $user = User::factory()->create();
        $aggregator = Mockery::mock(MetricsAggregator::class);
        $aggregator->shouldReceive('recalculateUserMetrics')->once()->withArgs([$user->id, Mockery::any()]);
        $aggregator->shouldReceive('recalculateTechnologyMetrics')->once()->with($user->id);
        $aggregator->shouldReceive('recalculateDailyMinutes')->once()->withArgs([$user->id, Mockery::any()]);

        $this->app->instance(MetricsAggregator::class, $aggregator);

        $taggedCache = Mockery::mock();
        $taggedCache->shouldReceive('flush')->once();
        Cache::shouldReceive('tags')->with(["analytics:user:{$user->id}"])->andReturn($taggedCache);

        $job = new RecalculateMetricsJob($user->id, true);
        $job->handle($aggregator);

        Event::assertDispatched(MetricsRecalculated::class, function ($e) use ($user) {
            return $e->userId === $user->id;
        });
    }

    public function test_failed_calls_log_error(): void
    {
        $user = User::factory()->create();
        $job = new RecalculateMetricsJob($user->id, true);
        $exception = new \RuntimeException('Test failure');

        Log::shouldReceive('error')
            ->once()
            ->withArgs(function ($msg, $context) use ($user) {
                return $msg === 'RecalculateMetricsJob failed'
                    && isset($context['userId'], $context['attempt'], $context['exception'])
                    && $context['userId'] === $user->id
                    && $context['exception']->getMessage() === 'Test failure';
            });

        $job->failed($exception);
    }
}
