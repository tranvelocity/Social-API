<?php

namespace Modules\Poster\app\Http\Requests;

use Modules\Core\app\Http\Requests\JsonRequest;

class CreatePosterRequest extends JsonRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'description' => ['string', 'nullable'],
            'user_id' => ['required', 'integer'],
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
