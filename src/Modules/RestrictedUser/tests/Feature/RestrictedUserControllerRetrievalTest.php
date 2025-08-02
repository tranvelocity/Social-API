<?php

namespace Modules\RestrictedUser\tests\Feature;

use Illuminate\Support\Facades\Lang;
use Modules\Poster\app\Models\Poster;
use Modules\RestrictedUser\app\Models\RestrictedUser;
use Modules\RestrictedUser\app\Resources\RestrictedUserResource;

/**
 * @group parallel
 */
final class RestrictedUserControllerRetrievalTest extends RestrictedUserControllerTestBase
{
    /** @test */
    public function test_200_response_when_retrieving_collection_of_restricted_users(): void
    {
        // 1. Generate the mock data
        $admin = $this->generateMockAdmin();
        $userUser = $this->generateMockUserUser();
        $userId = $userUser['user_id'];
        Poster::factory(['user_id' => $userId, 'admin_uuid' => $admin->getUuid()])->create();
        $restrictedUsers = RestrictedUser::factory(['admin_uuid' => $admin->getUuid()])->count($this->faker->numberBetween(1, 10))->create();

        // 2. Mock the dependencies
        $this->mockUserAuthenticationService($admin);
        $this->generateMockUserUserSession($userUser);

        // 3. Execute the endpoint
        $response = $this->getJson($this->endpoint, $this->generateMockSocialApiHeaders($admin));

        $resourceCollection = $restrictedUsers->map(function ($restrictedUser) {
            return new RestrictedUserResource($restrictedUser);
        });

        $expectedData = $resourceCollection->map(function ($restrictedUserResource) {
            return $restrictedUserResource->toArray(request());
        })->sortBy('created_at', 0, true)->values()->all();

        // 5. Assertions
        $this->assert200ResponseWithDataCollection($response, $this->restrictedUserResourceAttributes(), $expectedData);
    }

    /** @test */
    public function test_401_response_when_non_registered_user_retrieve_collection_of_restricted_users_fails_due_to_unauthorized(): void
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
    public function test_200_response_when_retrieving_a_restricted_user(): void
    {
        // 1. Generate the mock data
        $admin = $this->generateMockAdmin();
        $userUser = $this->generateMockUserUser();
        $userId = $userUser['user_id'];
        Poster::factory(['user_id' => $userId, 'admin_uuid' => $admin->getUuid()])->create();
        $restrictedUser = RestrictedUser::factory(['admin_uuid' => $admin->getUuid()])->create();

        // 2. Mock the dependencies
        $this->mockUserAuthenticationService($admin);
        $this->generateMockUserUserSession($userUser);

        // 3. Execute the endpoint
        $response = $this->getJson($this->endpoint . '/' . $restrictedUser->id, $this->generateMockSocialApiHeaders($admin));

        // 4. Get the expected data
        $expectedData = (new RestrictedUserResource($restrictedUser))->resolve();

        // 5. Assertions
        $this->assert200ResponseWithSimpleResource($response, $this->restrictedUserResourceAttributes(), $expectedData);
    }

    /** @test */
    public function test_401_response_when_non_registered_user_retrieve_restricted_user_fails_due_to_unauthorized(): void
    {
        // 1. Generate the mock data
        $admin = $this->generateMockAdmin();
        $restrictedUser = RestrictedUser::factory(['admin_uuid' => $admin->getUuid()])->create();

        // 3. Execute the endpoint
        $response = $this->getJson($this->endpoint . '/' . $restrictedUser->id, $this->generateMockSocialApiHeaders($admin, false));

        // 6. Assertions
        $this->assert401UnauthorizedResponse($response);
    }

    /** @test */
    public function test_404_response_when_retrieving_a_restricted_user_fails_due_to_resource_not_found(): void
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
        $nonExistingId = 'non-existing-id';
        $response = $this->getJson($this->endpoint . '/' . $nonExistingId, $this->generateMockSocialApiHeaders($admin));

        // 4. Assertions
        $this->assert404NotFoundResponse($response, Lang::get('restricteduser::errors.restricted_user_item_not_found', ['id' => $nonExistingId]));
    }
}
