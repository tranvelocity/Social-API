<?php

namespace Modules\Post\app\Http\Requests;

use Modules\Core\app\Http\Requests\JsonRequest;
use Modules\Post\app\Models\Post;

class SearchPostRequest extends JsonRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'is_published' => ['boolean', 'nullable'],
            'poster_id' => ['string', 'nullable'],
            'type' => ['string', 'nullable', 'in:' . Post::FREE_TYPE . ',' . Post::PREMIUM_TYPE],
            'published_start_at_from' => ['date', 'nullable', 'date_format:Y-m-d H:i:s'],
            'published_start_at_until' => ['date', 'nullable', 'date_format:Y-m-d H:i:s', 'after_or_equal:published_start_at_from'],
            'published_end_at_from' => ['date', 'nullable', 'date_format:Y-m-d H:i:s'],
            'published_end_at_until' => ['date', 'nullable', 'date_format:Y-m-d H:i:s', 'after_or_equal:published_end_at_from'],
            'content' => ['string', 'nullable'],
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
