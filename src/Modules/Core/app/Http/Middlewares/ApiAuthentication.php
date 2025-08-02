<?php

namespace Modules\Core\app\Http\Middlewares;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Modules\Admin\Entities\Admin;
use Modules\Admin\Services\AdminService;
use Modules\Core\app\Constants\StatusCodeConstant;
use Modules\Core\app\Exceptions\UnauthorizedException;
use Modules\Core\app\Helpers\TranauthHelper;
use Modules\Core\app\Repositories\CacheRepository;
use Modules\Core\app\Traits\ApiHeaderValidator;
use Modules\Core\app\Traits\ApiSignatureTrait;

/**
 * Class ApiAuthentication
 *
 * This class handles the authentication of API requests using SHA-2.
 */
class ApiAuthentication
{
    use ApiSignatureTrait;

    /**
     * @var AdminService
     */
    private AdminService $adminService;

    /**
     * Create a new ApiAuthentication instance.
     *
     * @param AdminService $adminService
     */
    public function __construct(AdminService $adminService)
    {
        $this->adminService = $adminService;
    }

    /**
     * Handle API authentication middleware.
     *
     * This middleware validates the basic authentication headers of an incoming request with SHA-2 algorithm.
     * It checks the API key, timestamp, and client signature headers, retrieves admin information from DynamoDB,
     * and verifies the timestamp and client signature.
     * If the headers are invalid or the authentication fails, it logs an error and throws an UnauthorizedException.
     * If the authentication is successful, it adds the authorized admin ID to the request and caches the verified headers.
     *
     * @param Request $request The incoming request.
     * @param Closure $next    The next middleware closure.
     *
     * @return mixed
     *
     * @throws UnauthorizedException When the headers are invalid or the authentication fails.
     */
    public function handle(Request $request, Closure $next): mixed
    {
        // Validate API headers
        $validator = ApiHeaderValidator::validateBasicAuthHeaders($request->headers->all());

        if ($validator->fails()) {
            $errorMessage = 'Invalid headers: ' . json_encode($validator->errors()->all());
            Log::error($errorMessage);
            throw new UnauthorizedException();
        }

        // Extract API key, timestamp, and client signature from headers
        $apiKey = $request->header(config('api.auth.headers.api_key'));
        $timestamp = $request->header(config('api.auth.headers.timestamp'));
        $clientSignature = $request->header(config('api.auth.headers.signature'));

        // Retrieve admin information from DynamoDB
        $admin = $this->getAdmin($apiKey);
        if (!$admin) {
            Log::error('Admin not found for API Key: ' . $apiKey);
            throw new UnauthorizedException();
        }

        // Check if the timestamp is not older than the time defined in the config/api.php file
        $currentTimestamp = Carbon::now()->timestamp;
        $timestampDiff = $currentTimestamp - $timestamp;

        $timestampExpiration = config('api.auth.authorization_expiration');
        if (intval($timestampExpiration) > 0 && $timestampDiff > $timestampExpiration) {
            throw new UnauthorizedException(StatusCodeConstant::UNAUTHORIZED_EXPIRED_CODE);
        }

        // Verify API signature
        if (!$this->verifySha2Signature($apiKey, $timestamp, $clientSignature, $admin->getApiSecret())) {
            throw new UnauthorizedException(StatusCodeConstant::UNAUTHORIZED_INVALID_SIGNATURE_CODE);
        }

        // Add authorized admin ID to the request
        $request[Config::get('auth.authorized_admin_id')] = $admin->getUuid();

        // Cache verified headers
        $cacheKey = TranauthHelper::getCacheKeyForVerifiedHeaders($admin->getUuid());

        if (!CacheRepository::has($cacheKey)) {
            CacheRepository::store($cacheKey, json_encode([
                'api_key' => $apiKey,
                'signature' => $clientSignature,
                'timestamp' => $timestamp,
                'api_secret' => $admin->getApiSecret(),
            ]));
        }

        $this->setRequestId($request);

        return $next($request);
    }

    /**
     * Get admin information from DynamoDB based on the API key.
     *
     * @param string $apiKey
     * @return Admin|null
     * @throws \Exception
     */
    private function getAdmin(string $apiKey): ?Admin
    {
        return $this->adminService->getAdminByApiKey($apiKey);
    }

    /**
     * Sets the request ID header if available.
     *
     * @param Request $request
     * @return void
     */
    private function setRequestId(Request $request)
    {
        $headers = [
            'HTTP_X_REQUEST_ID',
            'NGINX_REQUEST_ID',
        ];

        foreach ($headers as $header) {
            if ($request->hasHeader($header)) {
                $request->headers->set('X-REQUEST_ID', $request->header($header));
                break;
            }
        }
    }
}
