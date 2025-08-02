<?php

namespace Modules\Post\app\Http\Requests;

use Modules\Post\app\Models\Post;

class UpdatePostRequest extends CreatePostRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'is_published' => ['boolean', 'nullable'],
            'type' => ['string', 'in:' . implode(',', [Post::FREE_TYPE, Post::PREMIUM_TYPE])],
            'published_start_at' => ['nullable', 'date_format:Y-m-d H:i:s'],
            'published_end_at' => ['nullable', 'date_format:Y-m-d H:i:s', 'after:published_start_at'],
            'content' => ['nullable', 'string'],
            'media_ids' => ['nullable', 'array'],
            'media_ids.*.id' => ['required_with:media_ids', 'string'],
        ];
    }
}
