<?php

namespace Modules\RestrictedUser\app\Http\Requests;

use Modules\Core\app\Http\Requests\JsonRequest;

class SearchRestrictedUserRequest extends JsonRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'user_id' => ['integer', 'nullable', 'gt:0'],
        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }
}
