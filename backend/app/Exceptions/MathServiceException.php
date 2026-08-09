<?php

namespace App\Exceptions;

/**
 * Falha ao falar com o math-service (FastAPI + SymPy).
 * Mapeado pelo Handler para { success:false, error:{ code, message } } com o statusCode.
 */
class MathServiceException extends ApiException
{
    public function __construct(string $message = 'Falha no motor matemático.', int $statusCode = 502)
    {
        parent::__construct($message, $statusCode, 'MATH_SERVICE_ERROR');
    }
}
