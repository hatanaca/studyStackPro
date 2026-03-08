<?php

namespace Tests\Unit;

use App\Modules\Analytics\Repositories\Contracts\AnalyticsRepositoryInterface;
use App\Modules\Analytics\Services\AnalyticsService;
use Mockery;
use Tests\TestCase;

class AnalyticsServiceExportTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_get_export_data_delegates_to_repository_and_returns_array(): void
    {
        $userId = 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11';
        $start = '2025-01-01';
        $end = '2025-01-31';
        $expected = [
            ['date' => '2025-01-01', 'total_minutes' => 120, 'session_count' => 2],
            ['date' => '2025-01-02', 'total_minutes' => 0, 'session_count' => 0],
        ];

        $repository = Mockery::mock(AnalyticsRepositoryInterface::class);
        $repository->shouldReceive('getDailyMinutesByRange')
            ->once()
            ->with($userId, $start, $end)
            ->andReturn($expected);

        $this->app->instance(AnalyticsRepositoryInterface::class, $repository);

        $service = app(AnalyticsService::class);
        $result = $service->getExportData($userId, $start, $end);

        $this->assertSame($expected, $result);
    }
}
