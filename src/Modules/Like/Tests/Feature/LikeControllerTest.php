<?php

namespace Modules\Like\Tests\Feature;

use Modules\Crm\Member\Constants\MemberStatuses;
use Illuminate\Support\Facades\Lang;
use Illuminate\Testing\TestResponse;
use Modules\Like\app\Http\Entities\LikePost;
use Modules\Like\app\Models\Like;
use Modules\Like\app\Resources\LikePostResource;
use Modules\Post\app\Models\Post;
use Modules\Poster\app\Models\Poster;
use Symfony\Component\HttpFoundation\Response;

/**
 * @group parallel
 */
class LikeControllerTest extends LikeControllerTestBase
{
    /**
     * Like a post by sending a POST request to the specified endpoint.
     *
     * @param string $postId   The identifier of the post to be liked.
     * @param array  $headers  Headers to be included in the request.
     *
     * @return TestResponse
     */
    private function likeAPost(string $postId, array $headers): TestResponse
    {
        return $this->postJson("{$this->endpoint}/{$postId}/like", [], $headers);
    }

    /**
     * Unlike a post by sending a POST request to the specified endpoint.
     *
     * @param string $postId   The identifier of the post to be unliked.
     * @param array  $headers  Headers to be included in the request.
     *
     * @return TestResponse
     */
    private function unLikeAPost(string $postId, array $headers): TestResponse
    {
        return $this->postJson("{$this->endpoint}/{$postId}/unlike", [], $headers);
    }

    /** @test */
    public function test_403_response_free_member_cannot_like_a_paid_post_due_to_access_denied(): void
    {
        // 1. Generate the mock data
        $admin = $this->generateMockAdmin();
        $userUser = $this->generateMockUserUser();
        $post = Post::factory()->create(['admin_uuid' => $admin->getUuid(), 'type' => Post::PREMIUM_TYPE]);

        // 2. Mock the dependencies
        $this->mockUserAuthenticationService($admin);
        $this->generateMockUserUserSession($userUser);
        $this->generateMockCrmMembers([$this->generateMockCrmMember(['member_status' => MemberStatuses::FREE_MEMBER_STATUS])]);

        // 3. Retrieve the list of likes
        $headers = $this->generateMockSocialApiHeaders($admin);
        $response = $this->likeAPost($post->id, $headers);

        // 4. Assertions
        $this->assert403ForbiddenResponse($response, Lang::get('like::errors.access_denied_like_permission'));
    }

    /** @test */
    public function test_200_response_free_member_can_like_a_free_post(): void
    {
        // 1. Generate the mock data
        $admin = $this->generateMockAdmin();
        $userUser = $this->generateMockUserUser();
        $post = Post::factory()->create(['admin_uuid' => $admin->getUuid(), 'type' => Post::FREE_TYPE]);
        Like::factory(['post_id' => $post->id])->count(10)->create();

        // 2. Mock the dependencies
        $this->mockUserAuthenticationService($admin);
        $this->generateMockUserUserSession($userUser);
        $this->generateMockCrmMembers([$this->generateMockCrmMember(['member_status' => MemberStatuses::FREE_MEMBER_STATUS])]);

        // 3. Retrieve the list of likes
        $headers = $this->generateMockSocialApiHeaders($admin);
        $response = $this->likeAPost($post->id, $headers);
        $expectedData = $response->json('data');

        // 4. Assertions
        $this->assert200ResponseWithSimpleResource($response, $this->likeResourceAttributes(), $expectedData, Response::HTTP_CREATED);
    }

    /** @test */
    public function test_200_response_free_member_can_unlike_a_free_post(): void
    {
        // 1. Generate the mock data
        $admin = $this->generateMockAdmin();
        $userUser = $this->generateMockUserUser();
        $userId = $userUser['user_id'];
        $post = Post::factory()->create(['admin_uuid' => $admin->getUuid(), 'type' => Post::FREE_TYPE]);
        Like::factory(['post_id' => $post->id])->create();
        Like::factory(['post_id' => $post->id, 'user_id' => $userId])->create();

        // 2. Mock the dependencies
        $this->mockUserAuthenticationService($admin);
        $this->generateMockUserUserSession($userUser);
        $this->generateMockCrmMembers([$this->generateMockCrmMember(['member_status' => MemberStatuses::FREE_MEMBER_STATUS])]);

        // 3. Retrieve the list of likes
        $headers = $this->generateMockSocialApiHeaders($admin);
        $response = $this->unLikeAPost($post->id, $headers);
        $expectedData = $response->json('data');

        // 4. Assertions
        $this->assert200ResponseWithSimpleResource($response, $this->likeResourceAttributes(), $expectedData, Response::HTTP_CREATED);
    }

    /** @test */
    public function test_403_response_free_member_cannot_unlike_a_paid_post(): void
    {
        // 1. Generate the mock data
        $admin = $this->generateMockAdmin();
        $userUser = $this->generateMockUserUser();
        $post = Post::factory()->create(['admin_uuid' => $admin->getUuid(), 'type' => Post::PREMIUM_TYPE]);

        // 2. Mock the dependencies
        $this->mockUserAuthenticationService($admin);
        $this->generateMockUserUserSession($userUser);
        $this->generateMockCrmMembers([$this->generateMockCrmMember(['member_status' => MemberStatuses::FREE_MEMBER_STATUS])]);

        // 3. Retrieve the list of likes
        $headers = $this->generateMockSocialApiHeaders($admin);
        $response = $this->unLikeAPost($post->id, $headers);

        // 4. Assertions
        $this->assert403ForbiddenResponse($response, Lang::get('like::errors.access_denied_unlike_permission'));
    }

    /** @test */
    public function test_200_response_poster_can_like_a_post(): void
    {
        // 1. Generate the mock data
        $admin = $this->generateMockAdmin();
        $userUser = $this->generateMockUserUser();
        $userId = $userUser['user_id'];
        $poster = Poster::factory(['admin_uuid' => $admin->getUuid(), 'user_id' => $userId])->create();
        $post = Post::factory()->create(['admin_uuid' => $admin->getUuid(), 'poster_id' => $poster->id]);
        Like::factory(['post_id' => $post->id])->count(10)->create();
        $like = Like::factory(['post_id' => $post->id, 'user_id' => $userId])->create();

        // 2. Mock the dependencies
        $this->mockUserAuthenticationService($admin);
        $this->generateMockUserUserSession($userUser);

        // 3. Retrieve the list of likes
        $headers = $this->generateMockSocialApiHeaders($admin);
        $response = $this->likeAPost($post->id, $headers);

        // 4. Assertions
        $likePost = new LikePost($post->id, $like->user_id, LikePost::ACTION_LIKED, count($post->likes));
        $expectedData = LikePostResource::make($likePost)->resolve();
        $this->assert200ResponseWithSimpleResource($response, $this->likeResourceAttributes(), $expectedData, Response::HTTP_CREATED);
    }

    /** @test */
    public function test_200_response_poster_can_unlike_a_post(): void
    {
        // 1. Generate the mock data
        $admin = $this->generateMockAdmin();
        $userUser = $this->generateMockUserUser();
        $userId = $userUser['user_id'];
        $poster = Poster::factory(['admin_uuid' => $admin->getUuid(), 'user_id' => $userId])->create();
        $post = Post::factory()->create(['admin_uuid' => $admin->getUuid(), 'poster_id' => $poster->id]);
        Like::factory(['post_id' => $post->id])->count(10)->create();
        $like = Like::factory(['post_id' => $post->id, 'user_id' => $userId])->create();

        // 2. Mock the dependencies
        $this->mockUserAuthenticationService($admin);
        $this->generateMockUserUserSession($userUser);

        // 3. Retrieve the list of likes
        $headers = $this->generateMockSocialApiHeaders($admin);
        $response = $this->unLikeAPost($post->id, $headers);

        // 4. Assertions
        $likePost = new LikePost($post->id, $like->user_id, LikePost::ACTION_UNLIKED, count($post->likes));
        $expectedData = LikePostResource::make($likePost)->resolve();
        $this->assert200ResponseWithSimpleResource($response, $this->likeResourceAttributes(), $expectedData, Response::HTTP_CREATED);
    }

    /** @test */
    public function test_200_response_paid_member_can_like_a_post(): void
    {
        // 1. Generate the mock data
        $admin = $this->generateMockAdmin();
        $userUser = $this->generateMockUserUser();
        $userId = $userUser['user_id'];
        $post = Post::factory()->create(['admin_uuid' => $admin->getUuid()]);
        Like::factory(['post_id' => $post->id])->count(10)->create();
        $like = Like::factory(['post_id' => $post->id, 'user_id' => $userId])->create();

        // 2. Mock the dependencies
        $this->mockUserAuthenticationService($admin);
        $this->generateMockUserUserSession($userUser);
        $member = $this->generateMockCrmMember(['member_status' => MemberStatuses::REGULAR_MEMBER_STATUS]);
        $this->generateMockCrmMembers([$member]);

        // 3. Retrieve the list of likes
        $headers = $this->generateMockSocialApiHeaders($admin);
        $response = $this->likeAPost($post->id, $headers);

        // 4. Assertions
        $likePost = new LikePost($post->id, $like->user_id, LikePost::ACTION_LIKED, count($post->likes));
        $expectedData = LikePostResource::make($likePost)->resolve();
        $this->assert200ResponseWithSimpleResource($response, $this->likeResourceAttributes(), $expectedData, Response::HTTP_CREATED);
    }

    /** @test */
    public function test_200_response_paid_member_can_unlike_a_post(): void
    {
        // 1. Generate the mock data
        $admin = $this->generateMockAdmin();
        $userUser = $this->generateMockUserUser();
        $userId = $userUser['user_id'];
        $post = Post::factory()->create(['admin_uuid' => $admin->getUuid()]);
        Like::factory(['post_id' => $post->id])->count(10)->create();
        $like = Like::factory(['post_id' => $post->id, 'user_id' => $userId])->create();

        // 2. Mock the dependencies
        $this->mockUserAuthenticationService($admin);
        $this->generateMockUserUserSession($userUser);
        $member = $this->generateMockCrmMember(['member_status' => MemberStatuses::REGULAR_MEMBER_STATUS]);
        $this->generateMockCrmMembers([$member]);

        // 3. Retrieve the list of likes
        $headers = $this->generateMockSocialApiHeaders($admin);
        $response = $this->unLikeAPost($post->id, $headers);

        // 4. Assertions
        $likePost = new LikePost($post->id, $like->user_id, LikePost::ACTION_UNLIKED, count($post->likes));
        $expectedData = LikePostResource::make($likePost)->resolve();
        $this->assert200ResponseWithSimpleResource($response, $this->likeResourceAttributes(), $expectedData, Response::HTTP_CREATED);
    }

    /** @test */
    public function test_404_response_paid_member_like_a_post_fails_due_to_post_not_found(): void
    {
        // 1. Generate the mock data
        $admin = $this->generateMockAdmin();
        $userUser = $this->generateMockUserUser();
        $postId = 'non-existing-id';

        // 2. Mock the dependencies
        $this->mockUserAuthenticationService($admin);
        $this->generateMockUserUserSession($userUser);
        $member = $this->generateMockCrmMember(['member_status' => MemberStatuses::REGULAR_MEMBER_STATUS]);
        $this->generateMockCrmMembers([$member]);

        // 3. Retrieve the list of likes
        $headers = $this->generateMockSocialApiHeaders($admin);
        $response = $this->likeAPost($postId, $headers);

        // 4. Assertions
        $this->assert404NotFoundResponse($response, Lang::get('post::errors.post_item_not_found', ['id' => $postId]));
    }

    /** @test */
    public function test_404_response_paid_member_unlike_a_post_fails_due_to_post_not_found(): void
    {
        // 1. Generate the mock data
        $admin = $this->generateMockAdmin();
        $userUser = $this->generateMockUserUser();
        $postId = 'non-existing-id';

        // 2. Mock the dependencies
        $this->mockUserAuthenticationService($admin);
        $this->generateMockUserUserSession($userUser);
        $member = $this->generateMockCrmMember(['member_status' => MemberStatuses::REGULAR_MEMBER_STATUS]);
        $this->generateMockCrmMembers([$member]);

        // 3. Retrieve the list of likes
        $headers = $this->generateMockSocialApiHeaders($admin);
        $response = $this->unLikeAPost($postId, $headers);

        // 4. Assertions
        $this->assert404NotFoundResponse($response, Lang::get('post::errors.post_item_not_found', ['id' => $postId]));
    }

    /** @test */
    public function test_401_response_cannot_get_list_of_likes_due_to_unauthorized(): void
    {
        // 1. Generate the mock data
        $admin = $this->generateMockAdmin();
        $userUser = $this->generateMockUserUser();
        $userId = $userUser['user_id'];
        $poster = Poster::factory(['admin_uuid' => $admin->getUuid(), 'user_id' => $userId])->create();
        $post = Post::factory()->create(['admin_uuid' => $admin->getUuid(), 'poster_id' => $poster->id]);
        Like::factory(['post_id' => $post->id])->count(10)->create();

        // 2. Mock the dependencies
        $this->mockUserAuthenticationService($admin);
        $this->generateMockUserUserSession($userUser);

        // 3. Request the list of comments
        $headers = $this->generateMockSocialApiHeaders($admin, false);
        $response = $this->likeAPost($post->id, $headers);

        $this->assert401UnauthorizedResponse($response);
    }
}
