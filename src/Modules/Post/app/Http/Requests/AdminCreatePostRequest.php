<?php

namespace Modules\Post\app\Http\Requests;

use Modules\Core\app\Http\Requests\JsonRequest;
use Modules\Post\app\Models\Post;

class AdminCreatePostRequest extends JsonRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'poster_id' => ['string', 'required'],
            'is_published' => ['boolean', 'nullable'],
            'type' => ['required', 'string', 'in:' . implode(',', [Post::FREE_TYPE, Post::PREMIUM_TYPE])],
            'published_start_at' => ['nullable', 'date_format:Y-m-d H:i:s'],
            'published_end_at' => ['nullable', 'date_format:Y-m-d H:i:s', 'after:published_start_at'],
            'content' => ['nullable', 'string'],
            'media_ids' => ['array'],
            'media_ids.*.id' => ['required_with:media_ids', 'string'],
        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validated parameters from the request and prepare them for further processing.
     *
     * @return array An array containing the validated parameters.
     */
    public function getValidatedParams(): array
    {
        $inputs = $this->validation();

        // Convert 'is_published' boolean to integer (1 for true, 0 for false)
        if (isset($inputs['is_published'])) {
            $inputs['is_published'] = intval($inputs['is_published']);
        }

        if (isset($inputs['media_ids'])) {
            $inputs['media_ids'] = array_column($inputs['media_ids'], 'id');
        }

        return $inputs;
    }
}
