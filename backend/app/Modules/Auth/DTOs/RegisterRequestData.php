<?php

namespace App\Modules\Auth\DTOs;

readonly final class RegisterRequestData
{
    public function __construct(
        public string $name,
        public string $email,
        public string $password,
        public string $timezone = 'UTC',
    ) {}
}
