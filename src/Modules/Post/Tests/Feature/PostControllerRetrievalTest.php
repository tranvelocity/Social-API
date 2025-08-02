<?php

namespace Modules\Post\Tests\Feature;

use Modules\Crm\Member\Constants\MemberStatuses;
use Illuminate\Support\Facades\Lang;
use Modules\Post\app\Models\Post;
use Modules\Post\app\Resources\PostResource;
use Modules\Poster\app\Models\Poster;

/**
 * @group parallel
 */
class PostControllerRetrievalTest extends PostControllerTestBase
{
    /** @test */
    public function test_200_response_poster_can_retrieve_a_post(): void
    {
        // 1. Generate the mock data
        $admin = $this->generateMockAdmin();
        $userUser = $this->generateMockUserUser();
        $userId = $userUser['user_id'];
        $poster = Poster::factory(['user_id' => $userId, 'admin_uuid' => $admin->getUuid()])->create();
        $post = Post::factory()->create([
            'admin_uuid' => $admin->getUuid(),
            'poster_id' => $poster->id,
        ]);

        // 2. Mock the dependencies
        $this->mockUserAuthenticationService($admin);
        $this->generateMockUserUserSession($userUser);

        // 3. Execute the endpoint
        $response = $this->getJson($this->endpoint . '/' . $post->id, $this->generateMockSocialApiHeaders($admin));

        // 4. Extract expected data
        $expectedData = PostResource::make($post)->resolve();
        $expectedData['poster'] = [
            'id' => $poster->id,
            'user_id' => $poster->user_id,
            'nickname' => $poster->getNickname(),
            'avatar' => $poster->getAvatar(),
        ];

        // 5. Assertions
        $this->assert200ResponseWithSimpleResource($response, $this->simplePostResourceAttributes(), $expectedData);
    }

    /** @test */
    public function test_403_response_premium_member_retrieve_a_post_fails_due_to_access_denied(): void
    {
        // Mock Admin (merchant account that is stored in the Dynamo DB)
        $admin = $this->generateMockAdmin();
        $this->mockUserAuthenticationService($admin);

        // Mock User User
        $userUser = $this->generateMockUserUser();
        $this->generateMockUserUserSession($userUser);

        // Mock CRM Member
        $member = $this->generateMockCrmMember(['member_status' => MemberStatuses::REGULAR_MEMBER_STATUS, 'users' => ['user_id' => $userUser['user_id']]]);
        $this->generateMockCrmMembers([$member]);

        $post = Post::factory(['admin_uuid' => $admin->getUuid()])->create();

        // Execute the endpoint
        $response = $this->getJson($this->endpoint . '/' . $post->id, $this->generateMockSocialApiHeaders($admin));

        // Assertions
        $this->assert403ForbiddenResponse($response, Lang::get('post::errors.access_denied_retrieval_item_permission'));
    }

    /** @test */
    public function test_403_response_free_member_retrieve_a_post_fails_due_to_access_denied(): void
    {
        // Mock Admin (merchant account that is stored in the Dynamo DB)
        $admin = $this->generateMockAdmin();
        $this->mockUserAuthenticationService($admin);

        // Mock User User
        $userUser = $this->generateMockUserUser();
        $this->generateMockUserUserSession($userUser);

        // Mock CRM Member
        $member = $this->generateMockCrmMember(['member_status' => MemberStatuses::FREE_MEMBER_STATUS, 'users' => ['user_id' => $userUser['user_id']]]);
        $this->generateMockCrmMembers([$member]);

        $post = Post::factory(['admin_uuid' => $admin->getUuid()])->create();

        // Execute the endpoint
        $response = $this->getJson($this->endpoint . '/' . $post->id, $this->generateMockSocialApiHeaders($admin));

        // Assertions
        $this->assert403ForbiddenResponse($response, Lang::get('post::errors.access_denied_retrieval_item_permission'));
    }

    /** @test */
    public function test_403_response_non_registered_user_retrieve_a_post_fails_due_to_unauthorized(): void
    {
        // Mock Admin (merchant account that is stored in the Dynamo DB)
        $admin = $this->generateMockAdmin();
        $this->mockUserAuthenticationService($admin);

        // Mock User User
        $userUser = $this->generateMockUserUser();
        $this->generateMockUserUserSession($userUser);

        $post = Post::factory(['admin_uuid' => $admin->getUuid()])->create();

        // Execute the endpoint
        $response = $this->getJson($this->endpoint . '/' . $post->id, $this->generateMockSocialApiHeaders($admin, false));

        // Assertions
        $this->assert401UnauthorizedResponse($response);
    }

    /** @test */
    public function test_404_response_retrieve_a_specific_post_fails_due_to_post_not_found(): void
    {
        // 1. Generate the mock data
        $admin = $this->generateMockAdmin();
        $userUser = $this->generateMockUserUser();
        $userId = $userUser['user_id'];
        $poster = Poster::factory(['user_id' => $userId, 'admin_uuid' => $admin->getUuid()])->create();

        // 2. Mock the dependencies
        $this->mockUserAuthenticationService($admin);
        $this->generateMockUserUserSession($userUser);

        $id = 'non-existing-id';

        // 3. Execute the endpoint
        $response = $this->getJson($this->endpoint . '/' . $id, $this->generateMockSocialApiHeaders($admin));

        // 4. Assertions
        $this->assert404NotFoundResponse($response, Lang::get('post::errors.post_item_not_found', ['id' => $id]));
    }
}
