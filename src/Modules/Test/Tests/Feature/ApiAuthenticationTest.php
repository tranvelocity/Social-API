<?php

namespace Modules\Test\Tests\Feature;

use Illuminate\Support\Facades\Config;
use Modules\Core\app\Constants\StatusCodeConstant;

class ApiAuthenticationTest extends ApiTestCase
{
    protected string $endpoint = '/1/posters';

    /** @test */
    public function test_401_response_unauthorized_due_to_signature_invalid(): void
    {
        $admin = $this->generateMockAdmin();

        $this->mockUserAuthenticationService($admin);
        $this->generateMockUserUserSession($this->generateMockUserUser());

        $headers = $this->generateMockValidHeaders($admin, [], 'sha1'); // This project is using sha1 algorithm
        $response = $this->getJson($this->endpoint, $headers);

        $this->assert401UnauthorizedResponse($response, config('tranauth.exceptions.401.401003'), StatusCodeConstant::UNAUTHORIZED_INVALID_SIGNATURE_CODE);
    }

    /** @test */
    public function test_401_response_unauthorized_due_to_headers_invalid(): void
    {
        $admin = $this->generateMockAdmin();

        $this->mockUserAuthenticationService($admin);
        $this->generateMockUserUserSession($this->generateMockUserUser());

        $headers = $this->generateMockSocialApiHeaders($admin);
        unset($headers[Config::get('api.auth.headers.signature')]);

        $response = $this->getJson($this->endpoint, $headers);

        $this->assert401UnauthorizedResponse($response);
    }

    /** @test */
    public function test_401_response_unauthorized_due_to_authorization_timestamp_has_expired(): void
    {
        Config::set('api.auth.authorization_expiration', $this->faker->randomDigit());

        $admin = $this->generateMockAdmin();

        $this->mockUserAuthenticationService($admin);
        $this->generateMockUserUserSession($this->generateMockUserUser());

        $headers = $this->generateMockValidHeaders($admin, []);

        // set the timestamp to expired value
        $originalTimestamp = Config::get('api.auth.headers.timestamp');
        $expiredTimestamp = (int) $originalTimestamp - ((int) Config::get('api.auth.authorization_expiration') + $this->faker->numberBetween(1, 10));
        $headers[Config::get('api.auth.headers.timestamp')] = $expiredTimestamp;

        $response = $this->getJson($this->endpoint, $headers);

        $this->assert401UnauthorizedResponse($response, config('tranauth.exceptions.401.401002'), StatusCodeConstant::UNAUTHORIZED_EXPIRED_CODE);
    }
}
