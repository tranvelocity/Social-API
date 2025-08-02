<?php

namespace Modules\Comment\Tests\Feature;

use Illuminate\Support\Facades\Lang;
use Modules\Comment\app\Models\Comment;
use Modules\Comment\app\Resources\CommentResource;
use Modules\Post\app\Models\Post;
use Modules\Poster\app\Models\Poster;

/**
 * @group parallel
 */
class CommentControllerRetrievalTest extends CommentControllerTestBase
{
    /** @test */
    public function test_200_response_premium_member_retrieve_list_of_comments(): void
    {
        // 1. Generate the mock data
        $admin = $this->generateMockAdmin();
        $userUser = $this->generateMockUserUser();
        $poster = Poster::factory(['admin_uuid' => $admin->getUuid()])->create();
        $post = Post::factory()->create(['admin_uuid' => $admin->getUuid(), 'poster_id' => $poster->id]);
        $comments = Comment::factory(['post_id' => $post->id, 'is_hidden' => false])->count(10)->create();

        // 2. Mock the dependencies
        $this->mockUserAuthenticationService($admin);
        $this->generateMockUserUserSession($userUser);
        $this->generateMockCrmMember(['member_status' => MemberStatuses::REGULAR_MEMBER_STATUS]);

        // 3. Request the list of comments
        $headers = $this->generateMockSocialApiHeaders($admin);
        $response = $this->getJson("{$this->endpoint}/{$post->id}/comments", $headers);

        // 4. Prepare the expected data
        $expectedData = $comments
            ->reject->is_hidden
            ->map(fn ($comment) => (new CommentResource($comment, false))->toArray(request()))
            ->sortByDesc('created_at')
            ->values()
            ->all();

        // 5. Assertions
        $this->assert200ResponseWithDataCollection($response, $this->commentResourceAttributes(), $expectedData);
    }

    /** @test */
    public function test_200_response_free_member_retrieve_list_of_comments(): void
    {
        // 1. Generate the mock data
        $admin = $this->generateMockAdmin();
        $userUser = $this->generateMockUserUser();
        $poster = Poster::factory(['admin_uuid' => $admin->getUuid()])->create();
        $post = Post::factory()->create(['admin_uuid' => $admin->getUuid(), 'poster_id' => $poster->id]);
        $comments = Comment::factory(['post_id' => $post->id, 'is_hidden' => false])->count(10)->create();

        // 2. Mock the dependencies
        $this->mockUserAuthenticationService($admin);
        $this->generateMockUserUserSession($userUser);
        $this->generateMockCrmMember(['member_status' => MemberStatuses::FREE_MEMBER_STATUS]);

        // 3. Request the list of comments
        $headers = $this->generateMockSocialApiHeaders($admin);
        $response = $this->getJson("{$this->endpoint}/{$post->id}/comments", $headers);

        // 4. Prepare the expected data
        $expectedData = $comments
            ->reject->is_hidden
            ->map(fn ($comment) => (new CommentResource($comment, false))->toArray(request()))
            ->sortByDesc('created_at')
            ->values()
            ->all();

        // 5. Assertions
        $this->assert200ResponseWithDataCollection($response, $this->commentResourceAttributes(), $expectedData);
    }

    /** @test */
    public function test_200_response_poster_retrieve_list_of_comments(): void
    {
        // 1. Generate the mock data
        $admin = $this->generateMockAdmin();
        $userUser = $this->generateMockUserUser();
        $poster = Poster::factory(['admin_uuid' => $admin->getUuid(), 'user_id' => $userUser['user_id']])->create();
        $post = Post::factory()->create(['admin_uuid' => $admin->getUuid(), 'poster_id' => $poster->id]);
        $comments = Comment::factory(['post_id' => $post->id, 'is_hidden' => false])->count(10)->create();

        // 2. Mock the dependencies
        $this->mockUserAuthenticationService($admin);
        $this->generateMockUserUserSession($userUser);
        $this->generateMockCrmMember();

        // 3. Request the list of comments
        $headers = $this->generateMockSocialApiHeaders($admin);
        $response = $this->getJson("{$this->endpoint}/{$post->id}/comments", $headers);

        // 4. Prepare the expected data
        $expectedData = $comments
            ->reject->is_hidden
            ->map(fn ($comment) => (new CommentResource($comment, false))->toArray(request()))
            ->sortByDesc('created_at')
            ->values()
            ->all();

        // 5. Assertions
        $this->assert200ResponseWithDataCollection($response, $this->commentResourceAttributes(), $expectedData);
    }

    /** @test */
    public function test_200_response_poster_retrieve_a_comment(): void
    {
        // 1. Generate the mock data
        $admin = $this->generateMockAdmin();
        $userUser = $this->generateMockUserUser();
        $userId = $userUser['user_id'];
        $poster = Poster::factory(['admin_uuid' => $admin->getUuid(), 'user_id' => $userId])->create();
        $post = Post::factory()->create(['admin_uuid' => $admin->getUuid(), 'poster_id' => $poster->id]);
        $comment = Comment::factory(['post_id' => $post->id, 'is_hidden' => false])->create();

        // 2. Mock the dependencies
        $this->mockUserAuthenticationService($admin);
        $this->generateMockUserUserSession($userUser);
        $member = $this->generateMockCrmMember(['users' => ['user_id' => $userId]]);
        $this->generateMockCrmMembers([$member]);
        $user = $this->generateMockCrmUser(['user_id' => $userUser['user_id']]);
        $this->generateMockCrmUsers([$user]);

        // 3. Request the list of comments
        $headers = $this->generateMockSocialApiHeaders($admin);
        $response = $this->getJson("{$this->endpoint}/{$post->id}/comments/{$comment->id}", $headers);

        // 4. Prepare expected data
        $expectedData = $response->json('data');
        $expectedData['nickname'] = $user['nickname'];
        $expectedData['avatar'] = $user['profile_image'];

        // 5. Assertions
        $this->assert200ResponseWithSimpleResource($response, $this->commentResourceAttributes(true), $expectedData);
    }

    /** @test */
    public function test_200_response_premium_member_retrieve_a_comment(): void
    {
        // 1. Generate the mock data
        $admin = $this->generateMockAdmin();
        $userUser = $this->generateMockUserUser();
        $post = Post::factory()->create(['admin_uuid' => $admin->getUuid()]);
        $comment = Comment::factory(['post_id' => $post->id, 'is_hidden' => false])->create();

        // 2. Mock the dependencies
        $this->mockUserAuthenticationService($admin);
        $this->generateMockUserUserSession($userUser);
        $member = $this->generateMockCrmMember(['member_status' => MemberStatuses::REGULAR_MEMBER_STATUS, 'users' => ['user_id' => $userUser['user_id']]]);
        $this->generateMockCrmMembers([$member]);
        $user = $this->generateMockCrmUser(['user_id' => $userUser['user_id']]);
        $this->generateMockCrmUsers([$user]);

        // 3. Request the list of comments
        $headers = $this->generateMockSocialApiHeaders($admin);
        $response = $this->getJson("{$this->endpoint}/{$post->id}/comments/{$comment->id}", $headers);

        // 4. Prepare expected data
        $expectedData = $response->json('data');
        $expectedData['nickname'] = $user['nickname'];
        $expectedData['avatar'] = $user['profile_image'];

        // 5. Assertions
        $this->assert200ResponseWithSimpleResource($response, $this->commentResourceAttributes(true), $expectedData);
    }

    /** @test */
    public function test_200_response_free_member_retrieve_a_comment(): void
    {
        // 1. Generate the mock data
        $admin = $this->generateMockAdmin();
        $userUser = $this->generateMockUserUser();
        $post = Post::factory()->create(['admin_uuid' => $admin->getUuid()]);
        $comment = Comment::factory(['post_id' => $post->id, 'is_hidden' => false])->create();

        // 2. Mock the dependencies
        $this->mockUserAuthenticationService($admin);
        $this->generateMockUserUserSession($userUser);
        $member = $this->generateMockCrmMember(['member_status' => MemberStatuses::FREE_MEMBER_STATUS, 'users' => ['user_id' => $userUser['user_id']]]);
        $this->generateMockCrmMembers([$member]);
        $user = $this->generateMockCrmUser(['user_id' => $userUser['user_id']]);
        $this->generateMockCrmUsers([$user]);

        // 3. Request the list of comments
        $headers = $this->generateMockSocialApiHeaders($admin);
        $response = $this->getJson("{$this->endpoint}/{$post->id}/comments/{$comment->id}", $headers);

        // 4. Prepare expected data
        $expectedData = $response->json('data');
        $expectedData['nickname'] = $user['nickname'];
        $expectedData['avatar'] = $user['profile_image'];

        // 5. Assertions
        $this->assert200ResponseWithSimpleResource($response, $this->commentResourceAttributes(true), $expectedData);
    }

    /** @test */
    public function test_404_response_retrieve_list_of_comments_fails_due_to_post_not_found(): void
    {
        // 1. Generate the mock data
        $admin = $this->generateMockAdmin();
        $userUser = $this->generateMockUserUser();
        $nonExistingPostId = 'non-existing-post-id';

        // 2. Mock the dependencies
        $this->mockUserAuthenticationService($admin);
        $this->generateMockUserUserSession($userUser);
        $this->generateMockCrmMember(['member_status' => MemberStatuses::REGULAR_MEMBER_STATUS]);

        // 3. Request the list of comments
        $headers = $this->generateMockSocialApiHeaders($admin);
        $response = $this->getJson("{$this->endpoint}/{$nonExistingPostId}/comments", $headers);

        // 4. Assertions
        $this->assert404NotFoundResponse($response, Lang::get('post::errors.post_item_not_found', ['id' => $nonExistingPostId]));
    }

    /** @test */
    public function test_404_response_retrieve_a_specific_comment_fails_due_to_post_not_found(): void
    {
        // 1. Generate the mock data
        $admin = $this->generateMockAdmin();
        $userUser = $this->generateMockUserUser();
        $poster = Poster::factory(['admin_uuid' => $admin->getUuid()])->create();
        $post = Post::factory()->create(['admin_uuid' => $admin->getUuid(), 'poster_id' => $poster->id]);
        $comment = Comment::factory(['post_id' => $post->id])->create();
        $nonExistingPostId = 'non-existing-post-id';

        // 2. Mock the dependencies
        $this->mockUserAuthenticationService($admin);
        $this->generateMockUserUserSession($userUser);
        $this->generateMockCrmMember(['member_status' => MemberStatuses::REGULAR_MEMBER_STATUS]);

        // 3. Request the list of comments
        $headers = $this->generateMockSocialApiHeaders($admin);
        $response = $this->getJson("{$this->endpoint}/{$nonExistingPostId}/comments/$comment->id", $headers);

        // 4. Assertions
        $this->assert404NotFoundResponse($response, Lang::get('post::errors.post_item_not_found', ['id' => $nonExistingPostId]));
    }

    /** @test */
    public function test_404_response_retrieve_a_specific_comment_fails_due_to_comment_not_found(): void
    {
        // 1. Generate the mock data
        $admin = $this->generateMockAdmin();
        $userUser = $this->generateMockUserUser();
        $poster = Poster::factory(['admin_uuid' => $admin->getUuid()])->create();
        $post = Post::factory()->create(['admin_uuid' => $admin->getUuid(), 'poster_id' => $poster->id]);
        $nonExistingCommentId = 'non-existing-comment-id';

        // 2. Mock the dependencies
        $this->mockUserAuthenticationService($admin);
        $this->generateMockUserUserSession($userUser);
        $this->generateMockCrmMember(['member_status' => MemberStatuses::REGULAR_MEMBER_STATUS]);

        // 3. Request the list of comments
        $headers = $this->generateMockSocialApiHeaders($admin);
        $response = $this->getJson("{$this->endpoint}/{$post->id}/comments/$nonExistingCommentId", $headers);

        // 4. Assertions
        $this->assert404NotFoundResponse($response, Lang::get('comment::errors.comment_item_not_found', ['id' => $nonExistingCommentId]));
    }

    /** @test */
    public function test_401_response_cannot_get_a_comment_due_to_unauthorized(): void
    {
        // 1. Generate the mock data
        $admin = $this->generateMockAdmin();
        $userUser = $this->generateMockUserUser();
        $poster = Poster::factory(['admin_uuid' => $admin->getUuid()])->create();
        $post = Post::factory()->create(['admin_uuid' => $admin->getUuid(), 'poster_id' => $poster->id]);
        $comment = Comment::factory(['post_id' => $post->id, 'is_hidden' => false])->create();

        // 2. Mock the dependencies
        $this->mockUserAuthenticationService($admin);
        $this->generateMockUserUserSession($userUser);
        $this->generateMockCrmMember(['member_status' => MemberStatuses::REGULAR_MEMBER_STATUS]);

        // 3. Request the list of comments
        $headers = $this->generateMockSocialApiHeaders($admin, false);
        $response = $this->getJson("{$this->endpoint}/{$post->id}/comments/$comment->id", $headers);

        $this->assert401UnauthorizedResponse($response);
    }

    /** @test */
    public function test_401_response_cannot_get_list_of_comment_due_to_unauthorized(): void
    {
        // 1. Generate the mock data
        $admin = $this->generateMockAdmin();
        $userUser = $this->generateMockUserUser();
        $poster = Poster::factory(['admin_uuid' => $admin->getUuid()])->create();
        $post = Post::factory()->create(['admin_uuid' => $admin->getUuid(), 'poster_id' => $poster->id]);
        Comment::factory(['post_id' => $post->id, 'is_hidden' => false])->count(10)->create();

        // 2. Mock the dependencies
        $this->mockUserAuthenticationService($admin);
        $this->generateMockUserUserSession($userUser);
        $this->generateMockCrmMember(['member_status' => MemberStatuses::REGULAR_MEMBER_STATUS]);

        // 3. Request the list of comments
        $headers = $this->generateMockSocialApiHeaders($admin, false);
        $response = $this->getJson("{$this->endpoint}/{$post->id}/comments", $headers);

        $this->assert401UnauthorizedResponse($response);
    }
}
