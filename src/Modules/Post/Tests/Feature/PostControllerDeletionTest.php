<?php

namespace Modules\Post\Tests\Feature;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Lang;
use Modules\Core\app\Exceptions\FatalErrorException;
use Modules\Crm\Member\Constants\MemberStatuses;
use Modules\Post\app\Models\Post;
use Modules\Post\app\Services\PostService;
use Modules\Poster\app\Models\Poster;

/**
 * @group parallel
 */
class PostControllerDeletionTest extends PostControllerTestBase
{
    /** @test */
    public function test_204_response_poster_can_delete_a_specific_post(): void
    {
        // 1. Generate the mock data for admin and poster.
        $admin = $this->generateMockAdmin();
        $userUser = $this->generateMockUserUser();
        $userId = $userUser['user_id'];
        $poster = Poster::factory(['user_id' => $userId, 'admin_uuid' => $admin->getUuid()])->create();
        $post = Post::factory(['admin_uuid' => $admin->getUuid(), 'poster_id' => $poster->id])->create();

        // 2. Mock the dependencies, including the account service and user merchant session.
        $this->mockUserAuthenticationService($admin);
        $this->generateMockUserUserSession($userUser);

        // 3. Execute the endpoint or method that uses the mocked service or class to delete the post.
        $response = $this->deleteJson("{$this->endpoint}/{$post->id}", [], $this->generateMockSocialApiHeaders($admin));

        // 4. Assertions: Ensure that the response is a 204 No Content response.
        $this->assert204NoContentResponse($response);
    }

    /** @test */
    public function test_403_response_premium_member_cannot_delete_a_specific_post_due_to_access_denied(): void
    {
        // Mock Admin (merchant account that is stored in the Dynamo DB)
        $admin = $this->generateMockAdmin();
        $this->mockUserAuthenticationService($admin);

        // Mock User User and CRM member
        $userUser = $this->generateMockUserUser();
        $userId = $userUser['user_id'];
        $this->generateMockUserUserSession($userUser);

        // Mock CRM Member
        $member = $this->generateMockCrmMember(['member_status' => MemberStatuses::REGULAR_MEMBER_STATUS, 'users' => ['user_id' => $userId]]);
        $this->generateMockCrmMembers([$member]);

        // Mock Social API's post
        $post = Post::factory(['admin_uuid' => $admin->getUuid()])->create();

        // Execute the endpoint or method that uses the mocked service or class to delete the post.
        $response = $this->deleteJson("{$this->endpoint}/{$post->id}", [], $this->generateMockSocialApiHeaders($admin));

        // Assertions
        $this->assert403ForbiddenResponse($response, Lang::get('post::errors.access_denied_deletion_permission'));
    }

    /** @test */
    public function test_403_response_free_member_cannot_delete_a_specific_post_due_to_access_denied(): void
    {
        // Mock Admin (merchant account that is stored in the Dynamo DB)
        $admin = $this->generateMockAdmin();
        $this->mockUserAuthenticationService($admin);

        // Mock User User and CRM member
        $userUser = $this->generateMockUserUser();
        $userId = $userUser['user_id'];
        $this->generateMockUserUserSession($userUser);

        // Mock CRM Member
        $member = $this->generateMockCrmMember(['member_status' => MemberStatuses::FREE_MEMBER_STATUS, 'users' => ['user_id' => $userId]]);
        $this->generateMockCrmMembers([$member]);

        // Mock Social API's post
        $post = Post::factory(['admin_uuid' => $admin->getUuid()])->create();

        // Execute the endpoint or method that uses the mocked service or class to delete the post.
        $response = $this->deleteJson("{$this->endpoint}/{$post->id}", [], $this->generateMockSocialApiHeaders($admin));

        // Assertions
        $this->assert403ForbiddenResponse($response, Lang::get('post::errors.access_denied_deletion_permission'));
    }

    /** @test */
    public function test_401_response_non_registered_user_cannot_delete_a_specific_post_due_to_unauthorized(): void
    {
        // Mock Admin (merchant account that is stored in the Dynamo DB)
        $admin = $this->generateMockAdmin();
        $this->mockUserAuthenticationService($admin);

        // Mock User User and CRM member
        $userUser = $this->generateMockUserUser();
        $userId = $userUser['user_id'];
        $this->generateMockUserUserSession($userUser);

        // Mock Social API's post
        $post = Post::factory(['admin_uuid' => $admin->getUuid()])->create();

        // Execute the endpoint or method that uses the mocked service or class to delete the post.
        $response = $this->deleteJson("{$this->endpoint}/{$post->id}", [], $this->generateMockSocialApiHeaders($admin, false));

        // Assertions
        $this->assert401UnauthorizedResponse($response);
    }

    /** @test */
    public function test_404_response_delete_a_specific_post_fails_due_to_post_not_found(): void
    {
        // 1. Generate the mock data for admin and poster.
        $admin = $this->generateMockAdmin();
        $userUser = $this->generateMockUserUser();
        $userId = $userUser['user_id'];
        $poster = Poster::factory(['user_id' => $userId, 'admin_uuid' => $admin->getUuid()])->create();

        // 2. Mock the dependencies, including the account service and user merchant session.
        $this->mockUserAuthenticationService($admin);
        $this->generateMockUserUserSession($userUser);

        // 3. Attempt to delete a non-existing post by providing a non-existing poster ID.
        $nonExistingPosterId = 'non-exist-poster-id';
        $endpoint = $this->endpoint . '/' . $nonExistingPosterId;
        $response = $this->deleteJson($endpoint, [], $this->generateMockSocialApiHeaders($admin));

        // 4. Assertions: Ensure that the response is a 404 Not Found, indicating the post is not found.
        $this->assert404NotFoundResponse($response, Lang::get('post::errors.post_item_not_found', ['id' => $nonExistingPosterId]));
    }

    /** @test */
    public function test_500_response_delete_a_specific_post_fails_due_to_fatal_error_occurred(): void
    {
        // 1. Generate the mock data for admin and poster.
        $admin = $this->generateMockAdmin();
        $userUser = $this->generateMockUserUser();
        $userId = $userUser['user_id'];
        $poster = Poster::factory(['user_id' => $userId, 'admin_uuid' => $admin->getUuid()])->create();

        // 2. Mock the dependencies, including the account service and user merchant session.
        $this->mockUserAuthenticationService($admin);
        $this->generateMockUserUserSession($userUser);

        // 3. Mock the service or class that may throw a FatalErrorException.
        $this->mock(PostService::class, function ($mock) {
            $mock->shouldReceive('deletePost')
                ->andThrow(new FatalErrorException());
        });

        // 4. Attempt to delete a post, and the service or class throws a FatalErrorException.
        $response = $this->deleteJson($this->endpoint . '/1', [], $this->generateMockSocialApiHeaders($admin));

        // 5. Assertions: Ensure that the response is a 500 Internal Server Error.
        $this->assert500FailureResponse($response, Config::get('tranauth.exceptions.500.500001'));
    }
}
