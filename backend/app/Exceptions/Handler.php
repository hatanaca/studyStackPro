<?php

namespace App\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\MissingAttributeException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    public function render($request, Throwable $e)
    {
        if ($request->expectsJson()) {
            return match (true) {
                $e instanceof ValidationException => $this->validationError($e),
                $e instanceof AuthenticationException => response()->json([
                    'success' => false,
                    'error' => ['code' => 'UNAUTHENTICATED', 'message' => 'Credenciais inválidas ou token expirado.'],
                ], 401),
                $e instanceof AuthorizationException => response()->json([
                    'success' => false,
                    'error' => ['code' => 'FORBIDDEN', 'message' => 'Acesso negado.'],
                ], 403),
                $e instanceof ModelNotFoundException => response()->json([
                    'success' => false,
                    'error' => ['code' => 'NOT_FOUND', 'message' => 'Recurso não encontrado.'],
                ], 404),
                $e instanceof \App\Exceptions\Domain\ConcurrentSessionException => response()->json([
                    'success' => false,
                    'error' => ['code' => \App\Exceptions\Domain\ConcurrentSessionException::CODE, 'message' => $e->getMessage()],
                ], 409),
                $e instanceof \App\Exceptions\ApiException => response()->json([
                    'success' => false,
                    'error' => ['code' => $e->errorCode, 'message' => $e->getMessage()],
                ], $e->statusCode),
                $e instanceof QueryException && str_contains($e->getMessage(), 'sessão ativa') => response()->json([
                    'success' => false,
                    'error' => ['code' => \App\Exceptions\Domain\ConcurrentSessionException::CODE, 'message' => 'O usuário já possui uma sessão ativa.'],
                ], 409),
                $e instanceof MissingAttributeException && $this->isMissingStudySessionTitleAttribute($e) => $this->schemaOutdatedStudySessionsTitleResponse($e),
                $e instanceof QueryException && $this->isMissingStudySessionsTitleColumn($e) => $this->schemaOutdatedStudySessionsTitleResponse($e),
                $e instanceof TooManyRequestsHttpException => response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'RATE_LIMITED',
                        'message' => 'Muitas requisições.',
                        'retry_after' => (int) ($e->getHeaders()['Retry-After'] ?? $e->getHeaders()['retry-after'] ?? 60),
                    ],
                ], 429),
                default => response()->json([
                    'success' => false,
                    'error' => ['code' => 'INTERNAL_ERROR', 'message' => config('app.debug') ? $e->getMessage() : 'Erro interno.'],
                ], 500),
            };
        }

        return parent::render($request, $e);
    }

    /**
     * Postgres 42703 / texto típico quando a migração `add_title_to_study_sessions_table` não foi aplicada.
     */
    private function isMissingStudySessionsTitleColumn(QueryException $e): bool
    {
        $m = $e->getMessage();
        if (! str_contains($m, 'title')) {
            return false;
        }

        // Postgres: 42703 undefined_column — a mensagem nem sempre repete o nome da tabela.
        $state = (string) ($e->errorInfo[0] ?? '');
        if ($state === '42703' || $state === '42S22') {
            return true;
        }

        return str_contains($m, 'study_sessions')
            || str_contains($m, 'does not exist')
            || str_contains($m, 'Undefined column')
            || str_contains($m, 'Unknown column');
    }

    private function isMissingStudySessionTitleAttribute(MissingAttributeException $e): bool
    {
        $m = $e->getMessage();

        return str_contains($m, '[title]') && str_contains($m, 'StudySession');
    }

    /**
     * Resposta JSON quando a coluna `title` de `study_sessions` ainda não existe (migração pendente).
     * Registo apenas via canal de log da app — evita escrita em paths arbitrários fora do projeto.
     */
    private function schemaOutdatedStudySessionsTitleResponse(Throwable $e): \Illuminate\Http\JsonResponse
    {
        Log::notice('Schema study_sessions: coluna title em falta ou modelo desatualizado.', [
            'exception' => $e::class,
            'sql_state' => $e instanceof QueryException ? ($e->errorInfo[0] ?? null) : null,
        ]);

        return response()->json([
            'success' => false,
            'error' => [
                'code' => 'SCHEMA_OUTDATED',
                'message' => 'A tabela study_sessions ainda não tem a coluna title. Na pasta do backend execute: php artisan migrate (ou com Docker: docker compose exec php-fpm php artisan migrate).',
            ],
        ], 503);
    }

    protected function validationError(ValidationException $e)
    {
        return response()->json([
            'success' => false,
            'error' => [
                'code' => 'VALIDATION_ERROR',
                'message' => $e->getMessage(),
                'details' => $e->errors(),
            ],
        ], 422);
    }

    protected function unauthenticated($request, AuthenticationException $exception)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'UNAUTHENTICATED', 'message' => 'Credenciais inválidas ou token expirado.'],
            ], 401);
        }

        return redirect()->guest($exception->redirectTo($request) ?? route('login'));
    }
}
