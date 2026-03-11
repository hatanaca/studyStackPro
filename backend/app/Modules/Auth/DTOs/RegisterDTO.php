<?php

namespace App\Modules\Auth\DTOs;

/**
 * DTO de registro de usuário.
 *
 * Dados validados para criação de conta (name, email, password, timezone).
 */
final readonly class RegisterDTO
{
    public function __construct(
        public string $name,
        public string $email,
        public string $password,
        public string $timezone = 'UTC',
    ) {}
}
