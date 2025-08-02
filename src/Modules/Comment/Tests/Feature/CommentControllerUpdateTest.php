<?php

namespace Modules\Comment\Tests\Feature;

use Modules\Crm\Member\Constants\MemberStatuses;
use Modules\Crm\NGWord\Traits\NGWordGenerator;
use Illuminate\Support\Facades\Lang;
use Modules\Comment\app\Models\Comment;
use Modules\Post\app\Models\Post;
use Modules\Poster\app\Models\Poster;

/**
 * @group parallel
 */
class CommentControllerUpdateTest extends CommentControllerTestBase
{
    /** @test */
    public function test_200_response_commenter_can_update_own_comment(): void
    {
        // 1. Generate the mock data
        $admin = $this->generateMockAdmin();
        $userUser = $this->generateMockUserUser();
        $post = Post::factory()->create(['admin_uuid' => $admin->getUuid()]);

        // 2. Mock the dependencies
        $this->mockUserAuthenticationService($admin);
        $this->generateMockUserUserSession($userUser);
        $userId = $userUser['user_id'];
        $member = $this->generateMockCrmMember(['member_status' => MemberStatuses::REGULAR_MEMBER_STATUS, 'users' => ['user_id' => $userId]]);
        $this->generateMockCrmMembers([$member]);
        $user = $this->generateMockCrmUser(['user_id' => $userUser['user_id']]);
        $this->generateMockCrmUsers([$user]);

        $comment = Comment::factory(['post_id' => $post->id, 'is_hidden' => false, 'user_id' => $userId])->create();

        // 3. Update the comment
        $headers = $this->generateMockSocialApiHeaders($admin);
        $newCommentText = $this->faker->text;
        $response = $this->putJson("{$this->endpoint}/{$post->id}/comments/{$comment->id}", ['comment' => $newCommentText], $headers);

        // Get the updated comment data
        $updatedCommentData = $response->json('data');
        $updatedCommentData['nickname'] = $user['nickname'];
        $updatedCommentData['avatar'] = $user['profile_image'];

        // 4. Prepare expected data
        $this->assert200ResponseWithSimpleResource($response, $this->commentResourceAttributes(true), $updatedCommentData);
    }

    /** @test */
    public function test_200_response_poster_can_update_other_member_comment(): void
    {
        // 1. Generate the mock data
        $admin = $this->generateMockAdmin();
        $userUser = $this->generateMockUserUser();
        $userId = $userUser['user_id'];

        $poster = Poster::factory(['admin_uuid' => $admin->getUuid(), 'user_id' => $userId])->create();
        $post = Post::factory()->create(['admin_uuid' => $admin->getUuid(), 'poster_id' => $poster->id]);

        // 2. Mock the dependencies
        $this->mockUserAuthenticationService($admin);
        $this->generateMockUserUserSession($userUser);

        $user = $this->generateMockCrmUser(['user_id' => $userId]);
        $this->generateMockCrmUsers([$user]);

        $member = $this->generateMockCrmMember(['member_status' => MemberStatuses::REGULAR_MEMBER_STATUS, 'users' => ['user_id' => $user['user_id']]]);
        $this->generateMockCrmMembers([$member]);

        $comment = Comment::factory(['post_id' => $post->id, 'is_hidden' => false, 'user_id' => $user['user_id']])->create();

        // 3. Update the comment
        $headers = $this->generateMockSocialApiHeaders($admin);
        $newCommentText = $this->faker->text;
        $response = $this->putJson("{$this->endpoint}/{$post->id}/comments/{$comment->id}", ['comment' => $newCommentText], $headers);

        // Get the updated comment data
        $updatedCommentData = $response->json('data');
        $updatedCommentData['nickname'] = $user['nickname'];
        $updatedCommentData['avatar'] = $user['profile_image'];

        // 4. Prepare expected data
        $this->assert200ResponseWithSimpleResource($response, $this->commentResourceAttributes(true), $updatedCommentData);
    }

    /** @test */
    public function test_403_response_premium_member_cannot_update_other_member_comment(): void
    {
        // 1. Generate the mock data
        $admin = $this->generateMockAdmin();
        $userUser = $this->generateMockUserUser();

        $post = Post::factory()->create(['admin_uuid' => $admin->getUuid()]);
        $comment = Comment::factory(['post_id' => $post->id])->create();

        // 2. Mock the dependencies
        $this->mockUserAuthenticationService($admin);
        $this->generateMockUserUserSession($userUser);
        $this->generateMockCrmMembers([$this->generateMockCrmMember(['member_status' => MemberStatuses::REGULAR_MEMBER_STATUS])]);

        // 3. Execute the endpoint
        $headers = $this->generateMockSocialApiHeaders($admin);
        $response = $this->putJson("{$this->endpoint}/{$post->id}/comments/{$comment->id}", ['comment' => $this->faker->text], $headers);

        // 4. Assertions
        $this->assert403ForbiddenResponse($response, Lang::get('comment::errors.access_denied_edit_permission'));
    }

    /** @test */
    public function test_403_response_free_member_cannot_update_other_member_comment(): void
    {
        // 1. Generate the mock data
        $admin = $this->generateMockAdmin();
        $userUser = $this->generateMockUserUser();

        $post = Post::factory()->create(['admin_uuid' => $admin->getUuid()]);
        $comment = Comment::factory(['post_id' => $post->id])->create();

        // 2. Mock the dependencies
        $this->mockUserAuthenticationService($admin);
        $this->generateMockUserUserSession($userUser);
        $this->generateMockCrmMembers([$this->generateMockCrmMember(['member_status' => MemberStatuses::FREE_MEMBER_STATUS])]);

        // 3. Update the comment
        $headers = $this->generateMockSocialApiHeaders($admin);
        $response = $this->putJson("{$this->endpoint}/{$post->id}/comments/{$comment->id}", ['comment' => $this->faker->text], $headers);

        // 4. Assertions
        $this->assert403ForbiddenResponse($response, Lang::get('comment::errors.access_denied_edit_permission'));
    }

    /** @test */
    public function test_404_response_update_a_comment_fails_due_to_post_not_found(): void
    {
        // Mock Admin (merchant account that is stored in the Dynamo DB)
        $admin = $this->generateMockAdmin();
        $this->mockUserAuthenticationService($admin);

        // Mock User User and CRM member
        $userUser = $this->generateMockUserUser();
        $this->generateMockUserUserSession($userUser);
        $this->generateMockCrmMember();

        // Mock Social API's poster, comment
        Poster::factory(['admin_uuid' => $admin->getUuid(), 'user_id' => $userUser['user_id']])->create();
        $comment = Comment::factory()->create();
        $nonExistingPostId = 'non-existing-post-id';

        // Execute the endpoint
        $headers = $this->generateMockSocialApiHeaders($admin);
        $response = $this->putJson("{$this->endpoint}/{$nonExistingPostId}/comments/{$comment->id}", ['comment' => $this->faker->text], $headers);

        // Assertions
        $this->assert404NotFoundResponse($response, Lang::get('post::errors.post_item_not_found', ['id' => $nonExistingPostId]));
    }

    /** @test */
    public function test_404_response_update_a_comment_fails_due_to_comment_not_found(): void
    {
        // Mock Admin (merchant account that is stored in the Dynamo DB)
        $admin = $this->generateMockAdmin();
        $this->mockUserAuthenticationService($admin);

        // Mock User User and CRM member
        $userUser = $this->generateMockUserUser();
        $userId = $userUser['user_id'];
        $this->generateMockUserUserSession($userUser);
        $this->generateMockCrmMember();

        // Mock Social API's poster, comment
        $poster = Poster::factory(['admin_uuid' => $admin->getUuid(), 'user_id' => $userId])->create();
        $post = Post::factory()->create(['admin_uuid' => $admin->getUuid(), 'poster_id' => $poster->id]);
        $nonExistingCommentId = 'non-existing-id';

        // Execute the endpoint
        $headers = $this->generateMockSocialApiHeaders($admin);
        $response = $this->putJson("{$this->endpoint}/{$post->id}/comments/{$nonExistingCommentId}", ['comment' => $this->faker->text], $headers);

        // Assertions
        $this->assert404NotFoundResponse($response, Lang::get('comment::errors.comment_item_not_found', ['id' => $nonExistingCommentId]));
    }

    /** @test */
    public function test_400_response_premium_member_update_a_comment_fails_due_to_comment_contains_ng_word(): void
    {
        // Mock Admin and User User
        $admin = $this->generateMockAdmin();
        $this->mockUserAuthenticationService($admin);
        $userUser = $this->generateMockUserUser();
        $userId = $userUser['user_id'];
        $this->generateMockUserUserSession($userUser);

        // Mock CRM Member
        $member = $this->generateMockCrmMember(['member_status' => MemberStatuses::REGULAR_MEMBER_STATUS, 'users' => ['user_id' => $userId]]);
        $this->generateMockCrmMembers([$member]);

        // Mock Prohibited Words
        $ngWordList = NGWordGenerator::generate(100);
        $this->generateMockNGWords($ngWordList);
        $ngWord = $ngWordList[0];

        // Mock Poster, Post, and Comment
        Poster::factory(['admin_uuid' => $admin->getUuid()])->create();
        $post = Post::factory()->create(['admin_uuid' => $admin->getUuid()]);
        $comment = Comment::factory(['post_id' => $post->id, 'is_hidden' => false, 'user_id' => $userId])->create();

        // Execute the endpoint
        $response = $this->putJson(
            "{$this->endpoint}/{$post->id}/comments/{$comment->id}",
            ['comment' => $this->faker->text . ' ' . $ngWord . '。' . $this->faker->text],
            $this->generateMockSocialApiHeaders($admin)
        );

        // Assertions
        $expectedError = Lang::get('comment::errors.error_comment_contain_ng_word', ['word' => $ngWord]);
        $this->assert400BadRequestSimpleResponse($response, $expectedError);
    }

    /** @test */
    public function test_200_response_poster_update_comment_with_ng_word(): void
    {
        // Mock Admin and User User
        $admin = $this->generateMockAdmin();
        $this->mockUserAuthenticationService($admin);
        $userUser = $this->generateMockUserUser();
        $userId = $userUser['user_id'];
        $this->generateMockUserUserSession($userUser);

        // Mock CRM User and Member
        $user = $this->generateMockCrmUser(['user_id' => $userId]);
        $this->generateMockCrmUsers([$user]);
        $member = $this->generateMockCrmMember(['member_status' => MemberStatuses::REGULAR_MEMBER_STATUS, 'users' => ['user_id' => $userId]]);
        $this->generateMockCrmMembers([$member]);

        // Mock Prohibited Words
        $ngWordList = NGWordGenerator::generate(100);
        $this->generateMockNGWords($ngWordList);
        $ngWord = $ngWordList[0];

        // Mock Poster, Post, and Comment
        Poster::factory(['admin_uuid' => $admin->getUuid(), 'user_id' => $userId])->create();
        $post = Post::factory()->create(['admin_uuid' => $admin->getUuid()]);
        $comment = Comment::factory(['post_id' => $post->id, 'is_hidden' => false, 'user_id' => $userId])->create();

        // Execute the endpoint
        $response = $this->putJson(
            "{$this->endpoint}/{$post->id}/comments/{$comment->id}",
            ['comment' => $this->faker->text . ' ' . $ngWord . '。' . $this->faker->text],
            $this->generateMockSocialApiHeaders($admin)
        );

        // Get the updated comment data
        $updatedCommentData = $response->json('data');
        $updatedCommentData['nickname'] = $user['nickname'];
        $updatedCommentData['avatar'] = $user['profile_image'];

        // Assertions
        $this->assert200ResponseWithSimpleResource($response, $this->commentResourceAttributes(true), $updatedCommentData);
    }

    /** @test */
    public function test_401_response_non_registered_user_cannot_update_an_existing_comment_due_to_unauthorized(): void
    {
        // Mock Admin and User User
        $admin = $this->generateMockAdmin();
        $this->mockUserAuthenticationService($admin);
        $userUser = $this->generateMockUserUser();
        $userId = $userUser['user_id'];
        $this->generateMockUserUserSession($userUser);

        // Mock CRM User and Member
        $user = $this->generateMockCrmUser(['user_id' => $userId]);
        $this->generateMockCrmUsers([$user]);
        $member = $this->generateMockCrmMember(['member_status' => MemberStatuses::REGULAR_MEMBER_STATUS, 'users' => ['user_id' => $userId]]);
        $this->generateMockCrmMembers([$member]);

        // Mock Poster, Post, and Comment
        Poster::factory(['admin_uuid' => $admin->getUuid()])->create();
        $post = Post::factory()->create(['admin_uuid' => $admin->getUuid()]);
        $comment = Comment::factory(['post_id' => $post->id, 'is_hidden' => false, 'user_id' => $userId])->create();

        // Execute the endpoint
        $response = $this->putJson(
            "{$this->endpoint}/{$post->id}/comments/{$comment->id}",
            ['comment' => $this->faker->text],
            $this->generateMockSocialApiHeaders($admin, false)
        );

        // Assertions
        $this->assert401UnauthorizedResponse($response);
    }
}
