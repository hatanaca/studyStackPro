<?php

namespace Tests\Unit;

use App\Modules\Auth\DTOs\LoginDTO;
use App\Modules\Auth\DTOs\RegisterDTO;
use App\Modules\Technologies\DTOs\TechnologyDTO;
use Tests\TestCase;

class DTOTest extends TestCase
{
    public function test_register_dto_stores_values(): void
    {
        $dto = new RegisterDTO(
            name: 'John Doe',
            email: 'john@example.com',
            password: 'secret',
            timezone: 'America/Sao_Paulo',
        );

        $this->assertEquals('John Doe', $dto->name);
        $this->assertEquals('john@example.com', $dto->email);
        $this->assertEquals('secret', $dto->password);
        $this->assertEquals('America/Sao_Paulo', $dto->timezone);
    }

    public function test_register_dto_default_timezone_is_utc(): void
    {
        $dto = new RegisterDTO(
            name: 'Jane',
            email: 'jane@example.com',
            password: 'pass',
        );

        $this->assertEquals('UTC', $dto->timezone);
    }

    public function test_login_dto_stores_values(): void
    {
        $dto = new LoginDTO(
            email: 'john@example.com',
            password: 'secret',
            remember: true,
        );

        $this->assertEquals('john@example.com', $dto->email);
        $this->assertEquals('secret', $dto->password);
        $this->assertTrue($dto->remember);
    }

    public function test_login_dto_remember_defaults_false(): void
    {
        $dto = new LoginDTO(
            email: 'john@example.com',
            password: 'secret',
        );

        $this->assertFalse($dto->remember);
    }

    public function test_technology_dto_stores_values(): void
    {
        $dto = new TechnologyDTO(
            userId: 'user-1',
            name: 'Laravel',
            color: '#FF2D20',
            icon: 'code',
            description: 'PHP framework',
        );

        $this->assertEquals('user-1', $dto->userId);
        $this->assertEquals('Laravel', $dto->name);
        $this->assertEquals('#FF2D20', $dto->color);
        $this->assertEquals('code', $dto->icon);
        $this->assertEquals('PHP framework', $dto->description);
    }

    public function test_technology_dto_optional_fields_default_null(): void
    {
        $dto = new TechnologyDTO(
            userId: 'user-1',
            name: 'Vue.js',
        );

        $this->assertNull($dto->color);
        $this->assertNull($dto->icon);
        $this->assertNull($dto->description);
    }

    public function test_dtos_are_immutable(): void
    {
        $dto = new RegisterDTO(
            name: 'Test',
            email: 'test@test.com',
            password: 'pass',
        );

        $this->expectException(\Error::class);
        $dto->name = 'Changed';
    }

    public function test_login_dto_is_immutable(): void
    {
        $dto = new LoginDTO(
            email: 'test@test.com',
            password: 'pass',
        );

        $this->expectException(\Error::class);
        $dto->email = 'hacked@test.com';
    }

    public function test_technology_dto_is_immutable(): void
    {
        $dto = new TechnologyDTO(
            userId: 'user-1',
            name: 'PHP',
        );

        $this->expectException(\Error::class);
        $dto->name = 'Hacked';
    }
}
