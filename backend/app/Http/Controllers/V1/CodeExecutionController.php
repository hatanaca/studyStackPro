<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CodeExecution\ExecuteCodeRequest;
use App\Modules\CodeExecution\DTOs\CodeExecutionDTO;
use App\Modules\CodeExecution\Services\CodeExecutionService;
use App\Traits\HasApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * Controller para execução de código no mini terminal.
 *
 * Executa código de forma isolada: client-side (JS, Lua, HTML, CSS)
 * ou backend-side (PHP, SQL, Laravel, Bash) via Docker sandbox.
 */
class CodeExecutionController extends Controller
{
    use HasApiResponse;

    public function __construct(
        private readonly CodeExecutionService $codeExecution,
    ) {}

    /**
     * POST /api/v1/code/execute
     *
     * Executa código e retorna o resultado.
     */
    public function execute(ExecuteCodeRequest $request): JsonResponse
    {
        $dto = new CodeExecutionDTO(
            code: $request->validated('code'),
            language: $request->validated('language'),
            userId: $request->user()->id,
        );

        try {
            $result = $this->codeExecution->execute($dto->code, $dto->language);

            return $this->success($result);
        } catch (\Throwable $e) {
            Log::error('Code execution failed', [
                'user_id' => $request->user()->id,
                'language' => $dto->language,
                'exception' => $e,
            ]);

            return $this->error(
                'Falha ao executar código.',
                'EXECUTION_ERROR',
                null,
                500
            );
        }
    }

    /**
     * GET /api/v1/code/languages
     *
     * Retorna lista de linguagens suportadas.
     */
    public function languages(): JsonResponse
    {
        $languages = collect($this->codeExecution->supportedLanguages())
            ->map(fn (string $lang) => [
                'name' => $lang,
                'label' => match ($lang) {
                    'javascript' => 'JavaScript',
                    'php' => 'PHP',
                    'lua' => 'Lua',
                    'html' => 'HTML',
                    'css' => 'CSS',
                    'sql' => 'SQL',
                    'laravel' => 'Laravel',
                    'bash' => 'Bash',
                    default => $lang,
                },
                'executor' => in_array($lang, ['javascript', 'lua', 'html', 'css'], true) ? 'client' : 'backend',
            ])
            ->values();

        return $this->success($languages);
    }
}
