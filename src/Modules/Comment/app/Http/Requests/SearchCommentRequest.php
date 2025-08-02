<?php

namespace Modules\Comment\app\Http\Requests;

use Modules\Core\app\Http\Requests\JsonRequest;

class SearchCommentRequest extends JsonRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'comment' => ['nullable', 'string'],
            'is_hidden' => ['nullable', 'boolean'],
            'user_id' => ['nullable', 'integer'],
            'last_id' => ['nullable', 'string'],
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
