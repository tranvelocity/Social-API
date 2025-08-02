<?php

namespace Modules\Post\Tests\Feature;

use Modules\Crm\Member\Constants\MemberStatuses;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Lang;
use Modules\Post\app\Models\Post;
use Modules\Post\app\Resources\PostSocialResource;
use Modules\Poster\app\Models\Poster;
use Modules\Role\app\Entities\Role;
use Modules\Test\Tests\Feature\ApiTestCase;

final class PostSocialControllerTest extends ApiTestCase
{
    use RefreshDatabase;

    private string $endpoint = '/1/posts-social';

    /**
     * Extract media URLs from the response data.
     *
     * @param array $responseData
     * @return array
     */
    private function extractMediaUrls(array $responseData): array
    {
        $mediaUrls = [];

        foreach ($responseData as $post) {
            foreach (['images', 'videos'] as $mediaType) {
                if (isset($post['medias'][$mediaType])) {
                    $mediaUrls = array_merge($mediaUrls, $post['medias'][$mediaType]);
                }
            }
        }

        return $mediaUrls;
    }

    /**
     * Assert that the media URLs are valid.
     *
     * @param array $mediaUrls
     */
    private function assertMediaUrls(array $mediaUrls): void
    {
        foreach ($mediaUrls as $mediaUrl) {
            $this->assertValidUrl($mediaUrl);
        }
    }

    /**
     * Assert that the given URL is valid.
     *
     * @param string $url
     */
    private function assertValidUrl(string $url): void
    {
        $this->assertMatchesRegularExpression('/^https?:\/\/\S+$/', $url, Lang::get('post::errors.invalid_media_url', ['url' => $url]));
    }

    /**
     * Attributes for PostSocialResource.
     *
     * @return array
     */
    private function postSocialResourceAttributes(): array
    {
        return [
            'id',
            'admin_uuid',
            'type',
            'content',
            'is_published',
            'published_start_at',
            'published_end_at',
            'updated_at',
            'created_at',
            'medias' => [
                'images',
                'videos',
            ],
            'total_medias',
            'total_comments',
            'total_likes',
            'is_liked',
            'poster',
        ];
    }

    /**
     * Test retrieving the list of post social with poster role permission.
     *
     * @test
     */
    public function test_200_response_retrieve_list_of_post_social_with_posters(): void
    {
        // 1. Generate the mock data
        $admin = $this->generateMockAdmin();
        $userUser = $this->generateMockUserUser();
        $role = Role::poster()->getRole();
        $userId = $userUser['user_id'];
        $poster = Poster::factory(['user_id' => $userId, 'admin_uuid' => $admin->getUuid()])->create();
        $posts = Post::factory(['admin_uuid' => $admin->getUuid(), 'poster_id' => $poster->id])->count(10)->create();

        // 2. Mock the dependencies
        $this->generateMockUserUserSession($userUser);

        // 3. Execute the endpoint
        $response = $this->getJson($this->endpoint, $this->generateMockSocialApiHeaders($admin));

        // 4. Prepare expected data
        $resourceCollection = $posts->map(function ($postItem) use ($role) {
            return new PostSocialResource($postItem, $role);
        });

        $expectedData = $resourceCollection->map(function ($postResource) {
            return $postResource->toArray(request());
        })->sortBy('created_at', 0, true)->values()->all();

        // 5. Assertions
        $this->assert200ResponseWithDataCollection($response, $this->postSocialResourceAttributes(), $expectedData);

        $mediaUrls = $this->extractMediaUrls($response->json('data'));
        $this->assertMediaUrls($mediaUrls);
    }

    /**
     * Test retrieving the list of post social with paid member role permission.
     *
     * @test
     */
    public function test_200_response_retrieve_list_of_post_social_with_paid_members(): void
    {
        // 1. Generate the mock data
        $admin = $this->generateMockAdmin();
        $userUser = $this->generateMockUserUser();
        $role = Role::paidMember()->getRole();
        $poster = Poster::factory(['admin_uuid' => $admin->getUuid()])->create();
        $posts = Post::factory([
            'admin_uuid' => $admin->getUuid(),
            'is_published' => true,
            'published_start_at' => $this->faker->dateTimeBetween('-3 years', 'now')->format('Y-m-d H:i:s'),
            'published_end_at' => $this->faker->dateTimeBetween('now', '+3 years')->format('Y-m-d H:i:s'),
            'content' => $this->faker->text,
            'poster_id' => $poster->id,
        ])->count(10)->create();

        // 2. Mock the dependencies
        $this->generateMockUserUserSession($userUser);
        $member = $this->generateMockCrmMember(['member_status' => MemberStatuses::REGULAR_MEMBER_STATUS]);
        $this->generateMockCrmMembers([$member]);

        // 3. Execute the endpoint
        $headers = $this->generateMockSocialApiHeaders($admin);
        $response = $this->getJson($this->endpoint, $headers);

        // 4. Prepare expected data
        $resourceCollection = $posts->map(function ($postItem) use ($role) {
            return new PostSocialResource($postItem, $role);
        });

        $expectedData = $resourceCollection->map(function ($postResource) {
            return $postResource->toArray(request());
        })->sortBy('created_at', 0, true)->values()->all();

        // 5. Assertions
        $this->assert200ResponseWithDataCollection($response, $this->postSocialResourceAttributes(), $expectedData);

        $mediaUrls = $this->extractMediaUrls($response->json('data'));
        $this->assertMediaUrls($mediaUrls);
    }

    /**
     * Test retrieving the list of post social with free member role permission.
     *
     * @test
     */
    public function test_200_response_retrieve_list_of_post_social_with_free_members(): void
    {
        // 1. Generate the mock data
        $admin = $this->generateMockAdmin();
        $poster = Poster::factory(['admin_uuid' => $admin->getUuid()])->create();
        $posts = Post::factory([
            'admin_uuid' => $admin->getUuid(),
            'is_published' => true,
            'type' => Post::FREE_TYPE,
            'published_start_at' => $this->faker->dateTimeBetween('-3 years', 'now')->format('Y-m-d H:i:s'),
            'published_end_at' => $this->faker->dateTimeBetween('now', '+3 years')->format('Y-m-d H:i:s'),
            'content' => null,
            'poster_id' => $poster->id,
        ])->count(10)->create();

        // 2. Mock the dependencies
        $userUser = $this->generateMockUserUser();
        $this->generateMockUserUserSession($userUser);
        $this->generateMockCrmMembers([$this->generateMockCrmMember(['member_status' => MemberStatuses::FREE_MEMBER_STATUS])]);

        // 3. Execute the endpoint
        $response = $this->getJson($this->endpoint, $this->generateMockSocialApiHeaders($admin));

        // 4. Prepare expected data
        $userId = $userUser['user_id'];
        $role = Role::freeMember()->getRole();
        $resourceCollection = $posts->map(function ($postItem) use ($role) {
            return $postItem->is_published
                ? new PostSocialResource($postItem, $role)
                : null;
        })->filter();

        $expectedData = $resourceCollection->map(function ($postResource) {
            return $postResource->toArray(request());
        })->sortBy('created_at', 0, true)->values()->all();

        // 5. Assertions
        $this->assert200ResponseWithDataCollection($response, $this->postSocialResourceAttributes(), $expectedData);

        $mediaUrls = $this->extractMediaUrls($response->json('data'));
        $this->assertMediaUrls($mediaUrls);
    }

    /**
     * Test retrieving the list of post social with free member role permission.
     *
     * @test
     */
    public function test_200_response_retrieve_list_of_post_social_with_non_registered_users(): void
    {
        // 1. Generate the mock data
        $admin = $this->generateMockAdmin();
        $posts = Post::factory([
            'admin_uuid' => $admin->getUuid(),
            'is_published' => true,
            'type' => Post::FREE_TYPE,
            'published_start_at' => $this->faker->dateTimeBetween('-3 years', 'now')->format('Y-m-d H:i:s'),
            'published_end_at' => $this->faker->dateTimeBetween('now', '+3 years')->format('Y-m-d H:i:s'),
        ])->count(10)->create();

        // 2. Execute the endpoint
        $response = $this->getJson($this->endpoint, $this->generateMockSocialApiHeaders($admin, false));

        // 3. Prepare expected data
        $role = Role::nonRegisteredUser()->getRole();
        $resourceCollection = $posts->map(function ($postItem) use ($role) {
            return $postItem->is_published
                ? new PostSocialResource($postItem, $role)
                : null;
        })->filter();

        $expectedData = $resourceCollection->map(function ($postResource) {
            return $postResource->toArray(request());
        })->sortBy('created_at', 0, true)->values()->all();

        // 4. Assertions
        $this->assert200ResponseWithDataCollection($response, $this->postSocialResourceAttributes(), $expectedData);

        $mediaUrls = $this->extractMediaUrls($response->json('data'));
        $this->assertMediaUrls($mediaUrls);
    }
}
