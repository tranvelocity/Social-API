<?php

namespace Modules\Core\Tests;

use Illuminate\Http\Request;
use Mockery;
use Modules\Core\app\Constants\StatusCodeConstant;
use Modules\Core\app\Exceptions\UnauthorizedException;
use Modules\Core\app\Http\Middlewares\SessionAuthentication;
use Modules\Core\app\Repositories\CacheRepository;

/**
 * Class SessionAuthenticationMiddlewareTest
 *
 * This class contains test cases for the SessionAuthentication middleware.
 */
class SessionAuthenticationMiddlewareTest extends TestCase
{
    /**
     * @var SessionAuthentication
     */
    private SessionAuthentication $middleware;

    /**
     * Set up the test environment.
     *
     * This method is called before each test.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new SessionAuthentication();
    }

    /**
     * Test the case where the session ID is missing from the request headers.
     *
     * This test ensures that the middleware throws an UnauthorizedException
     * when the session_id header is not present in the request.
     *
     * @return void
     */
    public function testMissingSessionId(): void
    {
        $request = Request::create('/test', 'GET');

        $this->expectExceptionObject(new UnauthorizedException(
            StatusCodeConstant::UNAUTHORIZED_SESSION_INVALID_CODE,
            'Admin session_id field not present.'
        ));

        $next = function ($request) {
            return 'next middleware';
        };

        $this->middleware->handle($request, $next);
    }

    /**
     * Test the case where the session ID is expired or invalid.
     *
     * This test ensures that the middleware throws an UnauthorizedException
     * when the session_id is not found in the cache.
     *
     * @return void
     */
    public function testExpiredSession(): void
    {
        $sessionId = 'test_session_id';
        $request = Request::create('/test', 'GET', [], [], [], [
            'HTTP_' . config('api.auth.headers.session_id') => $sessionId,
        ]);

        $cacheKey = sprintf(SessionAuthentication::SESSION_STORE_NAME, config('app.env'), $sessionId);
        $cacheRepository = Mockery::mock(CacheRepository::class);
        $cacheRepository->shouldReceive('get')->with($cacheKey)->andReturnNull();

        $this->expectExceptionObject(new UnauthorizedException(
            StatusCodeConstant::UNAUTHORIZED_SESSION_EXPIRED_CODE,
            'Admin session_id not valid.'
        ));

        $next = function ($request) {
            return 'next middleware';
        };

        $this->middleware->handle($request, $next);
    }

    /**
     * Test the case where the session ID is valid.
     *
     * This test ensures that the middleware processes the request correctly
     * when the session_id is found in the cache and is valid.
     *
     * @return void
     */
    public function testValidSession(): void
    {
        $sessionId = 'test_session_id';
        $adminUuid = 'test_admin_uuid';
        $accountId = 'test_account_id';
        $sessionData = json_encode(['admin_uuid' => $adminUuid, 'account_id' => $accountId]);

        $request = Request::create('/test', 'GET', [], [], [], [
            'HTTP_' . config('api.auth.headers.session_id') => $sessionId,
        ]);

        $cacheKey = sprintf(SessionAuthentication::SESSION_STORE_NAME, config('app.env'), $sessionId);
        CacheRepository::store($cacheKey, $sessionData);

        $next = function ($request) {
            return $request;
        };

        $result = $this->middleware->handle($request, $next);

        $this->assertEquals($adminUuid, $result[config('auth.authorized_admin_id')]);
        $this->assertEquals($accountId, $result[config('auth.authorized_merchant_id')]);
    }
}
