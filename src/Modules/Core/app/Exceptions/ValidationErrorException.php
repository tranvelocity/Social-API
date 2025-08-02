<?php

namespace Modules\Core\app\Exceptions;

use Illuminate\Support\Facades\Config;
use Modules\Core\app\Constants\StatusCodeConstant;

class ValidationErrorException extends Exception
{
    public function __construct($code = null, $message = null)
    {
        $statusCode = StatusCodeConstant::STATUS_CODE_BAD_REQUEST;

        if (is_null($code)) {
            $code = StatusCodeConstant::BAD_REQUEST_VALIDATION_FAILED_CODE;
        }

        $message = $message ?? Config::get("auth.exceptions.{$statusCode}.{$code}", 'Request validation failed.');

        $errors = [
            'message' => $message,
            'code' => $code,
        ];

        parent::__construct($statusCode, $errors);
    }
}
