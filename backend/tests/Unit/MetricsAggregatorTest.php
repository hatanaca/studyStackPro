<?php

namespace Tests\Unit;

use App\Models\StudySession;
use App\Models\Technology;
use App\Models\User;
use App\Modules\Analytics\Aggregators\MetricsAggregator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MetricsAggregatorTest extends TestCase
{
    use RefreshDatabase;

    private MetricsAggregator $aggregator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->aggregator = app(MetricsAggregator::class);
    }

    public function test_streak_zero_without_sessions(): void
    {
        $user = User::factory()->create();
        $this->aggregator->recalculateUserMetrics($user->id);

        $row = DB::selectOne('SELECT * FROM analytics.user_metrics WHERE user_id = ?::uuid', [$user->id]);
        $this->assertNotNull($row);
        $this->assertSame(0, (int) $row->total_sessions);
        $this->assertSame(0, (int) $row->total_minutes);
        $this->assertSame(0, (int) $row->current_streak_days);
        $this->assertSame(0, (int) $row->max_streak_days);
    }

    public function test_streak_n_days_with_consecutive_sessions(): void
    {
        $user = User::factory()->create();
        $tech = Technology::forceCreate([
            'user_id' => $user->id,
            'name' => 'PHP',
            'slug' => 'php',
            'color' => '#777BB4',
            'is_active' => true,
        ]);

        $baseDate = now()->timezone(config('app.timezone'))->subDays(2)->startOfDay();
        for ($i = 0; $i < 3; $i++) {
            $start = $baseDate->copy()->addDays($i)->setTime(10, 0);
            $end = $start->copy()->addMinutes(30);
            StudySession::forceCreate([
                'user_id' => $user->id,
                'technology_id' => $tech->id,
                'started_at' => $start,
                'ended_at' => $end,
                'notes' => null,
                'mood' => null,
                'focus_score' => null,
            ]);
        }

        $this->aggregator->recalculateUserMetrics($user->id);

        $row = DB::selectOne('SELECT * FROM analytics.user_metrics WHERE user_id = ?::uuid', [$user->id]);
        $this->assertNotNull($row);
        $this->assertSame(3, (int) $row->total_sessions);
        $this->assertSame(90, (int) $row->total_minutes);
        $this->assertGreaterThanOrEqual(1, (int) $row->current_streak_days);
        $this->assertGreaterThanOrEqual(1, (int) $row->max_streak_days);
    }

    public function test_gap_breaks_streak(): void
    {
        $user = User::factory()->create();
        $tech = Technology::forceCreate([
            'user_id' => $user->id,
            'name' => 'PHP',
            'slug' => 'php',
            'color' => '#777BB4',
            'is_active' => true,
        ]);

        $tz = config('app.timezone', 'UTC');
        $day1 = now()->timezone($tz)->subDays(10)->startOfDay()->setTime(10, 0);
        $day2 = now()->timezone($tz)->subDays(9)->startOfDay()->setTime(10, 0);
        $day3 = now()->timezone($tz)->subDays(5)->startOfDay()->setTime(10, 0); // gap de 4 dias

        foreach ([$day1, $day2, $day3] as $start) {
            StudySession::forceCreate([
                'user_id' => $user->id,
                'technology_id' => $tech->id,
                'started_at' => $start,
                'ended_at' => $start->copy()->addMinutes(30),
                'notes' => null,
                'mood' => null,
                'focus_score' => null,
            ]);
        }

        $this->aggregator->recalculateUserMetrics($user->id);

        $row = DB::selectOne('SELECT * FROM analytics.user_metrics WHERE user_id = ?::uuid', [$user->id]);
        $this->assertNotNull($row);
        $this->assertSame(3, (int) $row->total_sessions);
        $this->assertSame(2, (int) $row->max_streak_days); // streak de 2 (day1, day2)
    }

    public function test_personal_best_preserved_after_gap(): void
    {
        $user = User::factory()->create();
        $tech = Technology::forceCreate([
            'user_id' => $user->id,
            'name' => 'PHP',
            'slug' => 'php',
            'color' => '#777BB4',
            'is_active' => true,
        ]);

        $tz = config('app.timezone', 'UTC');
        for ($i = 0; $i < 5; $i++) {
            $start = now()->timezone($tz)->subDays(20)->addDays($i)->startOfDay()->setTime(10, 0);
            StudySession::forceCreate([
                'user_id' => $user->id,
                'technology_id' => $tech->id,
                'started_at' => $start,
                'ended_at' => $start->copy()->addMinutes(30),
                'notes' => null,
                'mood' => null,
                'focus_score' => null,
            ]);
        }
        $this->aggregator->recalculateUserMetrics($user->id);
        $rowBefore = DB::selectOne('SELECT max_streak_days FROM analytics.user_metrics WHERE user_id = ?::uuid', [$user->id]);
        $maxBefore = (int) $rowBefore->max_streak_days;

        $gapStart = now()->timezone($tz)->subDays(10)->startOfDay()->setTime(10, 0);
        StudySession::forceCreate([
            'user_id' => $user->id,
            'technology_id' => $tech->id,
            'started_at' => $gapStart,
            'ended_at' => $gapStart->copy()->addMinutes(30),
            'notes' => null,
            'mood' => null,
            'focus_score' => null,
        ]);
        $this->aggregator->recalculateUserMetrics($user->id);
        $rowAfter = DB::selectOne('SELECT max_streak_days FROM analytics.user_metrics WHERE user_id = ?::uuid', [$user->id]);
        $maxAfter = (int) $rowAfter->max_streak_days;

        $this->assertSame($maxBefore, $maxAfter, 'max_streak_days deve ser preservado após gap');
    }
}
