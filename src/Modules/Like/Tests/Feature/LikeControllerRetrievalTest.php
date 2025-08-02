<?php

namespace Modules\Like\Tests\Feature;

use Modules\Crm\Member\Constants\MemberStatuses;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Lang;
use Illuminate\Testing\TestResponse;
use Modules\Like\app\Models\Like;
use Modules\Like\app\Resources\LikeResource;
use Modules\Post\app\Models\Post;
use Modules\Poster\app\Models\Poster;

/**
 * @group parallel
 */
class LikeControllerRetrievalTest extends LikeControllerTestBase
{
    /**
     * Retrieve the list of likes for a post and return the response.
     *
     * @param  Post $post
     * @param  array $headers
     * @return TestResponse
     */
    private function getLikesList(Post $post, array $headers): TestResponse
    {
        return $this->getJson("{$this->endpoint}/{$post->id}/likes", $headers);
    }

    /**
     * Prepare data for the list of likes.
     *
     * @param Collection $likes
     * @return array
     */
    private function getExpectedLikeCollection(Collection $likes): array
    {
        return $likes
            ->map(function ($like) {
                return (new LikeResource($like))->toArray(request());
            })
            ->sortBy('created_at', 0, true)
            ->values()
            ->all();
    }

    /** @test */
    public function test_200_response_poster_can_retrieve_list_of_likes(): void
    {
        // 1. Generate the mock data
        $admin = $this->generateMockAdmin();
        $userUser = $this->generateMockUserUser();
        $userId = $userUser['user_id'];
        $poster = Poster::factory(['admin_uuid' => $admin->getUuid(), 'user_id' => $userId])->create();
        $post = Post::factory()->create(['admin_uuid' => $admin->getUuid(), 'poster_id' => $poster->id]);
        $likes = Like::factory(['post_id' => $post->id])->count(10)->create();

        // 2. Mock the dependencies
        $this->mockUserAuthenticationService($admin);
        $this->generateMockUserUserSession($userUser);

        // 3. Retrieve the list of likes
        $headers = $this->generateMockSocialApiHeaders($admin);
        $response = $this->getLikesList($post, $headers);

        // 4. Assertions
        $expectedData = $this->getExpectedLikeCollection($likes);
        $this->assert200ResponseWithDataCollection($response, $this->likeHistoryResourceAttributes(), $expectedData);
    }

    /** @test */
    public function test_403_response_paid_member_cannot_retrieve_list_of_likes(): void
    {
        // 1. Generate the mock data
        $admin = $this->generateMockAdmin();
        $userUser = $this->generateMockUserUser();
        $post = Post::factory()->create(['admin_uuid' => $admin->getUuid()]);
        Like::factory(['post_id' => $post->id])->count(10)->create();

        // 2. Mock the dependencies
        $this->mockUserAuthenticationService($admin);
        $this->generateMockUserUserSession($userUser);
        $member = $this->generateMockCrmMember(['member_status' => MemberStatuses::REGULAR_MEMBER_STATUS]);
        $this->generateMockCrmMembers([$member]);

        // 3. Retrieve the list of likes
        $headers = $this->generateMockSocialApiHeaders($admin);
        $response = $this->getLikesList($post, $headers);

        // 4. Assertions
        $this->assert403ForbiddenResponse($response, Lang::get('like::errors.access_denied_retrieval_list_permission'));
    }

    /** @test */
    public function test_403_response_free_member_cannot_retrieve_list_of_likes(): void
    {
        // 1. Generate the mock data
        $admin = $this->generateMockAdmin();
        $userUser = $this->generateMockUserUser();
        $post = Post::factory()->create(['admin_uuid' => $admin->getUuid()]);
        Like::factory(['post_id' => $post->id])->count(10)->create();

        // 2. Mock the dependencies
        $this->mockUserAuthenticationService($admin);
        $this->generateMockUserUserSession($userUser);
        $this->generateMockCrmMembers([$this->generateMockCrmMember(['member_status' => MemberStatuses::FREE_MEMBER_STATUS])]);

        // 3. Retrieve the list of likes
        $headers = $this->generateMockSocialApiHeaders($admin);
        $response = $this->getLikesList($post, $headers);

        // 4. Assertions
        $this->assert403ForbiddenResponse($response, Lang::get('like::errors.access_denied_retrieval_list_permission'));
    }

    /** @test */
    public function test_401_response_non_registered_user_cannot_retrieve_list_of_likes_due_to_unauthorized(): void
    {
        // 1. Generate the mock data
        $admin = $this->generateMockAdmin();
        $userUser = $this->generateMockUserUser();
        $post = Post::factory()->create(['admin_uuid' => $admin->getUuid()]);
        Like::factory(['post_id' => $post->id])->count(10)->create();

        // 2. Mock the dependencies
        $this->mockUserAuthenticationService($admin);
        $this->generateMockUserUserSession($userUser);

        // 3. Retrieve the list of likes
        $headers = $this->generateMockSocialApiHeaders($admin, false);
        $response = $this->getLikesList($post, $headers);

        // 4. Assertions
        $this->assert401UnauthorizedResponse($response);
    }

    /** @test */
    public function test_404_response_retrieve_list_of_likes_fails_due_to_post_not_found(): void
    {
        // 1. Generate the mock data
        $admin = $this->generateMockAdmin();
        $userUser = $this->generateMockUserUser();
        $userId = $userUser['user_id'];
        Poster::factory(['admin_uuid' => $admin->getUuid(), 'user_id' => $userId])->create();
        $nonExistingPostId = 'non-existing-post-id';

        // 2. Mock the dependencies
        $this->mockUserAuthenticationService($admin);
        $this->generateMockUserUserSession($userUser);

        // 3. Request the list of comments
        $headers = $this->generateMockSocialApiHeaders($admin);
        $response = $this->getJson("{$this->endpoint}/{$nonExistingPostId}/likes", $headers);

        // 4. Assertions
        $this->assert404NotFoundResponse($response, Lang::get('post::errors.post_item_not_found', ['id' => $nonExistingPostId]));
    }
}
