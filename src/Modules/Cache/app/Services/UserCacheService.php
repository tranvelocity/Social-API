<?php

namespace Modules\Cache\app\Services;

use Exception;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Lang;
use Modules\Core\app\Exceptions\FatalErrorException;
use Modules\Core\app\Repositories\CacheRepository;
use Modules\Crm\User\Repositories\CrmUserRepositoryInterface;

/**
 * Class UserCacheService.
 *
 * This class handles caching user data retrieved from the CRM repository.
 */
class UserCacheService
{
    private CrmUserRepositoryInterface $crmUserRepository;

    /**
     * UserCacheService constructor.
     *
     * @param CrmUserRepositoryInterface $crmUserRepository The CRM user repository instance.
     */
    public function __construct(
        CrmUserRepositoryInterface $crmUserRepository
    ) {
        $this->crmUserRepository = $crmUserRepository;
    }

    /**
     * Clears the cached user data for a specific admin UUID and User ID combination.
     *
     * @param string $adminUuid The UUID of the admin.
     * @param int $userId The User ID of the user.
     */
    public static function clearCachedUserData(string $adminUuid, int $userId): void
    {
        $cacheKey = self::getUserDataCacheKey($adminUuid, $userId);

        if (CacheRepository::has($cacheKey)) {
            CacheRepository::forget($cacheKey);
        }
    }

    /**
     * Retrieves user data from the cache based on the User ID and admin UUID.
     * If the data is not found in the cache, it fetches the data from the CRM repository and stores it in the cache.
     *
     * @param int $userId The User ID of the user.
     * @param string $adminUuid The UUID of the admin.
     * @return mixed The user data retrieved from the cache or null if not found.
     * @throws FatalErrorException If the cache expiration configuration is missing or invalid.
     */
    public function getUserDataFromCache(int $userId, string $adminUuid): mixed
    {
        $cacheKey = self::getUserDataCacheKey($adminUuid, $userId);
        $cacheExpiration = Config::get('cache.user_data_cache_expiration');

        if (!is_int($cacheExpiration) || $cacheExpiration <= 0) {
            throw new FatalErrorException(Lang::get('cache::errors.user_data_cache_expiration_configuration_invalid'));
        }

        if (!CacheRepository::has($cacheKey)) {
            $user = $this->fetchUserFromCRM($userId);
            CacheRepository::store($cacheKey, json_encode($user), $cacheExpiration);
        }

        return json_decode(CacheRepository::get($cacheKey));
    }

    /**
     * Generates a cache key for storing the user data based on admin UUID and user ID.
     *
     * This method constructs a cache key using the provided admin UUID and user ID,
     * formatted according to the configuration specified in the cache configuration file.
     * The cache key is used to store and retrieve the user data from the cache.
     *
     * @param string $adminUuid The UUID of the administrator.
     * @param int $userId The user ID of the user.
     * @return string The cache key for storing the user's information.
     */
    public static function getUserDataCacheKey(string $adminUuid, int $userId): string
    {
        return sprintf(Config::get('cache.user_data_cache_key'), $adminUuid, $userId);
    }

    /**
     * Fetches user data from the CRM repository based on the User ID.
     *
     * @param int $userId The User ID of the user.
     * @return array|null The user data retrieved from the CRM repository or null if not found.
     */
    private function fetchUserFromCRM(int $userId): ?array
    {
        $response = $this->crmUserRepository->getUsers(['user_id' => $userId], 1);

        return !empty($response) ? $response[0] : null;
    }

    /**
     * Retrieve users' data from the cache or CRM based on the provided User IDs.
     *
     * @param string $adminUuid The UUID of the admin.
     * @param array $userIds An array of User IDs.
     * @return array An associative array containing users' data indexed by their User IDs.
     * @throws FatalErrorException If the cache expiration configuration is invalid.
     */
    public function getUsersFromCache(string $adminUuid, array $userIds): array
    {
        $cacheExpiration = Config::get('cache.user_data_cache_expiration');

        if (!is_int($cacheExpiration) || $cacheExpiration <= 0) {
            throw new FatalErrorException(Lang::get('cache::errors.user_data_cache_expiration_configuration_invalid'));
        }

        $cachedUsers = [];
        $nonCachedUserIds = [];

        foreach ($userIds as $userId) {
            $cacheKey = self::getUserDataCacheKey($adminUuid, $userId);

            if (CacheRepository::has($cacheKey)) {
                $cachedUsers[$userId] = json_decode(CacheRepository::get($cacheKey));
            } else {
                $nonCachedUserIds[] = $userId;
            }
        }

        if (!empty($nonCachedUserIds)) {
            $users = $this->getMultipleUsers(implode(',', $nonCachedUserIds));

            foreach ($users as $user) {
                $userId = (int) $user['user_id'];
                $cacheKey = self::getUserDataCacheKey($adminUuid, $userId);
                CacheRepository::store($cacheKey, json_encode($user), $cacheExpiration);
                $cachedUsers[$userId] = json_decode(CacheRepository::get($cacheKey));
            }
        }

        return $cachedUsers;
    }

    /**
     * Retrieve multiple users' data from the CRM based on the provided User IDs.
     *
     * @param string $userIds A comma-separated string of User IDs.
     * @return array An array containing users' data fetched from the CRM.
     */
    private function getMultipleUsers(string $userIds): array
    {
        $response = $this->crmUserRepository->getUsers(['user_ids' => $userIds], 1);

        return !empty($response) ? $response : [];
    }

    /**
     * Resets the cached user data for a given user ID and admin UUID.
     *
     * @param int $userId The user ID of the user.
     * @param string $adminUuid The UUID of the admin.
     * @return mixed
     * @throws Exception If there is an issue clearing or storing the cached user data.
     */
    public function getNewestUserData(int $userId, string $adminUuid): mixed
    {
        self::clearCachedUserData($adminUuid, $userId);

        return $this->getUserDataFromCache($userId, $adminUuid);
    }
}
