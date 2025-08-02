<?php

namespace Modules\Media\app\Http\Requests;


use Modules\Core\app\Http\Requests\JsonRequest;

class SearchMediaRequest extends JsonRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'post_id' => ['string', 'nullable'],
            'type' => ['nullable', 'string'],
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
