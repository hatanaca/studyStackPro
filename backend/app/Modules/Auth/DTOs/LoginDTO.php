<?php

namespace App\Modules\Auth\DTOs;

/**
 * DTO de login.
 *
 * Email, senha e flag remember (para sessões persistentes).
 */
final readonly class LoginDTO
{
    public function __construct(
        public string $email,
        public string $password,
        public bool $remember = false,
    ) {}
}
