<?php

namespace App\Modules\Auth\DTOs;

final readonly class LoginDTO
{
    public function __construct(
        public string $email,
        public string $password,
        public bool $remember = false,
    ) {}
}
