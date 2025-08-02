<?php

namespace Modules\RestrictedUser\tests\Feature;

use Illuminate\Support\Facades\Lang;
use Modules\Poster\app\Models\Poster;
use Modules\RestrictedUser\app\Models\RestrictedUser;

/**
 * @group parallel
 */
final class RestrictedUserControllerDeletionTest extends RestrictedUserControllerTestBase
{
    /** @test */
    public function test_204_response_when_deleting_a_restricted_user_successfully(): void
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
        $response = $this->deleteJson("{$this->endpoint}/{$restrictedUser->id}", [], $this->generateMockSocialApiHeaders($admin));

        // 4. Assertions
        $this->assert204NoContentResponse($response);
    }

    /** @test */
    public function test_401_response_when_non_registered_user_deletes_a_restricted_users_fails_due_to_unauthorized(): void
    {
        // 1. Generate the mock data
        $admin = $this->generateMockAdmin();

        // 2. Execute the endpoint
        $restrictedUser = RestrictedUser::factory(['admin_uuid' => $admin->getUuid()])->create();
        $response = $this->deleteJson($this->endpoint . "/{$restrictedUser->id}", [], $this->generateMockSocialApiHeaders($admin, false));

        // 3. Assertions
        $this->assert401UnauthorizedResponse($response);
    }

    /** @test */
    public function test_404_response_when_deleting_a_restricted_user_fails_due_to_resource_not_found(): void
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
        $response = $this->deleteJson($this->endpoint . '/' . $nonExistingId, [], $this->generateMockSocialApiHeaders($admin));

        // 4. Assertions
        $this->assert404NotFoundResponse($response, Lang::get('restricteduser::errors.restricted_user_item_not_found', ['id' => $nonExistingId]));
    }
}
