<?php

namespace Tests\Integration\Lua;

use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

/**
 * Executes sliding_window.lua against a real Redis instance.
 */
class SlidingWindowLuaIntegrationTest extends TestCase
{
    private string $script;

    protected function setUp(): void
    {
        parent::setUp();

        try {
            Redis::ping();
        } catch (\Throwable $e) {
            $this->markTestSkipped('Redis não disponível: '.$e->getMessage());
        }

        $path = base_path('../redis-scripts/sliding_window.lua');
        $content = file_get_contents($path);
        if ($content === false) {
            $this->markTestSkipped("Script Lua não encontrado em: {$path}");
        }
        $this->script = $content;

        Redis::flushdb();
    }

    protected function tearDown(): void
    {
        Redis::flushdb();
        parent::tearDown();
    }

    public function test_first_request_is_allowed(): void
    {
        $now = (int) (microtime(true) * 1000);

        $result = Redis::eval(
            $this->script,
            1,
            'rate:test:path',
            $now,
            60000,
            10
        );

        $this->assertSame(1, (int) $result[0]);
        $this->assertSame(0, (int) $result[1]);
    }

    public function test_requests_within_limit_pass(): void
    {
        $now = (int) (microtime(true) * 1000);

        for ($i = 0; $i < 5; $i++) {
            $result = Redis::eval(
                $this->script,
                1,
                'rate:test:multi',
                $now + $i,
                60000,
                5
            );
        }

        $this->assertSame(1, (int) $result[0]);
    }

    public function test_exceeding_limit_is_blocked(): void
    {
        $now = (int) (microtime(true) * 1000);

        for ($i = 0; $i < 3; $i++) {
            Redis::eval($this->script, 1, 'rate:test:block', $now + $i, 60000, 3);
        }

        $result = Redis::eval(
            $this->script,
            1,
            'rate:test:block',
            $now + 3,
            60000,
            3
        );

        $this->assertSame(0, (int) $result[0]);
        $this->assertGreaterThan(0, (int) $result[1]);
    }

    public function test_expired_entries_are_cleaned(): void
    {
        $past = (int) (microtime(true) * 1000) - 120000;

        for ($i = 0; $i < 3; $i++) {
            Redis::eval($this->script, 1, 'rate:test:expire', $past + $i, 60000, 3);
        }

        $now = (int) (microtime(true) * 1000);
        $result = Redis::eval($this->script, 1, 'rate:test:expire', $now, 60000, 3);

        $this->assertSame(1, (int) $result[0]);
    }

    public function test_retry_after_returns_positive_seconds(): void
    {
        $now = (int) (microtime(true) * 1000);

        for ($i = 0; $i < 2; $i++) {
            Redis::eval($this->script, 1, 'rate:test:retry', $now + $i, 60000, 2);
        }

        $result = Redis::eval($this->script, 1, 'rate:test:retry', $now + 2, 60000, 2);

        $this->assertSame(0, (int) $result[0]);
        $this->assertGreaterThanOrEqual(1, (int) $result[1]);
    }
}
