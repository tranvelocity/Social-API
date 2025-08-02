<?php

namespace Modules\Core\app\Responses;

use Illuminate\Http\JsonResponse;
use Modules\Core\app\Constants\StatusCodeConstant;

class NoContentResponse
{
    /**
     * Create a JSON response with a 204 status code and optional additional data.
     *
     * @param array $additionalData
     * @return JsonResponse
     */
    public static function make(array $additionalData = []): JsonResponse
    {
        return response()->json($additionalData, StatusCodeConstant::STATUS_CODE_NO_CONTENT);
    }
}
