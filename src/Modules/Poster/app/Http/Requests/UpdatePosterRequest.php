<?php

namespace Modules\Poster\app\Http\Requests;

class UpdatePosterRequest extends CreatePosterRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'description' => ['nullable', 'string'],
        ];
    }
}
