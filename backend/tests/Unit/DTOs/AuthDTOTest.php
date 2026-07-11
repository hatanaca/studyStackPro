<?php

namespace Tests\Unit\DTOs;

use App\Modules\Auth\DTOs\LoginDTO;
use App\Modules\Auth\DTOs\RegisterDTO;
use Tests\TestCase;

class AuthDTOTest extends TestCase
{
    public function test_register_dto_stores_all_properties(): void
    {
        $dto = new RegisterDTO(
            name: 'Thiago',
            email: 'thiago@test.com',
            password: 'secret123',
            timezone: 'America/Sao_Paulo',
        );

        $this->assertEquals('Thiago', $dto->name);
        $this->assertEquals('thiago@test.com', $dto->email);
        $this->assertEquals('secret123', $dto->password);
        $this->assertEquals('America/Sao_Paulo', $dto->timezone);
    }

    public function test_register_dto_defaults_timezone_to_utc(): void
    {
        $dto = new RegisterDTO(
            name: 'User',
            email: 'user@test.com',
            password: 'pass1234',
        );

        $this->assertEquals('UTC', $dto->timezone);
    }

    public function test_register_dto_is_readonly(): void
    {
        $dto = new RegisterDTO(
            name: 'User',
            email: 'user@test.com',
            password: 'pass1234',
        );

        $this->expectException(\Error::class);
        $dto->name = 'Changed';
    }

    public function test_login_dto_stores_all_properties(): void
    {
        $dto = new LoginDTO(
            email: 'user@test.com',
            password: 'secret123',
            remember: true,
        );

        $this->assertEquals('user@test.com', $dto->email);
        $this->assertEquals('secret123', $dto->password);
        $this->assertTrue($dto->remember);
    }

    public function test_login_dto_defaults_remember_to_false(): void
    {
        $dto = new LoginDTO(
            email: 'user@test.com',
            password: 'secret123',
        );

        $this->assertFalse($dto->remember);
    }

    public function test_login_dto_is_readonly(): void
    {
        $dto = new LoginDTO(
            email: 'user@test.com',
            password: 'secret123',
        );

        $this->expectException(\Error::class);
        $dto->email = 'changed@test.com';
    }

    public function test_register_dto_with_empty_name(): void
    {
        $dto = new RegisterDTO(
            name: '',
            email: 'user@test.com',
            password: 'pass1234',
        );

        $this->assertEquals('', $dto->name);
    }

    public function test_login_dto_with_long_password(): void
    {
        $longPassword = str_repeat('a', 1000);
        $dto = new LoginDTO(
            email: 'user@test.com',
            password: $longPassword,
        );

        $this->assertEquals($longPassword, $dto->password);
    }

    public function test_register_dto_with_special_characters_in_email(): void
    {
        $dto = new RegisterDTO(
            name: 'User',
            email: 'user+tag@sub.domain.com',
            password: 'pass1234',
        );

        $this->assertEquals('user+tag@sub.domain.com', $dto->email);
    }
}
