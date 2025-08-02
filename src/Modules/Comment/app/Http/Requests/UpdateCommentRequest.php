<?php

namespace Modules\Comment\app\Http\Requests;

use Modules\Core\app\Http\Requests\JsonRequest;

class UpdateCommentRequest extends JsonRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'comment' => ['nullable', 'string'],
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
