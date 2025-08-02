<?php

namespace Modules\Cache\app\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\Cache\app\Services\MemberCacheService;
use Modules\Cache\app\Services\NGWordCacheService;
use Modules\Cache\app\Services\UserCacheService;
use Modules\Core\app\Http\Controllers\Controller;
use Modules\Core\app\Responses\NoContentResponse;

/**
 * Class CacheController.
 *
 * Controller responsible for managing cache.
 */
class CacheController extends Controller
{
    /**
     * Clears the cached member data.
     *
     * @Route("/clear-caches/member/{userId}", methods={"DELETE"})
     *
     * @param int $userId The User ID of the member data to clear from the cache.
     *
     * @return JsonResponse A JSON response indicating success with no content.
     */
    public function destroyCachedMemberData(int $userId): JsonResponse
    {
        $adminUuid = $this->getAuthorizedAdminId();
        MemberCacheService::clearCachedMemberRole($adminUuid, $userId);
        MemberCacheService::clearCachedMemberData($adminUuid, $userId);
        UserCacheService::clearCachedUserData($adminUuid, $userId);

        return NoContentResponse::make();
    }

    /**
     * Clear the cached NG words.
     *
     * @Route("/clear-caches/ng-words", methods={"DELETE"})
     *
     * @return JsonResponse A JSON response indicating success with no content.
     *
     * @throw ForbiddenException If the user does not have permission to clear the cached NG words.
     */
    public function destroyCachedNGWord(): JsonResponse
    {
        $adminUuid = $this->getAuthorizedAdminId();
        NGWordCacheService::clearCache($adminUuid);

        return NoContentResponse::make();
    }
}
