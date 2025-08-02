<?php

namespace Modules\Session\Tests\Feature;

use Modules\Poster\app\Models\Poster;
use Modules\Test\Tests\Feature\ApiTestCase;

/**
 * @group parallel
 */
final class SessionControllerPosterVerificationTest extends ApiTestCase
{
    private string $endpoint = '/1/session/is-poster';

    /**
     * @test
     */
    public function test_200_response_when_current_user_is_a_poster(): void
    {
        // Mock data
        $admin = $this->generateMockAdmin();
        $userUser = $this->generateMockUserUser();
        $userId = $userUser['user_id'];

        // Mock the dependencies
        $this->mockUserAuthenticationService($admin);
        $this->generateMockUserUserSession($userUser);

        Poster::factory(['admin_uuid' => $admin->getUuid(), 'user_id' => $userId])->create();

        // Execute the endpoint
        $response = $this->postJson($this->endpoint, [], $this->generateMockSocialApiHeaders($admin));

        // Assertions
        $this->assertSuccessCustomResponse($response, ['is_poster' => true]);
    }

    /** @test */
    public function test_200_response_when_current_user_is_not_a_poster(): void
    {
        // Mock data
        $admin = $this->generateMockAdmin();
        $userUser = $this->generateMockUserUser();

        // Mock the dependencies
        $this->mockUserAuthenticationService($admin);
        $this->generateMockUserUserSession($userUser);

        Poster::factory(['admin_uuid' => $admin->getUuid()])->create();

        // Execute the endpoint
        $response = $this->postJson($this->endpoint, [], $this->generateMockSocialApiHeaders($admin));

        // Assertions
        $this->assertSuccessCustomResponse($response, ['is_poster' => false]);
    }

    /** @test */
    public function test_401_response_poster_validation_fails_due_to_unauthorized(): void
    {
        // Mock data
        $admin = $this->generateMockAdmin();
        $userUser = $this->generateMockUserUser();

        // Mock the dependencies
        $this->mockUserAuthenticationService($admin);
        $this->generateMockUserUserSession($userUser);

        Poster::factory(['admin_uuid' => $admin->getUuid()])->create();

        // Execute the endpoint
        $response = $this->postJson($this->endpoint, [], $this->generateMockSocialApiHeaders($admin, false));

        $this->assert401UnauthorizedResponse($response);
    }
}
