<?php

namespace Modules\Session\App\Services;

use Modules\Cache\app\Services\MemberCacheService;
use Modules\Core\app\Exceptions\FatalErrorException;

/**
 * Class SessionService.
 *
 * Service class responsible for handling operations related to user session.
 */
class SessionService
{
    private MemberCacheService $memberCacheService;

    /**
     * SessionService constructor.
     *
     * @param MemberCacheService $memberCacheService The member cache service.
     */
    public function __construct(MemberCacheService $memberCacheService)
    {
        $this->memberCacheService = $memberCacheService;
    }

    /**
     * Get member data based on the provided parameters.
     *
     * This function retrieves user data based on the provided parameters such as admin ID, role, and User ID.
     * If the User ID is null, it returns an empty array for user data.
     * If the User ID is provided, it retrieves the cached member data using the MemberCacheService.
     *
     * @param string $adminId The admin ID.
     * @param int $role The role of the user.
     * @param int|null $userId The User ID.
     * @return array An array containing the role and user data.
     * @throws FatalErrorException If an unexpected role ID is encountered.
     */
    public function getMembership(string $adminId, int $role, ?int $userId): array
    {
        if (is_null($userId)) {
            return [
                'role' => $role,
                'member_data' => [],
            ];
        }

        $membership = $this->memberCacheService->getMemberDataFromCache($userId, $adminId);

        return [
            'role' => $role,
            'member_data' => $membership,
        ];
    }
}
