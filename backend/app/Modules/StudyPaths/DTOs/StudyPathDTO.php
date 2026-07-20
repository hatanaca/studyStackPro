<?php

namespace App\Modules\StudyPaths\DTOs;

final readonly class StudyPathDTO
{
    public function __construct(
        public string $userId,
        public string $title = 'Mapa de Estudo',
        public ?string $technologyId = null,
        public ?array $nodes = null,
        public ?array $edges = null,
    ) {}
}
