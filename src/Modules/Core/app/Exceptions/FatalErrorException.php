<?php

declare(strict_types=1);

namespace Modules\Core\app\Exceptions;

use Illuminate\Support\Facades\Config;
use Modules\Core\app\Constants\StatusCodeConstant;

class FatalErrorException extends Exception
{
    public function __construct($code = null, $message = null)
    {
        $statusCode = StatusCodeConstant::STATUS_CODE_INTERNAL_SERVER_ERROR;

        if (is_null($code)) {
            $code = StatusCodeConstant::INTERNAL_SERVER_ERROR_DEFAULT_CODE;
        }

        $message = $message ?? Config::get("auth.exceptions.{$statusCode}.{$code}", 'Internal Server Error.');

        $errors = [
            'message' => $message,
            'code' => $code,
        ];

        parent::__construct($statusCode, $errors);
    }
}
