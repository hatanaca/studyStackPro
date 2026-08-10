<?php

namespace App\Exceptions\Domain;

use App\Exceptions\ApiException;
use Symfony\Component\HttpFoundation\Response;

class StudyQuestionVariantNotFoundException extends ApiException
{
    public function __construct()
    {
        parent::__construct(
            message: 'Variant não encontrada.',
            statusCode: Response::HTTP_UNPROCESSABLE_ENTITY,
            errorCode: 'VARIANT_NOT_FOUND',
        );
    }
}
