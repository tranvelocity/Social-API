<?php

namespace Modules\Comment\Tests\Feature;

use Illuminate\Support\Facades\Lang;
use Illuminate\Testing\TestResponse;
use Modules\Comment\app\Models\Comment;
use Modules\Post\app\Models\Post;

/**
 * @group parallel
 */
class CommentControllerDeletionTest extends CommentControllerTestBase
{
    /**
     * Delete a comment and assert the response.
     *
     * @param Post $post The post associated with the comment.
     * @param Comment $comment The comment to delete.
     * @param array $headers headers for the request.
     * @return TestResponse The response from the delete request.
     */
    private function deleteCommentAndAssert(Post $post, Comment $comment, array $headers): TestResponse
    {
        return $this->deleteJson("{$this->endpoint}/{$post->id}/comments/{$comment->id}", [], $headers);
    }

    /** @test */
    public function test_204_response_poster_delete_a_comment_successfully(): void
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

        // 3. Delete the comment and assert
        $headers = $this->generateMockSocialApiHeaders($admin);
        $response = $this->deleteCommentAndAssert($post, $comment, $headers);

        // 4. Assertions
        $this->assert204NoContentResponse($response);
    }

    /** @test */
    public function test_204_response_commenter_delete_a_comment__successfully(): void
    {
        // 1. Generate the mock data
        $admin = $this->generateMockAdmin();
        $userUser = $this->generateMockUserUser();
        $userId = $userUser['user_id'];
        $poster = $this->createPosterWithAdminAndUserId($admin, $userId);
        $post = $this->createPostWithAdmin($admin, $poster);
        $comment = Comment::factory(['post_id' => $post->id, 'user_id' => $userId])->create();

        // 2. Mock the dependencies
        $this->mockUserAuthenticationService($admin);
        $this->generateMockUserUserSession($userUser);
        $this->generateMockCrmMember(['member_status' => MemberStatuses::REGULAR_MEMBER_STATUS]);

        // 3. Delete the comment and assert
        $headers = $this->generateMockSocialApiHeaders($admin);
        $response = $this->deleteCommentAndAssert($post, $comment, $headers);

        // 4. Assertions
        $this->assert204NoContentResponse($response);
    }

    /** @test */
    public function test_403_response_delete_a_comment_fails_due_to_access_denied(): void
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

        // 3. Delete the comment and assert
        $headers = $this->generateMockSocialApiHeaders($admin);
        $response = $this->deleteCommentAndAssert($post, $comment, $headers);

        // 4. Assertions
        $this->assert403ForbiddenResponse($response, Lang::get('comment::errors.access_denied_delete_permission'));
    }

    /** @test */
    public function test_403_response_non_commenter_delete_a_comment_fails(): void
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

        // 3. Delete the comment and assert
        $headers = $this->generateMockSocialApiHeaders($admin);
        $response = $this->deleteCommentAndAssert($post, $comment, $headers);

        // 4. Assertions
        $this->assert403ForbiddenResponse($response, Lang::get('comment::errors.access_denied_delete_permission'));
    }

    /** @test */
    public function test_404_response_delete_a_comment_fails_due_to_post_not_found(): void
    {
        // 1. Generate the mock data
        $admin = $this->generateMockAdmin();
        $userUser = $this->generateMockUserUser();
        $userId = $userUser['user_id'];
        $poster = $this->createPosterWithAdminAndUserId($admin, $userId);
        $post = $this->createPostWithAdmin($admin, $poster);
        $nonExistingPostId = 'non-existing-post-id';
        $comment = Comment::factory(['post_id' => $post->id])->create();

        // 2. Mock the dependencies
        $this->mockUserAuthenticationService($admin);
        $this->generateMockUserUserSession($userUser);
        $this->generateMockCrmMember(['member_status' => MemberStatuses::REGULAR_MEMBER_STATUS]);

        // 3. Delete the comment and assert
        $headers = $this->generateMockSocialApiHeaders($admin);
        $response = $this->deleteJson("{$this->endpoint}/{$nonExistingPostId}/comments/{$comment->id}", [], $headers);

        // 4. Assertions
        $this->assert404NotFoundResponse($response, Lang::get('post::errors.post_item_not_found', ['id' => $nonExistingPostId]));
    }

    /** @test */
    public function test_404_response_delete_a_comment_fails_due_to_comment_not_found(): void
    {
        // 1. Generate the mock data
        $admin = $this->generateMockAdmin();
        $userUser = $this->generateMockUserUser();
        $userId = $userUser['user_id'];
        $poster = $this->createPosterWithAdminAndUserId($admin, $userId);
        $post = $this->createPostWithAdmin($admin, $poster);
        $nonExistingId = 'non-existing-post-id';

        // 2. Mock the dependencies
        $this->mockUserAuthenticationService($admin);
        $this->generateMockUserUserSession($userUser);
        $this->generateMockCrmMember(['member_status' => MemberStatuses::REGULAR_MEMBER_STATUS]);

        // 3. Delete the comment and assert
        $headers = $this->generateMockSocialApiHeaders($admin);
        $response = $this->deleteJson("{$this->endpoint}/{$post->id}/comments/{$nonExistingId}", [], $headers);

        // 4. Assertions
        $this->assert404NotFoundResponse($response, Lang::get('comment::errors.comment_item_not_found', ['id' => $nonExistingId]));
    }

    /** @test */
    public function test_401_response_cannot_delete_a_comment_due_to_unauthorized(): void
    {
        // 1. Generate the mock data
        $admin = $this->generateMockAdmin();
        $userUser = $this->generateMockUserUser();
        $userId = $userUser['user_id'];
        $poster = $this->createPosterWithAdminAndUserId($admin, $userId);
        $post = $this->createPostWithAdmin($admin, $poster);
        $nonExistingId = 'non-existing-post-id';

        // 2. Mock the dependencies
        $this->mockUserAuthenticationService($admin);
        $this->generateMockUserUserSession($userUser);
        $this->generateMockCrmMember(['member_status' => MemberStatuses::REGULAR_MEMBER_STATUS]);

        // 3. Delete the comment and assert
        $headers = $this->generateMockSocialApiHeaders($admin, false);
        $response = $this->deleteJson("{$this->endpoint}/{$post->id}/comments/{$nonExistingId}", [], $headers);

        $this->assert401UnauthorizedResponse($response);
    }
}
