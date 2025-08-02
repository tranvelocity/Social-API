<?php

declare(strict_types=1);

namespace Modules\Core\app\Repositories;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

/**
 * Class CacheRepository
 *
 * Repository class for cache operations, providing a layer of abstraction for caching mechanisms.
 *
 * This class can be extended to create a specialized Cache Repository for scenarios where caching needs to be
 * disabled, such as during unit tests or other specific use cases. By extending this class and overriding its
 * methods, you can implement custom behavior or disable caching altogether as needed.
 */
class CacheRepository implements CacheRepositoryInterface
{
    /**
     * Store a value in the cache.
     *
     * @param string $key The cache key.
     * @param mixed $value The value to store in the cache.
     * @param int|null $expiration The expiration time for the cache entry.
     */
    public static function store(string $key, mixed $value, ?int $expiration = null): void
    {
        $expiration = $expiration ?? Config::get('auth.cache.default_expiration', 60);

        Cache::put($key, $value, $expiration);
    }


    /**
     * Retrieve a value from the cache.
     *
     * @param string $key The cache key.
     * @param mixed|null $default The default value to return if the key is not found.
     * @return mixed The value from the cache or the default value if not found.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return Cache::get($key, $default);
    }

    /**
     * Check if a key exists in the cache.
     *
     * @param string $key The cache key.
     * @return bool True if the key exists in the cache, false otherwise.
     */
    public static function has(string $key): bool
    {
        return Cache::has($key);
    }

    /**
     * Remove a key from the cache.
     *
     * @param string $key The cache key to remove.
     */
    public static function forget(string $key): void
    {
        Cache::forget($key);
    }
}
