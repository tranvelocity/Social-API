<?php

declare(strict_types=1);

namespace Modules\Core\app\Traits;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Config;
use Modules\Admin\Services\AdminService;
use Modules\Core\app\Exceptions\UnauthorizedException;
use Modules\Core\app\Helpers\TranauthHelper;
use Modules\Core\app\Repositories\CacheRepository;

trait HttpClient
{
    use ApiSignatureTrait;

    /**
     * Generate an HTTP client with SHA-2 headers.
     *
     * This function generates an HTTP client with SHA-2 headers for authentication.
     * It first checks if the headers are available in the cache. If not found in the cache,
     * it retrieves the API key and secret from DynamoDB using the provided authorized admin ID.
     * It then generates the SHA-2 headers, stores them in the cache, and returns an HTTP client
     * with the generated headers.
     *
     * @param string $apiKeyHeader     The header key for the API key.
     * @param string $timestampHeader  The header key for the timestamp.
     * @param string $signatureHeader  The header key for the signature.
     *
     * @return Client The HTTP client with SHA-2 headers.
     */
    protected function generateHttpClientWithSha2(string $apiKeyHeader, string $timestampHeader, string $signatureHeader): Client
    {
        return $this->generateHttpClientWithSignatureAlgorithm($apiKeyHeader, $timestampHeader, $signatureHeader, 'sha2');
    }

    /**
     * Generate an HTTP client with SHA-1 headers.
     *
     * This function generates an HTTP client with SHA-1 headers for authentication.
     * It first checks if the headers are available in the cache. If not found in the cache,
     * it retrieves the API key and secret from DynamoDB using the provided authorized admin ID.
     * It then generates the SHA-1 headers, stores them in the cache, and returns an HTTP client
     * with the generated headers.
     *
     * @param string $apiKeyHeader     The header key for the API key.
     * @param string $timestampHeader  The header key for the timestamp.
     * @param string $signatureHeader  The header key for the signature.
     *
     * @return Client The HTTP client with SHA-1 headers.
     */
    protected function generateHttpClientWithSha1(string $apiKeyHeader, string $timestampHeader, string $signatureHeader): Client
    {
        return $this->generateHttpClientWithSignatureAlgorithm($apiKeyHeader, $timestampHeader, $signatureHeader, 'sha1');
    }

    /**
     * Generate an HTTP client with specified signature algorithm headers.
     *
     * This function generates an HTTP client with headers for the specified signature algorithm (SHA-1 or SHA-2) for authentication.
     * It first checks if the headers are available in the cache. If not found in the cache,
     * it retrieves the API key and secret from DynamoDB using the provided authorized admin ID.
     * It then generates the headers based on the specified algorithm, stores them in the cache, and returns an HTTP client
     * with the generated headers.
     *
     * @param string $apiKeyHeader     The header key for the API key.
     * @param string $timestampHeader  The header key for the timestamp.
     * @param string $signatureHeader  The header key for the signature.
     * @param string $algorithm        The signature algorithm to use ('sha1' or 'sha2').
     *
     * @return Client The HTTP client with specified signature algorithm headers.
     *
     * @throws UnauthorizedException When the headers are invalid or the authentication fails.
     */
    protected function generateHttpClientWithSignatureAlgorithm(string $apiKeyHeader, string $timestampHeader, string $signatureHeader, string $algorithm = 'sha2'): Client
    {
        $authorizedAdminId = \request()->get(Config::get('auth.authorized_admin_id'));
        $cacheKey = TranauthHelper::getCacheKeyForVerifiedHeaders($authorizedAdminId, $algorithm);

        if (!CacheRepository::has($cacheKey)) {
            // Get API key and secret from DynamoDB by admin UUID
            $adminService = \app()->make(AdminService::class);
            $admin = $adminService->getAdminByAdminUuid($authorizedAdminId);
            if (!$admin) {
                throw new UnauthorizedException();
            }

            // Generate headers with specified algorithm (SHA-1 or SHA-2)
            $headers = $this->generateSignatureHeaders($admin->getApiKey(), $admin->getApiSecret(), $algorithm);
            CacheRepository::store($cacheKey, json_encode($headers));
        }

        $cachedHeaders = CacheRepository::get($cacheKey);
        $headers = json_decode($cachedHeaders);

        $headers = [
            $apiKeyHeader => $headers->api_key,
            $timestampHeader => $headers->timestamp,
            $signatureHeader => $headers->signature,
        ];

        return new Client(['headers' => $headers]);
    }
}
