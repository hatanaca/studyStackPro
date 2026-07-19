<?php

namespace App\Exceptions;

use App\Exceptions\Domain\ConcurrentSessionException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;
use Sentry\Laravel\Integration;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * Exceções 4xx que são comportamento normal — não devem poluir os logs de erro.
     */
    protected $dontReport = [
        AuthenticationException::class,
        AuthorizationException::class,
        ModelNotFoundException::class,
        NotFoundHttpException::class,
        TooManyRequestsHttpException::class,
        MethodNotAllowedHttpException::class,
        ValidationException::class,
    ];

    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            if (app()->bound('sentry')) {
                Integration::captureUnhandledException($e);
            }
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
                $e instanceof ConcurrentSessionException => response()->json([
                    'success' => false,
                    'error' => ['code' => ConcurrentSessionException::CODE, 'message' => $e->getMessage()],
                ], 409),
                $e instanceof ApiException => response()->json([
                    'success' => false,
                    'error' => ['code' => $e->errorCode, 'message' => $e->getMessage()],
                ], $e->statusCode),
                $e instanceof QueryException && $this->isConcurrentSessionQueryException($e) => response()->json([
                    'success' => false,
                    'error' => ['code' => ConcurrentSessionException::CODE, 'message' => 'O usuário já possui uma sessão ativa.'],
                ], 409),
                $e instanceof TooManyRequestsHttpException => response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'RATE_LIMITED',
                        'message' => 'Muitas requisições.',
                        'retry_after' => (int) ($e->getHeaders()['Retry-After'] ?? $e->getHeaders()['retry-after'] ?? 60),
                    ],
                ], 429),
                $e instanceof MethodNotAllowedHttpException => response()->json([
                    'success' => false,
                    'error' => ['code' => 'METHOD_NOT_ALLOWED', 'message' => 'Método não permitido.'],
                ], 405),
                $e instanceof NotFoundHttpException => response()->json([
                    'success' => false,
                    'error' => ['code' => 'NOT_FOUND', 'message' => 'Rota não encontrada.'],
                ], 404),
                $e instanceof HttpException => response()->json([
                    'success' => false,
                    'error' => ['code' => 'HTTP_ERROR', 'message' => $e->getMessage() ?: 'Erro HTTP.'],
                ], $e->getStatusCode()),
                default => response()->json([
                    'success' => false,
                    'error' => ['code' => 'INTERNAL_ERROR', 'message' => 'Erro interno.'],
                ], 500),
            };
        }

        return parent::render($request, $e);
    }

    /**
     * Detecta se a QueryException é de sessão ativa duplicada via SQLSTATE do Postgres (P0001 = raise_exception).
     */
    private function isConcurrentSessionQueryException(QueryException $e): bool
    {
        $state = (string) ($e->errorInfo[0] ?? '');

        return $state === 'P0001';
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
