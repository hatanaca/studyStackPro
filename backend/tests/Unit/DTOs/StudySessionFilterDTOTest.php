<?php

namespace Tests\Unit\DTOs;

use App\Modules\StudySessions\DTOs\StudySessionFilterDTO;
use Tests\TestCase;

class StudySessionFilterDTOTest extends TestCase
{
    public function test_from_array_populates_all_fields(): void
    {
        $dto = StudySessionFilterDTO::fromArray([
            'technology_id' => 'tech-123',
            'date_from' => '2025-01-01',
            'date_to' => '2025-12-31',
            'min_duration' => '30',
            'mood' => '4',
            'status' => 'active',
            'per_page' => '25',
        ]);

        $this->assertEquals('tech-123', $dto->technologyId);
        $this->assertEquals('2025-01-01', $dto->dateFrom);
        $this->assertEquals('2025-12-31', $dto->dateTo);
        $this->assertEquals(30, $dto->minDuration);
        $this->assertEquals(4, $dto->mood);
        $this->assertEquals('active', $dto->status);
        $this->assertEquals(25, $dto->perPage);
    }

    public function test_from_array_defaults_to_null_for_missing_fields(): void
    {
        $dto = StudySessionFilterDTO::fromArray([]);

        $this->assertNull($dto->technologyId);
        $this->assertNull($dto->dateFrom);
        $this->assertNull($dto->dateTo);
        $this->assertNull($dto->minDuration);
        $this->assertNull($dto->mood);
        $this->assertNull($dto->status);
        $this->assertEquals(15, $dto->perPage);
    }

    public function test_from_array_caps_per_page_at_50(): void
    {
        $dto = StudySessionFilterDTO::fromArray(['per_page' => '100']);

        $this->assertEquals(50, $dto->perPage);
    }

    public function test_from_array_converts_empty_string_min_duration_to_null(): void
    {
        $dto = StudySessionFilterDTO::fromArray(['min_duration' => '']);

        $this->assertNull($dto->minDuration);
    }

    public function test_from_array_converts_empty_string_mood_to_null(): void
    {
        $dto = StudySessionFilterDTO::fromArray(['mood' => '']);

        $this->assertNull($dto->mood);
    }

    public function test_to_array_excludes_null_values(): void
    {
        $dto = new StudySessionFilterDTO(perPage: 10);
        $arr = $dto->toArray();

        $this->assertArrayNotHasKey('technology_id', $arr);
        $this->assertArrayNotHasKey('date_from', $arr);
        $this->assertArrayNotHasKey('date_to', $arr);
        $this->assertArrayNotHasKey('min_duration', $arr);
        $this->assertArrayNotHasKey('mood', $arr);
        $this->assertArrayNotHasKey('status', $arr);
        $this->assertEquals(10, $arr['per_page']);
    }

    public function test_to_array_includes_non_null_values(): void
    {
        $dto = new StudySessionFilterDTO(
            technologyId: 'tech-1',
            dateFrom: '2025-01-01',
            perPage: 20,
        );
        $arr = $dto->toArray();

        $this->assertEquals('tech-1', $arr['technology_id']);
        $this->assertEquals('2025-01-01', $arr['date_from']);
        $this->assertEquals(20, $arr['per_page']);
    }

    public function test_constructor_defaults(): void
    {
        $dto = new StudySessionFilterDTO();

        $this->assertNull($dto->technologyId);
        $this->assertNull($dto->dateFrom);
        $this->assertNull($dto->dateTo);
        $this->assertNull($dto->minDuration);
        $this->assertNull($dto->mood);
        $this->assertNull($dto->status);
        $this->assertEquals(15, $dto->perPage);
    }

    public function test_to_array_always_contains_per_page(): void
    {
        $dto = new StudySessionFilterDTO();
        $arr = $dto->toArray();

        $this->assertArrayHasKey('per_page', $arr);
    }
}
