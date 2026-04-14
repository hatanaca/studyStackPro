<?php

namespace App\Exceptions\Domain;

use App\Exceptions\ApiException;
use Symfony\Component\HttpFoundation\Response;

class TechnologyNotFoundException extends ApiException
{
    public function __construct(string $id = '')
    {
        parent::__construct(
            message: $id ? "Tecnologia {$id} não encontrada." : 'Tecnologia não encontrada.',
            statusCode: Response::HTTP_NOT_FOUND,
            errorCode: 'TECHNOLOGY_NOT_FOUND',
        );
    }
}
