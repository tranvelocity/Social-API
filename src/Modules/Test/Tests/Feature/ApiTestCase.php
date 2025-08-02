<?php

declare(strict_types=1);

namespace Modules\Test\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Admin\Entities\Admin;
use Modules\Core\app\Repositories\CacheRepository;
use Modules\Core\app\Traits\ApiSignatureTrait;
use Modules\Core\Tests\TestCase;

class ApiTestCase extends TestCase
{
    use RefreshDatabase;
    use ApiSignatureTrait;

    protected const CONSUMER_AUTH_HEADERS_CACHE_KEY = 'testing_consumer_auth_headers_cache_key';

    /**
     * Generate mock headers for the Social API authentication.
     *
     * This function retrieves or generates a mock login session based on the given Admin and caching logic.
     * It then generates and returns headers with authentication information for use in the Social API requests.
     *
     * @param Admin $admin The admin for whom the headers are being generated.
     *
     * @return array<string> An array containing mock headers with authentication information for the Social API requests.
     */
    protected function generateMockSocialApiHeaders(Admin $admin, bool $withLoginSession = true): array
    {
        $headers = [];

        if ($withLoginSession) {
            $loginSession = $this->getOrGenerateConsumerLoginSession();
            $headers = [
                config('api.auth.headers.user_ssid') => $loginSession['login_session_id'],
            ];
        }

        return $this->generateMockValidHeaders($admin, $headers);
    }

    /**
     * Retrieve or generate the consumer login session data.
     *
     * @return array The consumer login session data.
     */
    protected function getOrGenerateConsumerLoginSession(): array
    {
        $cacheKey = self::CONSUMER_AUTH_HEADERS_CACHE_KEY;

        if (!CacheRepository::has($cacheKey)) {
            $loginSession = $this->getConsumerLoginSession();
            CacheRepository::store($cacheKey, json_encode($loginSession));
        } else {
            $loginSession = json_decode(CacheRepository::get($cacheKey), true);
        }

        return $loginSession;
    }
}
