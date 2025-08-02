<?php

namespace Modules\Core\app\Http\Middlewares;

use Closure;
use Illuminate\Http\Request;
use Modules\Core\app\Constants\StatusCodeConstant;
use Modules\Core\app\Exceptions\UnauthorizedException;
use Modules\Core\app\Repositories\CacheRepository;

class SessionAuthentication
{
    public const SESSION_STORE_NAME = '%s:auth:session:%s';

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $sessionId = $request->header(config('api.auth.headers.session_id'));
        if (!$sessionId) {
            throw new UnauthorizedException(StatusCodeConstant::UNAUTHORIZED_SESSION_INVALID_CODE, 'Admin session_id field not present.');
        }

        $cacheKey = $this->getCacheKeyForSession($sessionId);
        $session = json_decode(CacheRepository::get($cacheKey));
        if (!$session) {
            throw new UnauthorizedException(StatusCodeConstant::UNAUTHORIZED_SESSION_EXPIRED_CODE, 'Admin session_id not valid.');
        }

        $request[config('auth.authorized_admin_id')] = $session->admin_uuid;
        $request[config('auth.authorized_merchant_id')] = $session->account_id;

        $this->setRequestId($request);

        return $next($request);
    }

    /**
     * Generate a cache key name of session.
     *
     * @param string $sessionId
     * @return string
     */
    private static function getCacheKeyForSession(string $sessionId)
    {
        return sprintf(self::SESSION_STORE_NAME, config('app.env'), $sessionId);
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
