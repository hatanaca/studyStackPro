<?php

namespace App\Modules\Auth\DTOs;

readonly final class LoginRequestData
{
    public function __construct(
        public string $email,
        public string $password,
        public bool $remember = false,
    ) {}
}
