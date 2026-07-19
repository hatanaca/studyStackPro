<?php

namespace App\Modules\LinkedIn\DTOs;

/**
 * DTO para publicação de post no LinkedIn.
 *
 * text: conteúdo do post (max 3000 caracteres).
 */
final readonly class LinkedInPostDTO
{
    public function __construct(
        public string $text,
    ) {}
}
