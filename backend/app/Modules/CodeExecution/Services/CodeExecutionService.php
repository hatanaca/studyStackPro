<?php

namespace App\Modules\CodeExecution\Services;

use App\Modules\CodeExecution\DTOs\CodeExecutionDTO;

/**
 * Serviço de execução de código.
 *
 * Orquestra a execução: valida o código, despacha para client (JS/Lua)
 * ou backend (Docker sandbox) conforme a linguagem.
 */
class CodeExecutionService
{
    /** Linguagens que rodam no browser (client-side). */
    private const CLIENT_LANGUAGES = ['javascript', 'lua', 'html', 'css'];

    /** Máximo de caracteres no código. */
    private const MAX_CODE_LENGTH = 10000;

    public function __construct(
        private readonly DockerSandboxService $sandbox,
    ) {}

    /**
     * Retorna lista de linguagens suportadas.
     *
     * @return string[]
     */
    public function supportedLanguages(): array
    {
        return ['javascript', 'php', 'lua', 'html', 'css', 'sql', 'laravel', 'bash'];
    }

    /**
     * Valida se o código e a linguagem são aceitáveis.
     */
    public function validate(string $code, string $language): bool
    {
        if (blank($code) || strlen($code) > self::MAX_CODE_LENGTH) {
            return false;
        }

        return in_array($language, $this->supportedLanguages(), true);
    }

    /**
     * Executa código — despacha para client ou backend.
     *
     * @return array{success: bool, output: string, error: string|null, executionTime: int, executor: string, language: string}
     */
    public function execute(CodeExecutionDTO $dto): array
    {
        if (! $this->validate($dto->code, $dto->language)) {
            return [
                'success' => false,
                'output' => '',
                'error' => 'Código inválido ou linguagem não suportada.',
                'executionTime' => 0,
                'executor' => 'none',
                'language' => $dto->language,
            ];
        }

        // Linguagens client-side: retorna instrução para executar no browser
        if (in_array($dto->language, self::CLIENT_LANGUAGES, true)) {
            return [
                'success' => true,
                'output' => '',
                'error' => null,
                'executionTime' => 0,
                'executor' => 'client',
                'language' => $dto->language,
            ];
        }

        // Backend: executa via Docker sandbox
        $result = $this->sandbox->run($dto->code, $dto->language);

        return array_merge($result, [
            'executor' => 'backend',
            'language' => $dto->language,
        ]);
    }
}
