<?php

namespace App\Modules\Auth\DTOs;

final readonly class RegisterDTO
{
    public function __construct(
        public string $name,
        public string $email,
        public string $password,
        public string $timezone = 'UTC',
    ) {}
}
