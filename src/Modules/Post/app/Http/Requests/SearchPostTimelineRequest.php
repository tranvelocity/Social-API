<?php

namespace Modules\Post\app\Http\Requests;

use Exception;
use Modules\Core\app\Http\Requests\JsonRequest;
use Modules\Role\app\Entities\Role;

class SearchPostSocialRequest extends JsonRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'last_id' => ['string', 'nullable'],
            'media_limit' => ['integer', 'nullable'],
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
     * Get the search criteria based on the provided user role.
     *
     * @param int $role The user role for which the search criteria are generated.
     *
     * @return array The array of search criteria based on the provided user role.
     *
     * @throws Exception If there is an issue with date and time calculations.
     */
    public function getSearchCriteria(int $role): array
    {
        $params = $this->validation();

        $params += match ($role) {
            Role::nonRegisteredUser()->getRole(), Role::freeMember()->getRole(), Role::paidMember()->getRole() => [
                'is_published' => true,
            ],
            default => []
        };

        return $params;
    }
}
