<?php

namespace Modules\ThrottleConfig\app\Http\Requests;

use Modules\Core\app\Http\Requests\JsonRequest;

class CreateThrottleConfigRequest extends JsonRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'time_frame_minutes' => ['required', 'integer', 'gt:0'],
            'max_comments' => ['required', 'integer', 'gt:0'],
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
