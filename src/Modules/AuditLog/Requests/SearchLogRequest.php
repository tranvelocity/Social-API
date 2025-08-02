<?php

namespace Tranauth\Laravel\Api\AuditLog\Requests;

use Tranauth\Laravel\Api\Requests\ApiRequest;

class SearchLogRequest extends ApiRequest
{
    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        return [
            'log' => ['max:255', 'nullable'],
            'creation_from' => ['nullable', 'date', 'date_format:Y-m-d H:i:s'],
            'creation_to' => ['nullable', 'date', 'date_format:Y-m-d H:i:s'],
            'offset' => ['integer', 'nullable', 'min:' . ApiRequest::DEFAULT_OFFSET],
            'limit' => ['integer', 'nullable', 'min:' . ApiRequest::DEFAULT_LIMIT]
        ];
    }

    /**
     * Converts the request to a request array.
     *
     * @return array
     */
    public function getValidatedParams(): array
    {
        $params = $this->validated();

        return $this->castLimitOffsetParamsWithDefault($params);
    }
}
