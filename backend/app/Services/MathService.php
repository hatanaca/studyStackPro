<?php

namespace App\Services;

use App\Exceptions\MathServiceException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Cliente HTTP do math-service (FastAPI + SymPy), usado para corrigir
 * respostas e gerar variantes de exercícios. Serviço interno, rede Docker.
 */
class MathService
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly string $token,
    ) {}

    /**
     * Corrige uma resposta (numeric | symbolic).
     *
     * @param  string[]  $variables
     * @return array{correct: bool, student_latex: string, expected_latex: string, feedback: string}
     *
     * @throws MathServiceException
     */
    public function grade(
        string $mode,
        string $studentAnswer,
        string $expectedExpression,
        array $variables = [],
        float $tolerance = 1e-6,
    ): array {
        return $this->post('/grade', [
            'mode' => $mode,
            'student_answer' => $studentAnswer,
            'expected_expression' => $expectedExpression,
            'variables' => $variables,
            'tolerance' => $tolerance,
        ]);
    }

    /**
     * Gera uma variante a partir do template + spec de parâmetros.
     *
     * @return array{parameters: array, prompt: string, answer_expr: string, answer_latex: string}
     *
     * @throws MathServiceException
     */
    public function generate(
        string $template,
        string $answerExpression,
        array $parametersSpec,
        ?int $seed = null,
    ): array {
        return $this->post('/generate', [
            'template' => $template,
            'answer_expression' => $answerExpression,
            'parameters_spec' => $parametersSpec,
            'seed' => $seed,
        ]);
    }

    /**
     * Resolve uma equação para a variável informada.
     *
     * @return array{solutions: string[], solution_latex: string}
     *
     * @throws MathServiceException
     */
    public function solve(string $expression, string $variable): array
    {
        return $this->post('/solve', [
            'expression' => $expression,
            'variable' => $variable,
        ]);
    }

    /**
     * Avalia uma expressão após substituir as variáveis.
     * Expressões booleanas (ex.: Eq(a, b)) retornam result "True"|"False".
     *
     * @param  array<string, int|float|string>  $variables
     * @return array{result: string, latex: string}
     *
     * @throws MathServiceException
     */
    public function evaluate(string $expression, array $variables = []): array
    {
        return $this->post('/evaluate', [
            'expression' => $expression,
            'variables' => $variables,
        ]);
    }

    /** @return array<string, mixed> */
    private function post(string $path, array $payload): array
    {
        try {
            $response = Http::timeout(5)
                ->withHeaders(['X-Math-Token' => $this->token])
                ->post(rtrim($this->baseUrl, '/').$path, $payload);
        } catch (ConnectionException) {
            throw new MathServiceException('Motor matemático indisponível.', 503);
        }

        if ($response->failed()) {
            Log::warning('Math service request failed', [
                'path' => $path,
                'status' => $response->status(),
                'body' => mb_substr((string) $response->body(), 0, 500),
            ]);
            throw new MathServiceException('Falha no motor matemático.', 502);
        }

        return $response->json();
    }
}
