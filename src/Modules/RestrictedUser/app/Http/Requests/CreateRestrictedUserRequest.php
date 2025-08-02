<?php

namespace Modules\RestrictedUser\app\Http\Requests;

use Modules\Core\app\Http\Requests\JsonRequest;

class CreateRestrictedUserRequest extends JsonRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'user_id' => ['integer', 'required', 'gt:0'],
            'remarks' => ['string', 'nullable'],
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
