<?php

namespace App\Exceptions\Domain;

use App\Exceptions\ApiException;
use Symfony\Component\HttpFoundation\Response;

class MetricsCalculationException extends ApiException
{
    public function __construct(string $message = 'Erro ao calcular métricas.')
    {
        parent::__construct(
            message: $message,
            statusCode: Response::HTTP_INTERNAL_SERVER_ERROR,
            code: 'METRICS_CALCULATION_ERROR',
        );
    }
}
