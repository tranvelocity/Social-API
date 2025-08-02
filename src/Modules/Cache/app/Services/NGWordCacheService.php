<?php

namespace Modules\Cache\app\Services;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Lang;
use Modules\Core\app\Exceptions\FatalErrorException;
use Modules\Core\app\Repositories\CacheRepository;
use Modules\Crm\NGWord\Repositories\CrmNGWordRepositoryInterface;

/**
 * Class NGWordCacheService.
 *
 * This class handles caching member data retrieved from the CRM repository.
 */
class NGWordCacheService
{
    private CrmNGWordRepositoryInterface $crmNGWordRepository;

    /**
     * NGWordCacheService constructor.
     *
     * @param CrmNGWordRepositoryInterface $crmNGWordRepository The CRM member repository instance.
     */
    public function __construct(CrmNGWordRepositoryInterface $crmNGWordRepository)
    {
        $this->crmNGWordRepository = $crmNGWordRepository;
    }

    /**
     * Retrieves the list of prohibited words from the cache for the specified admin UUID.
     *
     * This method retrieves the list of prohibited words from the cache for the specified admin UUID.
     * If the data is not found in the cache, it fetches the data from the CRM repository and stores it in the cache.
     *
     * @param string $adminUuid The UUID of the admin.
     * @return array Returns the list of prohibited words as an array if available in the cache, otherwise returns an empty array.
     * @throws FatalErrorException If the cache expiration configuration is missing or invalid.
     */
    public function getNGWordsFromCache(string $adminUuid): array
    {
        $cacheKey = $this->getNGWordsCacheKey($adminUuid);
        $cacheExpiration = Config::get('cache.ng_word_expiration');

        if (!is_int($cacheExpiration) || $cacheExpiration <= 0) {
            throw new FatalErrorException(Lang::get('cache::errors.ng_word_cache_expiration_configuration_invalid'));
        }

        if (!CacheRepository::has($cacheKey)) {
            $ngWords = $this->fetchNGWordsFromCRM();
            CacheRepository::store($cacheKey, json_encode($ngWords), $cacheExpiration);
        }

        return json_decode(CacheRepository::get($cacheKey));
    }

    /**
     * Fetches the list of prohibited words from the CRM system.
     *
     * @return array an array of prohibited words if available, otherwise returns empty qrray.
     */
    public function fetchNGWordsFromCRM(): array
    {
        $response = $this->crmNGWordRepository->getBulkNGWords();

        return !empty($response) ? $response : [];
    }

    /**
     * Generates the cache key for storing the prohibited words based on the admin UUID.
     *
     * @param string $adminUuid The UUID of the admin.
     * @return string Returns the cache key for storing the prohibited words.
     */
    public static function getNGWordsCacheKey(string $adminUuid): string
    {
        return sprintf(Config::get('cache.ng_word_cache_key'), $adminUuid);
    }

    /**
     * Clears the cached NG words data for the specified admin UUID.
     *
     * @param string $adminUuid The UUID of the admin.
     * @return void
     */
    public static function clearCache(string $adminUuid): void
    {
        $cacheKey = self::getNGWordsCacheKey($adminUuid);

        if (CacheRepository::has($cacheKey)) {
            CacheRepository::forget($cacheKey);
        }
    }
}
