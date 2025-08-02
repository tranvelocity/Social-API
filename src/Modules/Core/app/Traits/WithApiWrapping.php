<?php

namespace Modules\Core\app\Traits;

use Illuminate\Http\Request;
use Modules\Core\app\Constants\StatusCodeConstant;

trait WithApiWrapping
{
    /**
     * Defines the structure of the success response array.
     *
     * @param Request $request
     * @return array
     */
    public function with(Request $request)
    {
        $response = [
            'success' => true,
            'code' => $this->getStatusCode($request),
        ];

        if ($this->isGetMethod($request) && $this->shouldSetLimitOffset()) {
            $response['pagination'] = $this->getPaginationInfo();
        }

        return $response;
    }

    /**
     * Check if the request method is GET.
     *
     * @param Request $request
     * @return bool
     */
    protected function isGetMethod(Request $request): bool
    {
        return strtoupper($this->getMethod($request)) === 'GET';
    }

    /**
     * Retrieves the HTTP status code for the response.
     *
     * @param Request $request
     * @return int
     */
    protected function getStatusCode(Request $request)
    {
        return match ($this->getMethod($request)) {
            'POST' => StatusCodeConstant::STATUS_CODE_CREATED,
            'DELETE' => StatusCodeConstant::STATUS_CODE_NO_CONTENT,
            default => StatusCodeConstant::STATUS_CODE_OK
        };
    }

    private function getMethod(Request $request)
    {
        return strtoupper($request->getMethod());
    }

    /**
     * Retrieves the pagination information if applicable.
     *
     * @return array|null
     */
    protected function getPaginationInfo(): ?array
    {
        return $this->shouldSetLimitOffset()
            ? ['offset' => $this->offset, 'limit' => $this->limit, 'total' => $this->total]
            : null;
    }

    /**
     * Checks if pagination properties should be included in the response format.
     *
     * @return bool
     */
    protected function shouldSetLimitOffset(): bool
    {
        return $this->hasLimitOrOffset();
    }

    /**
     * Check the field exist or not.
     *
     * @return bool
     */
    private function hasLimitOrOffset(): bool
    {
        return $this->hasValidValue('limit') || $this->hasValidValue('offset');
    }

    /**
     * Check the value exist or not.
     *
     * @return bool
     */
    private function hasValidValue($value): bool
    {
        return isset($this->$value) && !is_null($this->$value);
    }
}
