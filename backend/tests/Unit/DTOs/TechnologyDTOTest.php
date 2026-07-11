<?php

namespace Tests\Unit\DTOs;

use App\Modules\Technologies\DTOs\TechnologyDTO;
use Tests\TestCase;

class TechnologyDTOTest extends TestCase
{
    public function test_technology_dto_stores_all_properties(): void
    {
        $dto = new TechnologyDTO(
            userId: 'user-123',
            name: 'Laravel',
            color: '#FF2D20',
            icon: 'code',
            description: 'PHP Framework',
        );

        $this->assertEquals('user-123', $dto->userId);
        $this->assertEquals('Laravel', $dto->name);
        $this->assertEquals('#FF2D20', $dto->color);
        $this->assertEquals('code', $dto->icon);
        $this->assertEquals('PHP Framework', $dto->description);
    }

    public function test_technology_dto_defaults_optional_fields_to_null(): void
    {
        $dto = new TechnologyDTO(
            userId: 'user-123',
            name: 'Vue.js',
        );

        $this->assertNull($dto->color);
        $this->assertNull($dto->icon);
        $this->assertNull($dto->description);
    }

    public function test_technology_dto_is_readonly(): void
    {
        $dto = new TechnologyDTO(
            userId: 'user-123',
            name: 'Vue.js',
        );

        $this->expectException(\Error::class);
        $dto->name = 'React';
    }
}
