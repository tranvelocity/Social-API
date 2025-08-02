<?php

namespace Modules\Media\app\Http\Requests;

use Modules\Core\app\Http\Requests\JsonRequest;

class UpdateMediaRequest extends JsonRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'post_id' => ['string', 'required'],
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
