<?php

namespace Modules\Core\app\Exceptions;

use Illuminate\Support\Facades\Config;
use Modules\Core\app\Constants\StatusCodeConstant;

class UnprocessableEntityException extends Exception
{
    public function __construct($message = null, $code = null)
    {
        $statusCode = StatusCodeConstant::STATUS_CODE_UNPROCESSABLE_ENTITY;

        if (is_null($code)) {
            $code = StatusCodeConstant::UNPROCESSABLE_ENTITY_DEFAULT_CODE;
        }

        $message = $message ?? Config::get("auth.exceptions.{$statusCode}.{$code}", __('Unprocessable Content.'));

        parent::__construct($statusCode, ['message' => $message, 'code' => $code]);
    }
}
