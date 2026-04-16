<?php

namespace Tests\Integration\Lua;

use Illuminate\Support\Facades\Redis;
use Tests\Concerns\InteractsWithRealRedis;
use Tests\TestCase;

/**
 * Executes streak_update.lua against a real Redis instance.
 * Requires REDIS_HOST to be available (CI service or local).
 */
class StreakLuaIntegrationTest extends TestCase
{
    use InteractsWithRealRedis;

    private string $script;

    protected function setUp(): void
    {
        parent::setUp();

        $path = base_path('../redis-scripts/streak_update.lua');
        $content = file_get_contents($path);
        if ($content === false) {
            $this->markTestSkipped("Script Lua não encontrado em: {$path}");
        }
        $this->script = $content;

        $this->skipUnlessRedisIntegrationProbe(function () {
            Redis::flushdb();
            Redis::eval(
                $this->script,
                2,
                'streak:user:probe',
                'streak:last_day:probe',
                '2026-04-08',
                '2026-04-07'
            );
        });
    }

    protected function tearDown(): void
    {
        try {
            Redis::flushdb();
        } catch (\Throwable) {
        }
        parent::tearDown();
    }

    public function test_first_study_day_sets_streak_to_one(): void
    {
        $result = Redis::eval(
            $this->script,
            2,
            'streak:user:test1',
            'streak:last_day:test1',
            '2026-04-08',
            '2026-04-07'
        );

        $this->assertSame(1, $result);
        $this->assertSame('1', Redis::get('streak:user:test1'));
        $this->assertSame('2026-04-08', Redis::get('streak:last_day:test1'));
    }

    public function test_consecutive_day_increments_streak(): void
    {
        Redis::set('streak:user:test2', 3, 'EX', 172800);
        Redis::set('streak:last_day:test2', '2026-04-07', 'EX', 172800);

        $result = Redis::eval(
            $this->script,
            2,
            'streak:user:test2',
            'streak:last_day:test2',
            '2026-04-08',
            '2026-04-07'
        );

        $this->assertSame(4, $result);
    }

    public function test_gap_resets_streak_to_one(): void
    {
        Redis::set('streak:user:test3', 5, 'EX', 172800);
        Redis::set('streak:last_day:test3', '2026-04-05', 'EX', 172800);

        $result = Redis::eval(
            $this->script,
            2,
            'streak:user:test3',
            'streak:last_day:test3',
            '2026-04-08',
            '2026-04-07'
        );

        $this->assertSame(1, $result);
    }

    public function test_same_day_returns_current_streak(): void
    {
        Redis::set('streak:user:test4', 7, 'EX', 172800);
        Redis::set('streak:last_day:test4', '2026-04-08', 'EX', 172800);

        $result = Redis::eval(
            $this->script,
            2,
            'streak:user:test4',
            'streak:last_day:test4',
            '2026-04-08',
            '2026-04-07'
        );

        $this->assertSame(7, $result);
    }

    public function test_streak_keys_have_ttl(): void
    {
        Redis::eval(
            $this->script,
            2,
            'streak:user:test5',
            'streak:last_day:test5',
            '2026-04-08',
            '2026-04-07'
        );

        $this->assertGreaterThan(0, Redis::ttl('streak:user:test5'));
        $this->assertGreaterThan(0, Redis::ttl('streak:last_day:test5'));
    }
}
