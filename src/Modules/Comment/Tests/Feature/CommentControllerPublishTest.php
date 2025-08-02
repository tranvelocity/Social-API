<?php

namespace Modules\Comment\Tests\Feature;

use Illuminate\Support\Facades\Lang;
use Illuminate\Testing\TestResponse;
use Modules\Comment\app\Models\Comment;
use Symfony\Component\HttpFoundation\Response;

/**
 * @group parallel
 */
class CommentControllerPublishTest extends CommentControllerTestBase
{
    /**
     * Publish a comment and return the response.
     *
     * @param  string $postId
     * @param  string $commentId
     * @param  array $headers
     * @return TestResponse
     */
    private function publishComment(string $postId, string $commentId, array $headers): TestResponse
    {
        return $this->postJson("{$this->endpoint}/{$postId}/comments/{$commentId}/publish", [], $headers);
    }

    /**
     * UnPublish a comment and return the response.
     *
     * @param  string $postId
     * @param  string $commentId
     * @param  array $headers
     * @return TestResponse
     */
    private function unPublishComment(string $postId, string $commentId, array $headers): TestResponse
    {
        return $this->postJson("{$this->endpoint}/{$postId}/comments/{$commentId}/unpublish", [], $headers);
    }

    /** @test */
    public function test_403_response_paid_member_cannot_unpublish_a_comment(): void
    {
        // 1. Generate the mock data
        $admin = $this->generateMockAdmin();
        $userUser = $this->generateMockUserUser();
        $poster = $this->createPosterWithAdmin($admin);
        $post = $this->createPostWithAdmin($admin, $poster);
        $comment = Comment::factory(['post_id' => $post->id])->create();

        // 2. Mock the dependencies
        $this->mockUserAuthenticationService($admin);
        $this->generateMockUserUserSession($userUser);
        $this->generateMockCrmMember(['member_status' => MemberStatuses::REGULAR_MEMBER_STATUS]);

        // 3. Execute the endpoint
        $headers = $this->generateMockSocialApiHeaders($admin);
        $response = $this->postJson("{$this->endpoint}/{$post->id}/comments/{$comment->id}/unpublish", [], $headers);

        // 4. Assertions
        $this->assert403ForbiddenResponse($response, Lang::get('comment::errors.access_denied_unpublish_permission'));
    }

    /** @test */
    public function test_403_response_free_member_cannot_unpublish_a_comment(): void
    {
        // 1. Generate the mock data
        $admin = $this->generateMockAdmin();
        $userUser = $this->generateMockUserUser();
        $poster = $this->createPosterWithAdmin($admin);
        $post = $this->createPostWithAdmin($admin, $poster);
        $comment = Comment::factory(['post_id' => $post->id])->create();

        // 2. Mock the dependencies
        $this->mockUserAuthenticationService($admin);
        $this->generateMockUserUserSession($userUser);
        $this->generateMockCrmMembers([$this->generateMockCrmMember(['member_status' => MemberStatuses::FREE_MEMBER_STATUS])]);

        // 3. Execute the endpoint
        $headers = $this->generateMockSocialApiHeaders($admin);
        $response = $this->postJson("{$this->endpoint}/{$post->id}/comments/{$comment->id}/unpublish", [], $headers);

        // 4. Assertions
        $this->assert403ForbiddenResponse($response, Lang::get('comment::errors.access_denied_unpublish_permission'));
    }

    /** @test */
    public function test_403_response_paid_member_cannot_publish_a_comment(): void
    {
        // 1. Generate the mock data
        $admin = $this->generateMockAdmin();
        $userUser = $this->generateMockUserUser();
        $poster = $this->createPosterWithAdmin($admin);
        $post = $this->createPostWithAdmin($admin, $poster);
        $comment = Comment::factory(['post_id' => $post->id])->create();

        // 2. Mock the dependencies
        $this->mockUserAuthenticationService($admin);
        $this->generateMockUserUserSession($userUser);
        $this->generateMockCrmMember(['member_status' => MemberStatuses::REGULAR_MEMBER_STATUS]);

        // 3. Execute the endpoint
        $headers = $this->generateMockSocialApiHeaders($admin);
        $response = $this->postJson("{$this->endpoint}/{$post->id}/comments/{$comment->id}/publish", [], $headers);

        // 4. Assertions
        $this->assert403ForbiddenResponse($response, Lang::get('comment::errors.access_denied_publish_permission'));
    }

    /** @test */
    public function test_403_response_free_member_cannot_publish_a_comment(): void
    {
        // 1. Generate the mock data
        $admin = $this->generateMockAdmin();
        $userUser = $this->generateMockUserUser();
        $poster = $this->createPosterWithAdmin($admin);
        $post = $this->createPostWithAdmin($admin, $poster);
        $comment = Comment::factory(['post_id' => $post->id])->create();

        // 2. Mock the dependencies
        $this->mockUserAuthenticationService($admin);
        $this->generateMockUserUserSession($userUser);
        $this->generateMockCrmMembers([$this->generateMockCrmMember(['member_status' => MemberStatuses::FREE_MEMBER_STATUS])]);

        // 3. Execute the endpoint
        $headers = $this->generateMockSocialApiHeaders($admin);
        $response = $this->postJson("{$this->endpoint}/{$post->id}/comments/{$comment->id}/publish", [], $headers);

        // 4. Assertions
        $this->assert403ForbiddenResponse($response, Lang::get('comment::errors.access_denied_publish_permission'));
    }

    /** @test */
    public function test_200_response_poster_can_publish_a_comment(): void
    {
        // 1. Generate the mock data
        $admin = $this->generateMockAdmin();
        $userUser = $this->generateMockUserUser();
        $userId = $userUser['user_id'];
        $poster = $this->createPosterWithAdminAndUserId($admin, $userId);
        $post = $this->createPostWithAdmin($admin, $poster);
        $comment = Comment::factory(['post_id' => $post->id])->create();

        // 2. Mock the dependencies
        $this->mockUserAuthenticationService($admin);
        $this->generateMockUserUserSession($userUser);
        $member = $this->generateMockCrmMember(['member_status' => MemberStatuses::REGULAR_MEMBER_STATUS, 'users' => ['user_id' => $userId]]);
        $this->generateMockCrmMembers([$member]);
        $user = $this->generateMockCrmUser(['user_id' => $userUser['user_id']]);
        $this->generateMockCrmUsers([$user]);

        // 3. Publish the comment
        $headers = $this->generateMockSocialApiHeaders($admin);
        $response = $this->publishComment($post->id, $comment->id, $headers);

        // 4. Prepare expected data
        $expectedData = $response->json('data');
        $expectedData['nickname'] = $user['nickname'];
        $expectedData['avatar'] = $user['profile_image'];

        // . Assertions
        $this->assert200ResponseWithSimpleResource($response, $this->commentResourceAttributes(), $expectedData, Response::HTTP_CREATED);
    }

    /** @test */
    public function test_200_response_poster_can_unpublish_a_comment(): void
    {
        // 1. Generate the mock data
        $admin = $this->generateMockAdmin();
        $userUser = $this->generateMockUserUser();
        $userId = $userUser['user_id'];
        $poster = $this->createPosterWithAdminAndUserId($admin, $userId);
        $post = $this->createPostWithAdmin($admin, $poster);
        $comment = Comment::factory(['post_id' => $post->id])->create();

        // 2. Mock the dependencies
        $this->mockUserAuthenticationService($admin);
        $this->generateMockUserUserSession($userUser);
        $member = $this->generateMockCrmMember(['member_status' => MemberStatuses::REGULAR_MEMBER_STATUS, 'users' => ['user_id' => $userId]]);
        $this->generateMockCrmMembers([$member]);
        $user = $this->generateMockCrmUser(['user_id' => $userUser['user_id']]);
        $this->generateMockCrmUsers([$user]);

        // 3. Publish the comment
        $headers = $this->generateMockSocialApiHeaders($admin);
        $response = $this->unPublishComment($post->id, $comment->id, $headers);

        // 4. Assertions
        $expectedData = $response->json('data');
        $expectedData['nickname'] = $user['nickname'];
        $expectedData['avatar'] = $user['profile_image'];

        $this->assert200ResponseWithSimpleResource($response, $this->commentResourceAttributes(), $expectedData, Response::HTTP_CREATED);
    }

    /** @test */
    public function test_404_response_publish_a_comment_fails_due_to_post_not_found(): void
    {
        // 1. Generate the mock data
        $admin = $this->generateMockAdmin();
        $userUser = $this->generateMockUserUser();
        $userId = $userUser['user_id'];
        $poster = $this->createPosterWithAdminAndUserId($admin, $userId);
        $post = $this->createPostWithAdmin($admin, $poster);
        $comment = Comment::factory(['post_id' => $post->id])->create();
        $nonExistingPostId = 'non-existing-id';

        // 2. Mock the dependencies
        $this->mockUserAuthenticationService($admin);
        $this->generateMockUserUserSession($userUser);

        // 3. Publish the comment
        $headers = $this->generateMockSocialApiHeaders($admin);
        $response = $this->publishComment($nonExistingPostId, $comment->id, $headers);

        // 4. Assertions
        $this->assert404NotFoundResponse($response, Lang::get('post::errors.post_item_not_found', ['id' => $nonExistingPostId]));
    }

    /** @test */
    public function test_404_response_unpublish_a_comment_fails_due_to_post_not_found(): void
    {
        // 1. Generate the mock data
        $admin = $this->generateMockAdmin();
        $userUser = $this->generateMockUserUser();
        $userId = $userUser['user_id'];
        $poster = $this->createPosterWithAdminAndUserId($admin, $userId);
        $post = $this->createPostWithAdmin($admin, $poster);
        $comment = Comment::factory(['post_id' => $post->id])->create();
        $nonExistingPostId = 'non-existing-id';

        // 2. Mock the dependencies
        $this->mockUserAuthenticationService($admin);
        $this->generateMockUserUserSession($userUser);

        // 3. Publish the comment
        $headers = $this->generateMockSocialApiHeaders($admin);
        $response = $this->unPublishComment($nonExistingPostId, $comment->id, $headers);

        // 4. Assertions
        $this->assert404NotFoundResponse($response, Lang::get('post::errors.post_item_not_found', ['id' => $nonExistingPostId]));
    }

    /** @test */
    public function test_404_response_publish_a_comment_fails_due_to_comment_not_found(): void
    {
        // 1. Generate the mock data
        $admin = $this->generateMockAdmin();
        $userUser = $this->generateMockUserUser();
        $userId = $userUser['user_id'];
        $poster = $this->createPosterWithAdminAndUserId($admin, $userId);
        $post = $this->createPostWithAdmin($admin, $poster);
        $nonExistingPostId = 'non-existing-id';

        // 2. Mock the dependencies
        $this->mockUserAuthenticationService($admin);
        $this->generateMockUserUserSession($userUser);

        // 3. Publish the comment
        $headers = $this->generateMockSocialApiHeaders($admin);
        $response = $this->publishComment($post->id, $nonExistingPostId, $headers);

        // 4. Assertions
        $this->assert404NotFoundResponse($response, Lang::get('comment::errors.comment_item_not_found', ['id' => $nonExistingPostId]));
    }

    /** @test */
    public function test_404_response_unpublish_a_comment_fails_due_to_comment_not_found(): void
    {
        // 1. Generate the mock data
        $admin = $this->generateMockAdmin();
        $userUser = $this->generateMockUserUser();
        $userId = $userUser['user_id'];
        $poster = $this->createPosterWithAdminAndUserId($admin, $userId);
        $post = $this->createPostWithAdmin($admin, $poster);
        $nonExistingPostId = 'non-existing-id';

        // 2. Mock the dependencies
        $this->mockUserAuthenticationService($admin);
        $this->generateMockUserUserSession($userUser);

        // 3. Publish the comment
        $headers = $this->generateMockSocialApiHeaders($admin);
        $response = $this->unPublishComment($post->id, $nonExistingPostId, $headers);

        // 4. Assertions
        $this->assert404NotFoundResponse($response, Lang::get('comment::errors.comment_item_not_found', ['id' => $nonExistingPostId]));
    }

    /** @test */
    public function test_401_response_cannot_published_a_comment_due_to_unauthorized(): void
    {
        // 1. Generate the mock data
        $admin = $this->generateMockAdmin();
        $userUser = $this->generateMockUserUser();
        $userId = $userUser['user_id'];
        $poster = $this->createPosterWithAdminAndUserId($admin, $userId);
        $post = $this->createPostWithAdmin($admin, $poster);
        $comment = Comment::factory(['post_id' => $post->id])->create();

        // 2. Mock the dependencies
        $this->mockUserAuthenticationService($admin);
        $this->generateMockUserUserSession($userUser);

        // 3. Publish the comment
        $headers = $this->generateMockSocialApiHeaders($admin, false);
        $response = $this->publishComment($post->id, $comment->id, $headers);

        $this->assert401UnauthorizedResponse($response);
    }

    /** @test */
    public function test_401_response_cannot_unpublished_a_comment_due_to_unauthorized(): void
    {
        // 1. Generate the mock data
        $admin = $this->generateMockAdmin();
        $userUser = $this->generateMockUserUser();
        $userId = $userUser['user_id'];
        $poster = $this->createPosterWithAdminAndUserId($admin, $userId);
        $post = $this->createPostWithAdmin($admin, $poster);
        $comment = Comment::factory(['post_id' => $post->id])->create();

        // 2. Mock the dependencies
        $this->mockUserAuthenticationService($admin);
        $this->generateMockUserUserSession($userUser);

        // 3. Publish the comment
        $headers = $this->generateMockSocialApiHeaders($admin, false);
        $response = $this->unPublishComment($post->id, $comment->id, $headers);

        $this->assert401UnauthorizedResponse($response);
    }
}
