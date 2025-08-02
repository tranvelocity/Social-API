<?php

namespace Modules\Core\app\Exceptions;

use Illuminate\Support\Facades\Config;
use Modules\Core\app\Constants\StatusCodeConstant;

class ResourceNotFoundException extends Exception
{
    public function __construct($message = null, $code = null)
    {
        $statusCode = StatusCodeConstant::STATUS_CODE_NOT_FOUND;

        if (is_null($code)) {
            $code = StatusCodeConstant::RESOURCE_NOT_FOUND;
        }

        $message = $message ?? Config::get("auth.exceptions.{$statusCode}.{$code}", __('No resource was found.'));

        parent::__construct($statusCode, ['message' => $message, 'code' => $code]);
    }
}
