<?php

declare(strict_types=1);

namespace Modules\Core\app\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Modules\Core\app\Constants\ResourceConstant;

class JsonRequest extends FormRequest
{
    /**
     * @inheritdoc
     */
    public function messages()
    {
        return [
            'accepted' => "accepted|The :attribute must be accepted.",
            'active_url' => "active_url|The :attribute is not a valid URL.",
            'alpha' => "alpha|The :attribute may only contain letters.",
            'alpha_dash' => "alpha_dash|The :attribute may only contain letters, numbers, and dashes.",
            'alpha_num' => "alpha_num|The :attribute may only contain letters and numbers.",
            'array' => "array|The :attribute must be an array.",
            'before' => "before|The :attribute must be before :date.",
            'after' => "after|The :attribute must be after :date.",
            'between' => "between|The :attribute must be between :min and :max characters.",
            'boolean' => "boolean|The :attribute field must be true or false.",
            'confirmed' => "confirmed|The :attribute confirmation does not match.",
            'date' => "date|The :attribute is not a valid date.",
            'date_format' => "date_format|The :attribute does not match the format :format.",
            'different' => "different|The :attribute and :other must be different.",
            'digits' => "digits|The :attribute must be :digits digits.",
            'digits_between' => "digits_between|The :attribute must be between :min and :max digits.",
            'email' => "email|The :attribute must be a valid email address.",
            'image' => "image|The :attribute must be an image.",
            'in' => "in|The selected :attribute is invalid.",
            'integer' => "integer|The :attribute must be an integer.",
            'ip' => "ip|The :attribute must be a valid IP address.",
            'json' => "json|The :attribute must be a valid JSON string.",
            'max' => "max|The :attribute may not be greater than :max characters.",
            'min' => "min|The :attribute must be at least :min characters.",
            'mimes' => "mimes|The :attribute must be a file of type :values.",
            'not_in' => "not_in|The selected :attribute is invalid.",
            'numeric' => "numeric|The :attribute must be a number.",
            'regex' => "regex|The :attribute format is invalid.",
            'required' => "required|The :attribute field is required.",
            'required_if' => "required_if|The :attribute field is required when :other is :value.",
            'required_unless' => "required_unless|The :attribute field is required unless :other is in :values.",
            'required_with' => "required_with|The :attribute field is required when :values are present.",
            'required_with_all' => "required_with_all|The :attribute field is required when :values are present.",
            'required_without' => "required_without|The :attribute field is required when :values are not present.",
            'required_without_all' => "required_without_all|The :attribute field is required when none of :values are present.",
            'same' => "same|The :attribute and :other must match.",
            'size' => "size|The :attribute must be :size characters.",
            'string' => "string|The :attribute must be a string.",
            'timezone' => "timezone|The :attribute must be a valid time zone.",
            'unique' => "unique|The :attribute has already been taken.",
            'url' => "url|The :attribute format is invalid.",
            'source' => "source|The :attribute must be a file of type mp3, ogg, m4a, mpeg.",
            'is_base64' => "is_base64|The :attribute must be a valid base64 string.",
            'exists' => "exists|The selected :attribute is invalid.",
            'gt' => "gt|The :attribute must be a positive integer.",
        ];
    }

    /**
     * Get data for validation from the JSON request.
     *
     * @return array
     */
    public function validation(): array
    {
        $validParams = $this->validated();

        // Add limit and offset to $params array if the request is a GET method
        if ($this->isGetRequest()) {
            return array_merge($validParams, $this->getLimitOffset());
        }

        return $validParams;
    }

    /**
     * Get limit and offset values with default configurations.
     *
     * @return array
     */
    private function getLimitOffset(): array
    {
        $limitMaximum = config("api.pagination.max_limit") ?? ResourceConstant::MAX_LIMIT_DEFAULT;
        $limitDefault = config("api.pagination.limit") ?? ResourceConstant::LIMIT_DEFAULT;
        $offsetDefault = config("api.pagination.offset") ?? ResourceConstant::OFFSET_DEFAULT;

        // Ensure that the limit is always at least 1.
        $limit = max(1, min((int) Request::get('limit', $limitDefault), $limitMaximum));
        $offset = (int) Request::get('offset', $offsetDefault);

        return [
            'limit' => $limit,
            'offset' => $offset
        ];
    }

    /**
     * Check if the request method is GET.
     *
     * @return bool
     */
    private function isGetRequest(): bool
    {
        return strtoupper($this->getMethod()) === 'GET';
    }
}
