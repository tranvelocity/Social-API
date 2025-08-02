<?php

namespace Modules\Core\Tests;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Mockery;
use Mockery\MockInterface;
use Modules\Admin\Entities\Admin;
use Modules\Admin\Services\AdminService;
use Modules\Core\app\Constants\StatusCodeConstant;
use Modules\Core\app\Exceptions\UnauthorizedException;
use Modules\Core\app\Http\Middlewares\ApiAuthentication;

/**
 * Class ApiAuthenticationMiddlewareTest
 *
 * This class contains test cases for the ApiAuthentication middleware.
 * It tests various scenarios such as invalid headers, expired timestamps,
 * valid requests, and invalid signatures.
 */
class ApiAuthenticationMiddlewareTest extends TestCase
{
    /**
     * @var ApiAuthentication
     */
    private ApiAuthentication $middleware;

    /**
     * @var MockInterface|AdminService
     */
    private MockInterface $adminService;

    /**
     * Set up the test environment.
     *
     * This method is called before each test. It initializes the middleware
     * and mocks the AdminService dependency.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->adminService = Mockery::mock(AdminService::class);
        $this->middleware = new ApiAuthentication($this->adminService);
    }

    /**
     * Test the case where headers are invalid or missing.
     *
     * This test ensures that the middleware throws an UnauthorizedException
     * when the required headers are not present in the request.
     *
     * @return void
     */
    /** @test */
    public function testInvalidHeaders(): void
    {
        $request = Request::create('/test', 'GET');

        $this->expectException(UnauthorizedException::class);

        $next = function ($request) {
            return 'next middleware';
        };

        $this->middleware->handle($request, $next);
    }

    /**
     * Test the case where the timestamp in the request is expired.
     *
     * This test ensures that the middleware throws an UnauthorizedException
     * when the timestamp is older than the allowed expiration time.
     *
     * @return void
     */
    /** @test */
    public function testExpiredTimestamp(): void
    {
        $testAdminUuid = $this->faker->uuid();
        $testApiKey = $this->faker->regexify('[A-Za-z0-9]{20}');
        $testApiSecret = $this->faker->regexify('[A-Za-z0-9]{40}');

        Config::set('api.auth.authorization_expiration', $this->faker->randomNumber(1, 1000));

        $request = Request::create('/test', 'GET', [], [], [], [
            'HTTP_' . config('api.auth.headers.api_key') => $testApiKey,
            'HTTP_' . config('api.auth.headers.timestamp') => Carbon::now()->subSeconds(config('api.auth.authorization_expiration') + $this->faker->randomNumber(1, 1000))->timestamp,
            'HTTP_' . config('api.auth.headers.signature') => $this->faker->regexify('[A-Za-z0-9]{20}'),
        ]);

        $admin = Mockery::mock(Admin::class);
        $admin->shouldReceive('getApiSecret')->andReturn($testApiSecret);
        $admin->shouldReceive('getUuid')->andReturn($testAdminUuid);
        $this->adminService->shouldReceive('getAdminByApiKey')->with($testApiKey)->andReturn($admin);

        $this->expectExceptionObject(new UnauthorizedException(StatusCodeConstant::UNAUTHORIZED_EXPIRED_CODE));

        $next = function ($request) {
            return 'next middleware';
        };

        $this->middleware->handle($request, $next);
    }

    /**
     * Test the case where the request is valid and all headers are correct.
     *
     * This test ensures that the middleware correctly processes the request
     * when all headers are valid and the API signature is correct.
     *
     * @return void
     */
    /** @test */
    public function testValidRequest(): void
    {
        $testAdminUuid = $this->faker->uuid();
        $testApiKey = $this->faker->regexify('[A-Za-z0-9]{20}');
        $testApiSecret = $this->faker->regexify('[A-Za-z0-9]{40}');

        $request = Request::create('/test', 'GET', [], [], [], [
            'HTTP_' . config('api.auth.headers.api_key') => $testApiKey,
            'HTTP_' . config('api.auth.headers.timestamp') => Carbon::now()->timestamp,
            'HTTP_' . config('api.auth.headers.signature') => $this->faker->regexify('[A-Za-z0-9]{20}'),
        ]);

        $admin = Mockery::mock(Admin::class);
        $admin->shouldReceive('getApiSecret')->andReturn($testApiSecret);
        $admin->shouldReceive('getUuid')->andReturn($testAdminUuid);
        $this->adminService->shouldReceive('getAdminByApiKey')->with($testApiKey)->andReturn($admin);

        // Mock the ApiSignatureTrait::verifySha2Signature method to return true
        $this->middleware = Mockery::mock(ApiAuthentication::class, [$this->adminService])
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
        $this->middleware->shouldReceive('verifySha2Signature')->andReturn(true);

        $next = function ($request) {
            return 'next middleware';
        };

        $response = $this->middleware->handle($request, $next);

        $this->assertEquals('next middleware', $response);
        $this->assertEquals($testAdminUuid, $request->get(config('auth.authorized_admin_id')));
    }

    /**
     * Test the case where the signature in the request is invalid.
     *
     * This test ensures that the middleware throws an UnauthorizedException
     * when the signature provided in the request does not match the expected signature.
     *
     * @return void
     */
    /** @test */
    public function testInvalidSignature(): void
    {
        $testAdminUuid = $this->faker->uuid();
        $testApiKey = $this->faker->regexify('[A-Za-z0-9]{20}');
        $testApiSecret = $this->faker->regexify('[A-Za-z0-9]{40}');

        $request = Request::create('/test', 'GET', [], [], [], [
            'HTTP_' . config('api.auth.headers.api_key') => $testApiKey,
            'HTTP_' . config('api.auth.headers.timestamp') => Carbon::now()->timestamp,
            'HTTP_' . config('api.auth.headers.signature') => $this->faker->regexify('[A-Za-z0-9]{20}'),
        ]);

        $admin = Mockery::mock(Admin::class);
        $admin->shouldReceive('getApiSecret')->andReturn($testApiSecret);
        $admin->shouldReceive('getUuid')->andReturn($testAdminUuid);

        $this->adminService->shouldReceive('getAdminByApiKey')->with($testApiKey)->andReturn($admin);

        $this->expectExceptionObject(new UnauthorizedException(StatusCodeConstant::UNAUTHORIZED_INVALID_SIGNATURE_CODE));

        $next = function ($request) {
            return 'next middleware';
        };

        $this->middleware->handle($request, $next);
    }
}
