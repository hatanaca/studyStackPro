<?php

namespace App\Exceptions\Domain;

use App\Exceptions\ApiException;
use Symfony\Component\HttpFoundation\Response;

class ConcurrentSessionException extends ApiException
{
    public const CODE = 'CONCURRENT_SESSION';

    public function __construct(string $message = 'O usuário já possui uma sessão ativa.')
    {
        parent::__construct(
            message: $message,
            statusCode: Response::HTTP_CONFLICT,
            errorCode: self::CODE,
        );
    }
}
