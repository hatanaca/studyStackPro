<?php

namespace App\Modules\CodeExecution\Services;

use App\Modules\CodeExecution\DTOs\ExecutionResultDTO;
use App\Modules\CodeExecution\Exceptions\SandboxExecutionException;

class CodeExecutionService
{
    public function __construct(
        private DockerSandboxService $sandboxService
    ) {}

    public function execute(string $code, string $language): ExecutionResultDTO|array
    {
        $language = strtolower(trim($language));
        if (! in_array($language, $this->supportedLanguages(), true)) {
            throw new SandboxExecutionException("Linguagem '{$language}' não é suportada.");
        }

        // Linguagens client-side (JS, Lua, HTML, CSS) rodam no navegador, não no sandbox
        $clientSide = ['javascript', 'lua', 'html', 'css'];
        if (in_array($language, $clientSide, true)) {
            return ['executor' => 'client', 'language' => $language, 'code' => $code];
        }

        $result = $this->sandboxService->run($code, $language);

        return new ExecutionResultDTO(
            success: $result['success'],
            output: $result['output'],
            error: $result['error'],
            executionTime: $result['executionTime']
        );
    }

    public function supportedLanguages(): array
    {
        return ['javascript', 'php', 'lua', 'html', 'css', 'sql', 'laravel', 'bash'];
    }

    /**
     * Valida se o código e a linguagem são aceitáveis para execução.
     *
     * @param  string  $code  Código fonte.
     * @param  string  $language  Linguagem alvo.
     * @return bool True se código e linguagem são válidos.
     */
    public function validate(string $code, string $language): bool
    {
        if (trim($code) === '') {
            return false;
        }
        if (! in_array(strtolower(trim($language)), $this->supportedLanguages(), true)) {
            return false;
        }
        if (strlen($code) > 10000) {
            return false;
        }

        return true;
    }
}
