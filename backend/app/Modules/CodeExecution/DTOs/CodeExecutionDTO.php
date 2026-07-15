<?php

namespace App\Modules\CodeExecution\DTOs;

/**
 * DTO para execução de código no terminal.
 *
 * code: código fonte a ser executado.
 * language: linguagem de programação.
 * userId: ID do usuário que está executando.
 */
final readonly class CodeExecutionDTO
{
    public function __construct(
        public string $code,
        public string $language,
        public string $userId,
    ) {}
}
