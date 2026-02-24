<?php

namespace App\Exceptions;

use Exception;

class ConcurrentSessionException extends Exception
{
    public const CODE = 'CONCURRENT_SESSION';

    public function __construct(string $message = 'O usuário já possui uma sessão ativa.')
    {
        parent::__construct($message);
    }
}
