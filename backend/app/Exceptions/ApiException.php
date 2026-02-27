<?php

namespace App\Exceptions;

use Symfony\Component\HttpFoundation\Response;

abstract class ApiException extends \Exception
{
    public function __construct(
        string $message = 'Erro na API',
        public readonly int $statusCode = Response::HTTP_INTERNAL_SERVER_ERROR,
        public readonly string $code = 'API_ERROR',
    ) {
        parent::__construct($message);
    }
}
