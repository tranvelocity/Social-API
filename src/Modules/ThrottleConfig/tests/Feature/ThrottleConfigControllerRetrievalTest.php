<?php

namespace Modules\ThrottleConfig\tests\Feature;

use Illuminate\Support\Facades\Lang;
use Modules\Poster\app\Models\Poster;
use Modules\ThrottleConfig\app\Models\ThrottleConfig;
use Modules\ThrottleConfig\app\Resources\ThrottleConfigResource;

/**
 * @group parallel
 */
final class ThrottleConfigControllerRetrievalTest extends ThrottleConfigControllerTestBase
{
    /** @test */
    public function test_200_response_when_retrieving_throttle_config(): void
    {
        // 1. Generate the mock data
        $admin = $this->generateMockAdmin();
        $userUser = $this->generateMockUserUser();
        $userId = $userUser['user_id'];
        Poster::factory(['user_id' => $userId, 'admin_uuid' => $admin->getUuid()])->create();
        ThrottleConfig::factory(['admin_uuid' => $admin->getUuid()])->create();

        // 2. Mock the dependencies
        $this->mockUserAuthenticationService($admin);
        $this->generateMockUserUserSession($userUser);

        // 3. Execute the endpoint
        $response = $this->getJson($this->endpoint, $this->generateMockSocialApiHeaders($admin));

        // 4. Retrieve the created post from the database
        $throttleConfig = ThrottleConfig::find($response->json('data.id'));

        // 5. Get the expected data
        $expectedData = (new ThrottleConfigResource($throttleConfig))->resolve();

        // 6. Assertions
        $this->assert200ResponseWithSimpleResource($response, $this->throttleConfigResourceAttributes(), $expectedData);
    }

    /** @test */
    public function test_401_response_when_non_registered_user_retrieve_throttle_config_fails_due_to_unauthorized(): void
    {
        // 1. Generate the mock data
        $admin = $this->generateMockAdmin();

        // 3. Execute the endpoint
        $response = $this->getJson(
            $this->endpoint,
            $this->generateMockSocialApiHeaders($admin, false)
        );

        // 6. Assertions
        $this->assert401UnauthorizedResponse($response);
    }

    /** @test */
    public function test_404_response_when_retrieving_throttle_config_fails_due_to_resource_not_found(): void
    {
        // 1. Generate the mock data
        $admin = $this->generateMockAdmin();
        $userUser = $this->generateMockUserUser();
        $userId = $userUser['user_id'];
        Poster::factory(['user_id' => $userId, 'admin_uuid' => $admin->getUuid()])->create();

        // 2. Mock the dependencies
        $this->mockUserAuthenticationService($admin);
        $this->generateMockUserUserSession($userUser);

        // 3. Execute the endpoint
        $response = $this->getJson($this->endpoint, $this->generateMockSocialApiHeaders($admin));

        // 5. Assertions
        $this->assert404NotFoundResponse($response, Lang::get('throttleconfig::errors.throttle_config_not_found'));
    }
}
