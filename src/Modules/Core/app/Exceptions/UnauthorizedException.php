<?php

declare(strict_types=1);

namespace Modules\Core\app\Exceptions;

use Illuminate\Support\Facades\Config;
use Modules\Core\app\Constants\StatusCodeConstant;

class UnauthorizedException extends Exception
{
    public function __construct($code = null, $message = null)
    {
        $statusCode = StatusCodeConstant::STATUS_CODE_UNAUTHORIZED;

        if (is_null($code)) {
            $code = StatusCodeConstant::UNAUTHORIZED_DEFAULT_CODE;
        }

        $message = $message ?? Config::get("auth.exceptions.{$statusCode}.{$code}", 'Authentication failed!');

        $errors = [
            'message' => $message,
            'code' => $code,
        ];

        parent::__construct($statusCode, $errors);
    }
}
