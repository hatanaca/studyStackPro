<?php

namespace App\Modules\CodeExecution\DTOs;

/**
 * DTO para resultado da execução de código.
 *
 * success: true se executou com sucesso, false caso contrário.
 * output: saída textual da execução.
 * error: mensagem de erro, se houver.
 * executionTime: tempo de execução em milissegundos.
 */
final readonly class ExecutionResultDTO
{
    public function __construct(
        public bool $success,
        public string $output,
        public ?string $error,
        public int $executionTime,
    ) {}
}
