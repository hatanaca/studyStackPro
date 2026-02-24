<?php

namespace App\Modules\Technologies\DTOs;

readonly final class TechnologyDTO
{
    public function __construct(
        public string $userId,
        public string $name,
        public ?string $color = null,
        public ?string $icon = null,
        public ?string $description = null,
    ) {}
}
