<?php

declare(strict_types=1);

namespace Modules\Core\app\Helpers;

use Illuminate\Support\Facades\Config;

class TranauthHelper
{
    /**
     * Get the cache key for an authorized admin based on the provided extension key.
     *
     * @param string $extensionKey The extension key for which the cache key is generated.
     * @return string The cache key for the authorized admin.
     */
    public static function getCacheKeyForAuthorizedAdmin(string $extensionKey): string
    {
        $cacheKeyTemplate = Config::get("auth.cache.authorized_admin_key", '%app_name%_%extension_key%');

        $replacements = [
            '%app_name%' => config("app.name"),
            '%extension_key%' => $extensionKey,
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $cacheKeyTemplate);
    }

    /**
     * Get the cache key for verified headers based on the provided extension key and algorithm.
     *
     * @param string $extensionKey The extension key for which the cache key is generated.
     * @param string $algorithm The algorithm used for verification (default is 'sha2').
     * @return string The cache key for the verified headers.
     */
    public static function getCacheKeyForVerifiedHeaders(string $extensionKey, string $algorithm = 'sha2'): string
    {
        $cacheKeyTemplate = Config::get("auth.cache.verified_headers_{$algorithm}_key", '%app_name%_%extension_key%');

        $replacements = [
            '%app_name%' => config("app.name"),
            '%extension_key%' => $extensionKey,
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $cacheKeyTemplate);
    }

    /**
     * Generates a cache key for storing User auth data based on the provided extension key.
     *
     * @param string $extensionKey The extension key associated with the User authentication.
     *
     * @return string The generated cache key for storing User auth data.
     */
    public static function getCacheKeyForStoringUserAuthentication(string $extensionKey): string
    {
        $cacheKeyTemplate = Config::get("auth.cache.user_auth_key", '%app_name%_%extension_key%');

        $replacements = [
            '%app_name%' => config("app.name"),
            '%extension_key%' => $extensionKey,
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $cacheKeyTemplate);
    }
}
