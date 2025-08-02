<?php

namespace Modules\Core\app\Exceptions;

use Illuminate\Support\Facades\Config;
use Modules\Core\app\Constants\StatusCodeConstant;

class ForbiddenException extends Exception
{
    public function __construct($message = null, $code = null)
    {
        $statusCode = StatusCodeConstant::STATUS_CODE_FORBIDDEN;

        if (is_null($code)) {
            $code = StatusCodeConstant::FORBIDDEN_PERMISSION_DENIED;
        }

        $message = $message ?? Config::get("auth.exceptions.{$statusCode}.{$code}", __('Permission Denied.'));

        parent::__construct($statusCode, ['message' => $message, 'code' => $code]);
    }
}
