<?php

namespace Modules\Cache\app\Services;

use Illuminate\Support\Facades\Config;
use Modules\Core\app\Repositories\CacheRepository;
use Modules\Crm\Member\Repositories\CrmMemberRepository;

/**
 * Class MemberCacheService.
 *
 * This class handles caching member data retrieved from the CRM repository.
 */
class MemberCacheService
{
    /**
     * @var CrmMemberRepository The CRM member repository instance.
     */
    private CrmMemberRepository $crmMemberRepository;

    /**
     * MemberCacheService constructor.
     *
     * @param CrmMemberRepository $crmMemberRepository The CRM member repository instance.
     */
    public function __construct(CrmMemberRepository $crmMemberRepository)
    {
        $this->crmMemberRepository = $crmMemberRepository;
    }

    /**
     * Clears the cached member data for a specific admin UUID and User ID combination.
     *
     * @param string $adminUuid The UUID of the admin.
     * @param int $userId The User ID of the member.
     */
    public static function clearCachedMemberData(string $adminUuid, int $userId): void
    {
        $cacheKey = self::getMemberDataCacheKey($adminUuid, $userId);

        if (CacheRepository::has($cacheKey)) {
            CacheRepository::forget($cacheKey);
        }
    }

    /**
     * Resets the cached member data for a given user ID and admin UUID.
     *
     * @param int $userId The user ID of the user.
     * @param string $adminUuid The UUID of the admin.
     * @return mixed
     */
    public function getNewestMemberData(int $userId, string $adminUuid): mixed
    {
        self::clearCachedMemberData($adminUuid, $userId);

        return $this->getMemberDataFromCache($userId, $adminUuid);
    }

    /**
     * Retrieves member data from the cache based on the User ID and admin UUID.
     * If the data is not found in the cache, it fetches the data from the CRM repository and stores it in the cache.
     *
     * @param int $userId The User ID of the member.
     * @param string $adminUuid The UUID of the admin.
     * @return mixed The member data retrieved from the cache or null if not found.
     */
    public function getMemberDataFromCache(int $userId, string $adminUuid): mixed
    {
        $cacheKey = self::getMemberDataCacheKey($adminUuid, $userId);

        if (!CacheRepository::has($cacheKey)) {
            $member = $this->fetchMemberDataFromCRM($userId);

            // Generate a random expiration time between 1 month and 2 months (in seconds)
            $expiration = rand(
                Config::get('cache.member_data_cache_expiration_min', 2600000), // 1 month in seconds
                Config::get('cache.member_data_cache_expiration_max', 5200000)  // 2 months in seconds
            );

            CacheRepository::store($cacheKey, json_encode($member), $expiration);
        }

        return json_decode(CacheRepository::get($cacheKey));
    }

    /**
     * Fetches member data from the CRM repository based on the User ID.
     *
     * @param int $userId The User ID of the member.
     * @return array|null The member data retrieved from the CRM repository or null if not found.
     */
    public function fetchMemberDataFromCRM(int $userId): ?array
    {
        $response = $this->crmMemberRepository->getMembers(['user_id' => $userId, 'is_resigned' => false]);

        return !empty($response) ? $response[0] : null;
    }

    /**
     * Generates a cache key for storing the member data based on admin UUID and user ID.
     *
     * This method constructs a cache key using the provided admin UUID and user ID,
     * formatted according to the configuration specified in the cache configuration file.
     * The cache key is used to store and retrieve the member data from the cache.
     *
     * @param string $adminUuid The UUID of the administrator.
     * @param int $userId The user ID of the member.
     * @return string The cache key for storing the member's information.
     */
    public static function getMemberDataCacheKey(string $adminUuid, int $userId): string
    {
        return sprintf(Config::get('cache.member_data_cache_key'), $adminUuid, $userId);
    }

    /**
     * Gets the cache key for a specific admin and user ID.
     *
     * @param string $adminUuid The UUID of the admin.
     * @param int    $userId The user ID associated with the admin.
     *
     * @return string The generated cache key.
     */
    public static function getMemberRoleCacheKey(string $adminUuid, int $userId): string
    {
        return sprintf(Config::get('cache.member_role_cache_key'), $adminUuid, $userId);
    }

    /**
     * Clears the cached role for a specific admin and user ID.
     *
     * @param string $adminUuid The UUID of the admin for whom the role cache needs to be cleared.
     * @param int    $userId The user ID associated with the admin.
     */
    public static function clearCachedMemberRole(string $adminUuid, int $userId): void
    {
        $cacheKey = self::getMemberRoleCacheKey($adminUuid, $userId);

        if (CacheRepository::has($cacheKey)) {
            CacheRepository::forget($cacheKey);
        }
    }
}
