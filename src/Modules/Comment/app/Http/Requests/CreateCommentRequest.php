<?php

namespace Modules\Comment\app\Http\Requests;

use Modules\Core\app\Http\Requests\JsonRequest;

class CreateCommentRequest extends JsonRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'comment' => ['required', 'string'],
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

        $inputs['is_hidden'] = false;

        return $inputs;
    }
}
