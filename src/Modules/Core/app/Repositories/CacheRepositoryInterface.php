<?php

declare(strict_types=1);

namespace Modules\Core\app\Repositories;

interface CacheRepositoryInterface
{
    /**
     * Store a value in the cache.
     *
     * @param string $key The cache key.
     * @param mixed $value The value to store in the cache.
     * @param int|null $expiration The expiration time for the cache entry.
     */
    public static function store(string $key, mixed $value, ?int $expiration = null): void;

    /**
     * Retrieve a value from the cache.
     *
     * @param string $key The cache key.
     * @param mixed|null $default The default value to return if the key is not found.
     * @return mixed The value from the cache or the default value if not found.
     */
    public static function get(string $key, mixed $default = null): mixed;

    /**
     * Check if a key exists in the cache.
     *
     * @param string $key The cache key.
     * @return bool True if the key exists in the cache, false otherwise.
     */
    public static function has(string $key): bool;

    /**
     * Remove a key from the cache.
     *
     * @param string $key The cache key to remove.
     */
    public static function forget(string $key): void;
}
