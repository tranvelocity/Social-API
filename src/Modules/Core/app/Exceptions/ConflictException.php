<?php

namespace Modules\Core\app\Exceptions;

use Illuminate\Support\Facades\Config;
use Modules\Core\app\Constants\StatusCodeConstant;

class ConflictException extends Exception
{
    public function __construct($message = null, $code = null)
    {
        $statusCode = StatusCodeConstant::STATUS_CODE_CONFLICT;

        if (is_null($code)) {
            $code = StatusCodeConstant::RESOURCE_CONFLICT;
        }

        $message = $message ?? Config::get("auth.exceptions.{$statusCode}.{$code}", __('Resource Conflict.'));

        parent::__construct($statusCode, ['message' => $message, 'code' => $code]);
    }
}
