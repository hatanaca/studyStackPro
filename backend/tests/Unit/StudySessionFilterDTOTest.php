<?php

namespace Tests\Unit;

use App\Modules\StudySessions\DTOs\StudySessionFilterDTO;
use Tests\TestCase;

class StudySessionFilterDTOTest extends TestCase
{
    public function test_from_array_creates_dto_with_valid_fields(): void
    {
        $dto = StudySessionFilterDTO::fromArray([
            'technology_id' => 'tech-123',
            'date_from' => '2026-01-01',
            'date_to' => '2026-12-31',
            'per_page' => 25,
            'min_duration' => 30,
            'mood' => 4,
            'status' => 'active',
        ]);

        $this->assertEquals('tech-123', $dto->technologyId);
        $this->assertEquals('2026-01-01', $dto->dateFrom);
        $this->assertEquals('2026-12-31', $dto->dateTo);
        $this->assertEquals(25, $dto->perPage);
        $this->assertEquals(30, $dto->minDuration);
        $this->assertEquals(4, $dto->mood);
        $this->assertEquals('active', $dto->status);
    }

    public function test_from_array_with_defaults(): void
    {
        $dto = StudySessionFilterDTO::fromArray([]);

        $this->assertNull($dto->technologyId);
        $this->assertNull($dto->dateFrom);
        $this->assertNull($dto->dateTo);
        $this->assertEquals(15, $dto->perPage);
        $this->assertNull($dto->minDuration);
        $this->assertNull($dto->mood);
        $this->assertNull($dto->status);
    }

    public function test_from_array_clamps_per_page_to_50(): void
    {
        $dto = StudySessionFilterDTO::fromArray(['per_page' => 99]);

        $this->assertEquals(50, $dto->perPage);
    }

    public function test_to_array_removes_null_values(): void
    {
        $dto = new StudySessionFilterDTO(technologyId: 'tech-1', perPage: 10);
        $arr = $dto->toArray();

        $this->assertArrayNotHasKey('date_from', $arr);
        $this->assertArrayNotHasKey('date_to', $arr);
        $this->assertArrayNotHasKey('min_duration', $arr);
        $this->assertArrayNotHasKey('mood', $arr);
        $this->assertArrayNotHasKey('status', $arr);
        $this->assertArrayHasKey('technology_id', $arr);
        $this->assertArrayHasKey('per_page', $arr);
    }

    public function test_to_array_includes_non_null_values(): void
    {
        $dto = new StudySessionFilterDTO(
            technologyId: 'tech-1',
            dateFrom: '2026-01-01',
            dateTo: '2026-12-31',
            minDuration: 30,
            mood: 5,
            status: 'completed',
            perPage: 20,
        );
        $arr = $dto->toArray();

        $this->assertEquals('tech-1', $arr['technology_id']);
        $this->assertEquals('2026-01-01', $arr['date_from']);
        $this->assertEquals('2026-12-31', $arr['date_to']);
        $this->assertEquals(30, $arr['min_duration']);
        $this->assertEquals(5, $arr['mood']);
        $this->assertEquals('completed', $arr['status']);
        $this->assertEquals(20, $arr['per_page']);
    }

    public function test_from_array_with_partial_data(): void
    {
        $dto = StudySessionFilterDTO::fromArray([
            'technology_id' => 'tech-456',
        ]);

        $this->assertEquals('tech-456', $dto->technologyId);
        $this->assertNull($dto->dateFrom);
        $this->assertNull($dto->dateTo);
    }
}
