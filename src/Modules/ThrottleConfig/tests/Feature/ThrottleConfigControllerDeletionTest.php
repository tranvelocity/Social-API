<?php

namespace Modules\ThrottleConfig\tests\Feature;

use Illuminate\Support\Facades\Lang;
use Modules\Poster\app\Models\Poster;
use Modules\ThrottleConfig\app\Models\ThrottleConfig;

/**
 * @group parallel
 */
final class ThrottleConfigControllerDeletionTest extends ThrottleConfigControllerTestBase
{
    /** @test */
    public function test_204_response_when_deleting_throttle_config_successfully(): void
    {
        // 1. Generate the mock data
        $admin = $this->generateMockAdmin();
        $userUser = $this->generateMockUserUser();
        $throttleConfig = ThrottleConfig::factory(['admin_uuid' => $admin->getUuid()])->create();

        // 2. Mock the dependencies
        $this->mockUserAuthenticationService($admin);
        $this->generateMockUserUserSession($userUser);

        // 3. Execute the endpoint
        $response = $this->deleteJson("{$this->endpoint}/{$throttleConfig->id}", [], $this->generateMockSocialApiHeaders($admin));

        // 4. Assertions
        $this->assert204NoContentResponse($response);
    }

    /** @test */
    public function test_401_response_non_registered_user_deletes_throttle_config_fails_due_to_unauthorized(): void
    {
        // 1. Generate the mock data
        $admin = $this->generateMockAdmin();

        // 3. Execute the endpoint
        $throttleConfig = ThrottleConfig::factory(['admin_uuid' => $admin->getUuid()])->create();
        $response = $this->deleteJson("{$this->endpoint}/{$throttleConfig->id}", [], $this->generateMockSocialApiHeaders($admin, false));

        // 4. Assertions
        $this->assert401UnauthorizedResponse($response);
    }

    /** @test */
    public function test_404_response_when_deleting_fails_due_to_resource_not_found(): void
    {
        $admin = $this->generateMockAdmin();
        $userUser = $this->generateMockUserUser();
        $userId = $userUser['user_id'];

        Poster::factory(['user_id' => $userId, 'admin_uuid' => $admin->getUuid()])->create();

        // Mock necessary services
        $this->mockUserAuthenticationService($admin);
        $this->generateMockUserUserSession($userUser);

        // Send request with missing parameters
        $response = $this->deleteJson("{$this->endpoint}/non-existing-id", [], $this->generateMockSocialApiHeaders($admin));

        // Assert 400 response with validation error
        $this->assert404NotFoundResponse($response, Lang::get('throttleconfig::errors.throttle_config_not_found'));
    }
}
