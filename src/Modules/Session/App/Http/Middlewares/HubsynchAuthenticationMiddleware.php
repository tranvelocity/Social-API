<?php

namespace Modules\Session\App\Http\Middlewares;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Modules\Admin\Services\AdminService;
use Modules\Core\app\Constants\StatusCodeConstant;
use Modules\Core\app\Exceptions\UnauthorizedException;
use Modules\Core\app\Traits\ApiHeaderValidator;
use Modules\Core\app\Traits\ApiSignatureTrait;
use Modules\User\Session\Services\UserSessionService;
use Modules\UserAuthentication\Services\UserAuthenticationService;

class UserAuthenticationMiddleware
{
    use ApiSignatureTrait;

    private UserSessionService $userSessionService;
    private UserAuthenticationService $userAuthenticationService;
    private AdminService $adminService;

    /**
     * Create a new AuthenticateSession instance.
     */
    public function __construct(
        UserSessionService $userSessionService,
        UserAuthenticationService $userAuthenticationService,
        AdminService $adminService
    ) {
        $this->userSessionService = $userSessionService;
        $this->userAuthenticationService = $userAuthenticationService;
        $this->adminService = $adminService;
    }

    /**
     * Handle the request by validating headers and session information.
     *
     * @param Request $request
     * @param Closure $next
     *
     * @return mixed
     *
     * @throws UnauthorizedException
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $this->validateHeaders($request);

        $apiKey = $request->header(config('api.auth.headers.api_key'));
        $admin = $this->adminService->getAdminByApiKey($apiKey);

        //Retrieve the UserAuthentication header with the provided Admin.
        $userAuth = $this->userAuthenticationService->getUserAuthenticationByAdmin($admin);

        $authorizedUserId = $this->getAuthorizedUserIdByLoginSession(
            $request->header(config('api.auth.headers.auth_id')),
            $request->header(config('api.auth.headers.user_ssid')),
            $userAuth->getAppCode(),
            $userAuth->getSiteId()
        );

        $request[Config::get('tranauth.authorized_user_id')] = $authorizedUserId;

        return $next($request);
    }

    /**
     * Validate API headers and throw an exception if invalid.
     *
     * @param Request $request
     *
     * @throws UnauthorizedException
     */
    private function validateHeaders(Request $request): void
    {
        $validator = ApiHeaderValidator::validateSessionCheckHeaders($request->headers->all());

        if ($validator->fails()) {
            $errorMessage = 'Invalid headers: ' . json_encode($validator->errors()->all());
            Log::error($errorMessage);
            throw new UnauthorizedException();
        }
    }

    /**
     * Validate the session ID by calling the User API.
     *
     * @param string $authId
     * @param string $userSSID
     * @param string $appCode
     * @param string $siteId
     *
     * @throws UnauthorizedException
     */
    private function getAuthorizedUserIdByLoginSession(string $authId, string $userSSID, string $appCode, string $siteId): int
    {
        $params = [
            'site_id' => $siteId,
            'app_code' => $appCode,
            'auth_id' => $authId,
            'login_session_id' => $userSSID,
        ];

        $userAccount = $this->userSessionService->getUserAccount($params);

        if (empty($userAccount)) {
            throw new UnauthorizedException(StatusCodeConstant::UNAUTHORIZED_SESSION_INVALID_CODE);
        }

        if (!isset($userAccount['user_id'])) {
            throw new UnauthorizedException(StatusCodeConstant::UNAUTHORIZED_UNDETERMINED_ACCOUNT_CODE);
        }

        return (int) $userAccount['user_id'];
    }
}
