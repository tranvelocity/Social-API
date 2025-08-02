<?php

namespace Modules\Post\Tests\Feature;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Lang;
use Modules\Core\app\Exceptions\ConflictException;
use Modules\Core\app\Exceptions\FatalErrorException;
use Modules\Crm\Member\Constants\MemberStatuses;
use Modules\Media\app\Models\Media;
use Modules\Post\app\Models\Post;
use Modules\Post\app\Resources\PostResource;
use Modules\Post\app\Services\PostService;
use Modules\Poster\app\Models\Poster;

/**
 * @group parallel
 */
class PostControllerUpdateTest extends PostControllerTestBase
{
    /** @test */
    public function test_200_response_poster_can_update_a_specific_post(): void
    {
        // 1. Generate the mock data
        $admin = $this->generateMockAdmin();
        $userUser = $this->generateMockUserUser();
        $userId = $userUser['user_id'];
        $poster = Poster::factory(['user_id' => $userId, 'admin_uuid' => $admin->getUuid()])->create();
        $post = Post::factory(['admin_uuid' => $admin->getUuid(), 'poster_id' => $poster->id])->create();
        $image = Media::factory()->availabilityItem()->create();

        $validRequestParams = [
            'admin_uuid' => $admin->getUuid(),
            'is_published' => $this->faker->boolean,
            'poster_id' => $poster->id,
            'type' => $this->faker->randomElement([Post::FREE_TYPE, Post::PREMIUM_TYPE]),
            'published_start_at' => $this->faker->dateTimeBetween('-3 years', 'now')->format('Y-m-d H:i:s'),
            'published_end_at' => $this->faker->dateTimeBetween('+1 years', '+3 years')->format('Y-m-d H:i:s'),
            'content' => $this->faker->text,
            'media_ids' => [
                ['id' => $image->id],
            ],
        ];

        // 2. Mock the dependencies
        $this->mockUserAuthenticationService($admin);
        $this->generateMockUserUserSession($userUser);

        // 3. Execute the endpoint
        $response = $this->putJson($this->endpoint . "/{$post->id}", $validRequestParams, $this->generateMockSocialApiHeaders($admin));

        // 4. Get the expected data
        $updatedPost = Post::find($response->json('data.id'));
        $this->app->make(PostService::class)->loadMediaForPost($updatedPost);
        $expectedData = PostResource::make($updatedPost)->resolve();
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
    public function test_403_response_premium_member_update_post_fails_due_to_access_denied(): void
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

        $post = Post::factory()->create(['admin_uuid' => $admin->getUuid()]);
        $validRequestParams = [
            'admin_uuid' => $admin->getUuid(),
            'is_published' => $this->faker->boolean,
            'type' => $this->faker->randomElement([Post::FREE_TYPE, Post::PREMIUM_TYPE]),
            'published_start_at' => $this->faker->dateTimeBetween('-3 years', 'now')->format('Y-m-d H:i:s'),
            'published_end_at' => $this->faker->dateTimeBetween('+1 years', '+3 years')->format('Y-m-d H:i:s'),
            'content' => $this->faker->text,
        ];

        $response = $this->putJson($this->endpoint . "/{$post->id}", $validRequestParams, $this->generateMockSocialApiHeaders($admin));
        $this->assert403ForbiddenResponse($response, Lang::get('post::errors.access_denied_edit_permission'));
    }

    /** @test */
    public function test_403_response_free_member_update_post_fails_due_to_access_denied(): void
    {
        // Mock Admin and User User
        $admin = $this->generateMockAdmin();
        $this->mockUserAuthenticationService($admin);
        $userUser = $this->generateMockUserUser();
        $userId = $userUser['user_id'];
        $this->generateMockUserUserSession($userUser);

        // Mock CRM Member
        $member = $this->generateMockCrmMember(['member_status' => MemberStatuses::FREE_MEMBER_STATUS, 'users' => ['user_id' => $userId]]);
        $this->generateMockCrmMembers([$member]);

        $post = Post::factory()->create(['admin_uuid' => $admin->getUuid()]);
        $validRequestParams = [
            'admin_uuid' => $admin->getUuid(),
            'is_published' => $this->faker->boolean,
            'type' => $this->faker->randomElement([Post::FREE_TYPE, Post::PREMIUM_TYPE]),
            'published_start_at' => $this->faker->dateTimeBetween('-3 years', 'now')->format('Y-m-d H:i:s'),
            'published_end_at' => $this->faker->dateTimeBetween('+1 years', '+3 years')->format('Y-m-d H:i:s'),
            'content' => $this->faker->text,
        ];

        $response = $this->putJson($this->endpoint . "/{$post->id}", $validRequestParams, $this->generateMockSocialApiHeaders($admin));
        $this->assert403ForbiddenResponse($response, Lang::get('post::errors.access_denied_edit_permission'));
    }

    /** @test */
    public function test_401_response_non_registered_user_update_post_fails_due_to_unauthorized(): void
    {
        // Mock Admin and User User
        $admin = $this->generateMockAdmin();
        $this->mockUserAuthenticationService($admin);
        $userUser = $this->generateMockUserUser();
        $this->generateMockUserUserSession($userUser);

        $post = Post::factory()->create(['admin_uuid' => $admin->getUuid()]);
        $validRequestParams = [
            'admin_uuid' => $admin->getUuid(),
            'is_published' => $this->faker->boolean,
            'type' => $this->faker->randomElement([Post::FREE_TYPE, Post::PREMIUM_TYPE]),
            'published_start_at' => $this->faker->dateTimeBetween('-3 years', 'now')->format('Y-m-d H:i:s'),
            'published_end_at' => $this->faker->dateTimeBetween('+1 years', '+3 years')->format('Y-m-d H:i:s'),
            'content' => $this->faker->text,
        ];

        $response = $this->putJson($this->endpoint . "/{$post->id}", $validRequestParams, $this->generateMockSocialApiHeaders($admin, false));
        $this->assert401UnauthorizedResponse($response);
    }

    /**
     * Test that updating a post with a non-existing media resource results in a 404 error.
     * @test
     */
    public function test_404_response_update_post_fails_due_to_media_resource_not_found(): void
    {
        // Arrange: Generate the mock data for admin, poster, and post.
        $admin = $this->generateMockAdmin();
        $userUser = $this->generateMockUserUser();
        $userId = $userUser['user_id'];
        $poster = Poster::factory(['user_id' => $userId, 'admin_uuid' => $admin->getUuid()])->create();
        $post = Post::factory(['admin_uuid' => $admin->getUuid(), 'poster_id' => $poster->id])->create();

        // Set up a non-existing media ID to simulate a media resource not found scenario.
        $nonExistingMediaId = 'non-existing-media-id';

        // Create valid request parameters with the non-existing media ID.
        $validRequestParams = [
            'admin_uuid' => $admin->getUuid(),
            'is_published' => $this->faker->boolean,
            'poster_id' => $poster->id,
            'type' => $this->faker->randomElement([Post::FREE_TYPE, Post::PREMIUM_TYPE]),
            'published_start_at' => $this->faker->dateTimeBetween('-3 years', 'now')->format('Y-m-d H:i:s'),
            'published_end_at' => $this->faker->dateTimeBetween('+1 years', '+3 years')->format('Y-m-d H:i:s'),
            'content' => $this->faker->text,
            'media_ids' => [
                ['id' => $nonExistingMediaId],
            ],
        ];

        // Mock the dependencies, including the account service and user merchant session.
        $this->mockUserAuthenticationService($admin);
        $this->generateMockUserUserSession($userUser);

        // Act: Execute the endpoint or method that uses the mocked service or class.
        $response = $this->putJson($this->endpoint . '/' . $post->id, $validRequestParams, $this->generateMockSocialApiHeaders($admin));

        // Assert: Ensure that the response is a 404 Not Found, indicating the media resource is not found.
        $this->assert404NotFoundResponse($response, Lang::get('media::errors.media_item_not_found', ['id' => $nonExistingMediaId]));
    }

    /** @test */
    public function test_400_response_update_a_specific_post_fails_due_to_request_parameters_invalid(): void
    {
        // 1. Generate the mock data
        $admin = $this->generateMockAdmin();
        $userUser = $this->generateMockUserUser();
        $userId = $userUser['user_id'];
        $poster = Poster::factory(['user_id' => $userId, 'admin_uuid' => $admin->getUuid()])->create();
        $post = Post::factory(['admin_uuid' => $admin->getUuid(), 'poster_id' => $poster->id])->create();

        // 2. Mock the dependencies
        $this->mockUserAuthenticationService($admin);
        $this->generateMockUserUserSession($userUser);

        // 3. Generate valid request parameters
        $validRequestData = $this->generateValidParameters();

        // 4. Introduce an invalid parameter, for example, setting published_end_at before published_start_at
        $invalidRequestData = $validRequestData;
        $invalidRequestData['published_end_at'] = $this->faker->dateTimeBetween('-3 years', $invalidRequestData['published_start_at'])->format('Y-m-d H:i:s');

        // 5. Attempt to create a post with invalid parameters
        $response = $this->putJson($this->endpoint . '/' . $post->id, $invalidRequestData, $this->generateMockSocialApiHeaders($admin));

        // 6. Expected error response
        $expectedError = [
            'message' => Config::get('tranauth.exceptions.400.400001'),
            'validation' => [
                [
                    'attribute' => 'published_end_at',
                    'errors' => [
                        [
                            'key' => 'After',
                            'message' => 'after|The published end at must be after published start at.',
                        ],
                    ],
                ],
            ],
        ];

        // 7. Assertions
        $this->assert400BadRequestValidationFailureResponse($response, $expectedError);
    }

    /** @test */
    public function test_409_response_update_a_specific_post_fails_due_to_media_association_conflict(): void
    {
        // 1. Generate the mock data for admin and poster.
        $admin = $this->generateMockAdmin();
        $userUser = $this->generateMockUserUser();
        $userId = $userUser['user_id'];
        $poster = Poster::factory(['user_id' => $userId, 'admin_uuid' => $admin->getUuid()])->create();

        // 2. Mock the dependencies, including the account service and user merchant session.
        $this->mockUserAuthenticationService($admin);
        $this->generateMockUserUserSession($userUser);

        // 3. Create a post and associated media.
        $post = Post::factory(['admin_uuid' => $admin->getUuid(), 'poster_id' => $poster->id])->create();
        $media = Media::factory()->create();

        // 4. Mock the service or class that may throw a ConflictException.
        $this->mock(PostService::class, function ($mock) use ($media) {
            $mock->shouldReceive('updatePost')
                ->andThrow(new ConflictException(Lang::get('media::errors.media_association_conflict', ['media_id' => $media->id])));
        });

        // 5. Attempt to associate the same media with the second post.
        $requestParams = $this->generateValidParameters();
        $requestParams['media_ids'][] = ['id' => $media->id];

        // 6. Execute the endpoint or method that uses the mocked service or class.
        $response = $this->putJson("{$this->endpoint}/{$post->id}", $requestParams, $this->generateMockSocialApiHeaders($admin));

        // 7. Assertions: Ensure that the response contains the expected conflict error message.
        $expectedErrorMessage = Lang::get('media::errors.media_association_conflict', ['media_id' => $media->id]);
        $this->assert409ResourceConflictResponse($response, $expectedErrorMessage);
    }

    /** @test */
    public function test_500_response_update_a_specific_post_fails_fatal_error_response(): void
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

        // 3. Mock the service or class that may throw a FatalErrorException.
        $this->mock(PostService::class, function ($mock) {
            $mock->shouldReceive('updatePost')
                ->andThrow(new FatalErrorException());
        });

        // 4. Generate valid request parameters for the update.
        $requestParams = $this->generateValidParameters();

        // 5. Execute the endpoint or method that uses the mocked service or class.
        $response = $this->putJson("{$this->endpoint}/{$post->id}", $requestParams, $this->generateMockSocialApiHeaders($admin));

        // 6. Assertions: Ensure that the response contains the expected 500 failure response.
        $this->assert500FailureResponse($response, Config::get('tranauth.exceptions.500.500001'));
    }
}
