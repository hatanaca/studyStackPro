<?php

namespace App\Modules\Canvas\DTOs;

final readonly class CanvasArtworkDTO
{
    public function __construct(
        public string $userId,
        public string $title = 'Sem título',
        public ?array $canvasData = null,
        public ?array $muralItems = null,
        public int $width = 800,
        public int $height = 600,
        public string $bgColor = '#ffffff',
    ) {}
}
