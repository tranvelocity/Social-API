<?php

namespace Modules\Core\app\Exceptions;

use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;

class Exception extends HttpResponseException
{
    public function __construct(int $statusCode = 0, array $errors)
    {
        $response = new JsonResponse([
            'success' => false,
            'code' => $statusCode,
            'errors' => $errors,
        ], $statusCode);

        parent::__construct($response);
    }
}
