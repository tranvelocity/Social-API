<?php

namespace Modules\Post\Tests\Feature;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Lang;
use Modules\Admin\Entities\Admin;
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
final class PostControllerCreationTest extends PostControllerTestBase
{
    /**
     * Generate an array of valid parameters for testing post creation.
     */
    protected function generateValidParameters(Admin $admin = null): array
    {
        $admin = $admin ?? $this->generateMockAdmin();
        Poster::factory(['admin_uuid' => $admin->getUuid()])->create();
        $image = Media::factory()->imageItem()->create();
        $video = Media::factory()->videoItem()->create();

        return [
            'admin_uuid' => $admin->getUuid(),
            'is_published' => $this->faker->boolean,
            'type' => $this->faker->randomElement([Post::FREE_TYPE, Post::PREMIUM_TYPE]),
            'published_start_at' => $this->faker->dateTimeBetween('-3 years', 'now')->format('Y-m-d H:i:s'),
            'published_end_at' => $this->faker->dateTimeBetween('+1 years', '+3 years')->format('Y-m-d H:i:s'),
            'content' => $this->faker->text,
            'media_ids' => [
                ['id' => $image->id],
                ['id' => $video->id],
            ],
        ];
    }

    /** @test */
    public function test_201_response_create_a_new_post_successfully_by_poster(): void
    {
        // 1. Generate the mock data
        $admin = $this->generateMockAdmin();
        $userUser = $this->generateMockUserUser();
        $userId = $userUser['user_id'];
        Poster::factory(['user_id' => $userId, 'admin_uuid' => $admin->getUuid()])->create();

        // 2. Mock the dependencies
        $this->mockUserAuthenticationService($admin);
        $this->generateMockUserUserSession($userUser);

        // 3. Execute the endpoint
        $validRequestData = $this->generateValidParameters($admin);
        unset($validRequestData['media_ids']);
        $response = $this->postJson($this->endpoint, $validRequestData, $this->generateMockSocialApiHeaders($admin));

        // 4. Retrieve the created post from the database
        $createdPost = Post::find($response->json('data.id'));
        $poster = Poster::first();

        // 5. Get the expected data
        $expectedData = (new PostResource($createdPost))->resolve();
        $expectedData['poster'] = [
            'id' => $poster->id,
            'user_id' => $poster->getUserId(),
            'nickname' => $poster->getNickname(),
            'avatar' => $poster->getAvatar(),
        ];

        // 6. Assertions
        $this->assert201SuccessCreationResponse($response, $this->simplePostResourceAttributes(), $expectedData);
    }

    /** @test */
    public function test_403_response_paid_member_create_post_fails(): void
    {
        // 1. Generate the mock data
        $admin = $this->generateMockAdmin();
        $userUser = $this->generateMockUserUser();

        // 2. Mock the dependencies
        $this->mockUserAuthenticationService($admin);
        $this->generateMockUserUserSession($userUser);
        $this->generateMockCrmMember(['member_status' => MemberStatuses::REGULAR_MEMBER_STATUS]);

        // 3. Execute the endpoint
        $validRequestData = $this->generateValidParameters($admin);
        $response = $this->postJson($this->endpoint, $validRequestData, $this->generateMockSocialApiHeaders($admin));

        // 6. Assertions
        $this->assert403ForbiddenResponse($response, Lang::get('post::errors.access_denied_creation_permission'));
    }

    /** @test */
    public function test_403_response_free_member_create_post_fails(): void
    {
        // 1. Generate the mock data
        $admin = $this->generateMockAdmin();
        $userUser = $this->generateMockUserUser();

        // 2. Mock the dependencies
        $this->mockUserAuthenticationService($admin);
        $this->generateMockUserUserSession($userUser);
        $this->generateMockCrmMember(['member_status' => MemberStatuses::FREE_MEMBER_STATUS]);

        // 3. Execute the endpoint
        $validRequestData = $this->generateValidParameters($admin);
        $response = $this->postJson($this->endpoint, $validRequestData, $this->generateMockSocialApiHeaders($admin));

        // 6. Assertions
        $this->assert403ForbiddenResponse($response, Lang::get('post::errors.access_denied_creation_permission'));
    }

    /** @test */
    public function test_401_response_non_registered_user_cannot_create_post_due_to_unauthorized(): void
    {
        // 1. Generate the mock data
        $admin = $this->generateMockAdmin();
        $userUser = $this->generateMockUserUser();

        // 2. Mock the dependencies
        $this->mockUserAuthenticationService($admin);
        $this->generateMockUserUserSession($userUser);
        $this->generateMockCrmMember(['member_status' => MemberStatuses::FREE_MEMBER_STATUS]);

        // 3. Execute the endpoint
        $validRequestData = $this->generateValidParameters($admin);
        $response = $this->postJson($this->endpoint, $validRequestData, $this->generateMockSocialApiHeaders($admin, false));

        // 6. Assertions
        $this->assert401UnauthorizedResponse($response);
    }

    /** @test */
    public function test_404_response_create_post_fails_media_not_found(): void
    {
        // 1. Generate the mock data
        $admin = $this->generateMockAdmin();
        $userUser = $this->generateMockUserUser();
        $userId = $userUser['user_id'];
        $poster = Poster::factory(['user_id' => $userId, 'admin_uuid' => $admin->getUuid()])->create();

        $media = Media::factory()->availabilityItem()->create();
        $nonExistingMediaId = 'non-existing-id';

        $requestData = [
            'admin_uuid' => $admin->getUuid(),
            'is_published' => $this->faker->boolean,
            'poster_id' => $poster->id,
            'type' => $this->faker->randomElement([Post::FREE_TYPE, Post::PREMIUM_TYPE]),
            'published_start_at' => $this->faker->dateTimeBetween('-3 years', 'now')->format('Y-m-d H:i:s'),
            'published_end_at' => $this->faker->dateTimeBetween('+1 years', '+3 years')->format('Y-m-d H:i:s'),
            'content' => $this->faker->text,
            'media_ids' => [
                ['id' => $media->id],
                ['id' => $nonExistingMediaId],
            ],
        ];

        // 2. Mock the dependencies
        $this->mockUserAuthenticationService($admin);
        $this->generateMockUserUserSession($userUser);

        // 3. Execute the endpoint
        $response = $this->postJson($this->endpoint, $requestData, $this->generateMockSocialApiHeaders($admin));

        // 4. Assertions
        $expectedErrorMessage = Lang::get('media::errors.media_item_not_found', ['id' => $nonExistingMediaId]);
        $this->assert404NotFoundResponse($response, $expectedErrorMessage);
    }

    /** @test */
    public function test_400_response_create_post_fails_due_to_invalid_publish_period(): void
    {
        // 1. Generate the mock data
        $admin = $this->generateMockAdmin();
        $userUser = $this->generateMockUserUser();
        $userId = $userUser['user_id'];
        Poster::factory(['user_id' => $userId, 'admin_uuid' => $admin->getUuid()])->create();

        // 2. Mock the dependencies
        $this->mockUserAuthenticationService($admin);
        $this->generateMockUserUserSession($userUser);

        // 3. Generate valid request parameters
        $validRequestData = $this->generateValidParameters();

        // 4. Introduce an invalid parameter, for example, setting published_end_at before published_start_at
        $invalidRequestData = $validRequestData;
        $invalidRequestData['published_end_at'] = $this->faker->dateTimeBetween('-3 years', $invalidRequestData['published_start_at'])->format('Y-m-d H:i:s');

        // 5. Attempt to create a post with invalid parameters
        $response = $this->postJson($this->endpoint, $invalidRequestData, $this->generateMockSocialApiHeaders($admin));

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
    public function test_400_response_create_post_fails_due_to_missing_required_type(): void
    {
        // 1. Generate the mock data
        $admin = $this->generateMockAdmin();
        $userUser = $this->generateMockUserUser();
        $userId = $userUser['user_id'];
        Poster::factory(['user_id' => $userId, 'admin_uuid' => $admin->getUuid()])->create();

        // 2. Mock the dependencies
        $this->mockUserAuthenticationService($admin);
        $this->generateMockUserUserSession($userUser);

        // 3. Generate valid request parameters
        $validRequestData = $this->generateValidParameters();

        // 4. Remove the required 'type' parameter
        unset($validRequestData['type']);

        // 5. Attempt to create a post without the 'type' parameter
        $response = $this->postJson($this->endpoint, $validRequestData, $this->generateMockSocialApiHeaders($admin));

        // 6. Expected error response
        $expectedError = [
            'message' => Config::get('tranauth.exceptions.400.400001'),
            'validation' => [
                [
                    'attribute' => 'type',
                    'errors' => [
                        [
                            'key' => 'Required',
                            'message' => 'required|The type field is required.',
                        ],
                    ],
                ],
            ],
        ];

        // 7. Assertions
        $this->assert400BadRequestValidationFailureResponse($response, $expectedError);
    }

    /** @test */
    public function test_400_response_create_post_fails_due_to_invalid_type(): void
    {
        // 1. Generate the mock data
        $admin = $this->generateMockAdmin();
        $userUser = $this->generateMockUserUser();
        $userId = $userUser['user_id'];
        Poster::factory(['user_id' => $userId, 'admin_uuid' => $admin->getUuid()])->create();

        // 2. Mock the dependencies
        $this->mockUserAuthenticationService($admin);
        $this->generateMockUserUserSession($userUser);

        // 3. Generate valid request parameters
        $validRequestData = $this->generateValidParameters();

        // 4. Set an invalid 'type' parameter
        $validRequestData['type'] = 'invalid_type';

        // 5. Attempt to create a post with invalid 'type' parameter
        $response = $this->postJson($this->endpoint, $validRequestData, $this->generateMockSocialApiHeaders($admin));

        // 6. Expected error response
        $expectedError = [
            'message' => Config::get('tranauth.exceptions.400.400001'),
            'validation' => [
                [
                    'attribute' => 'type',
                    'errors' => [
                        [
                            'key' => 'In',
                            'message' => 'in|The selected type is invalid.',
                        ],
                    ],
                ],
            ],
        ];

        // 7. Assertions
        $this->assert400BadRequestValidationFailureResponse($response, $expectedError);
    }

    /** @test */
    public function test_400_response_create_post_fails_due_to_invalid_date_format(): void
    {
        // 1. Generate the mock data
        $admin = $this->generateMockAdmin();
        $userUser = $this->generateMockUserUser();
        $userId = $userUser['user_id'];
        Poster::factory(['user_id' => $userId, 'admin_uuid' => $admin->getUuid()])->create();

        // 2. Mock the dependencies
        $this->mockUserAuthenticationService($admin);
        $this->generateMockUserUserSession($userUser);

        // 3. Generate valid request parameters
        $validRequestData = $this->generateValidParameters();

        // 4. Set an invalid date format for 'published_start_at'
        $validRequestData['published_start_at'] = 'invalid_date_format';

        // 5. Attempt to create a post with invalid date format
        $response = $this->postJson($this->endpoint, $validRequestData, $this->generateMockSocialApiHeaders($admin));

        // 6. Expected error response
        $expectedError = [
            'message' => Config::get('tranauth.exceptions.400.400001'),
            'validation' => [
                [
                    'attribute' => 'published_start_at',
                    'errors' => [
                        [
                            'key' => 'DateFormat',
                            'message' => 'date_format|The published start at does not match the format Y-m-d H:i:s.',
                        ],
                    ],
                ],
            ],
        ];

        // 7. Assertions
        $this->assert400BadRequestValidationFailureResponse($response, $expectedError);
    }

    /** @test */
    public function test_400_response_create_post_fails_due_to_missing_media_id(): void
    {
        // 1. Generate the mock data
        $admin = $this->generateMockAdmin();
        $userUser = $this->generateMockUserUser();
        $userId = $userUser['user_id'];
        Poster::factory(['user_id' => $userId, 'admin_uuid' => $admin->getUuid()])->create();

        // 2. Mock the dependencies
        $this->mockUserAuthenticationService($admin);
        $this->generateMockUserUserSession($userUser);

        // 3. Generate valid request parameters
        $validRequestData = $this->generateValidParameters();

        // 4. Add media_ids array without id
        $validRequestData['media_ids'] = [['invalid_key' => '123']];

        // 5. Attempt to create a post with missing media id
        $response = $this->postJson($this->endpoint, $validRequestData, $this->generateMockSocialApiHeaders($admin));

        // 6. Expected error response
        $expectedError = [
            'message' => Config::get('tranauth.exceptions.400.400001'),
            'validation' => [
                [
                    'attribute' => 'media_ids.0.id',
                    'errors' => [
                        [
                            'key' => 'RequiredWith',
                            'message' => 'required_with|The media_ids.0.id field is required when media ids are present.',
                        ],
                    ],
                ],
            ],
        ];

        // 7. Assertions
        $this->assert400BadRequestValidationFailureResponse($response, $expectedError);
    }

    /** @test */
    public function test_400_response_create_post_fails_due_to_non_boolean_is_published(): void
    {
        // 1. Generate the mock data
        $admin = $this->generateMockAdmin();
        $userUser = $this->generateMockUserUser();
        $userId = $userUser['user_id'];
        Poster::factory(['user_id' => $userId, 'admin_uuid' => $admin->getUuid()])->create();

        // 2. Mock the dependencies
        $this->mockUserAuthenticationService($admin);
        $this->generateMockUserUserSession($userUser);

        // 3. Generate valid request parameters
        $validRequestData = $this->generateValidParameters();

        // 4. Set non-boolean value for 'is_published'
        $validRequestData['is_published'] = 'invalid_boolean';

        // 5. Attempt to create a post with non-boolean 'is_published'
        $response = $this->postJson($this->endpoint, $validRequestData, $this->generateMockSocialApiHeaders($admin));

        // 6. Expected error response
        $expectedError = [
            'message' => Config::get('tranauth.exceptions.400.400001'),
            'validation' => [
                [
                    'attribute' => 'is_published',
                    'errors' => [
                        [
                            'key' => 'Boolean',
                            'message' => 'boolean|The is published field must be true or false.',
                        ],
                    ],
                ],
            ],
        ];

        // 7. Assertions
        $this->assert400BadRequestValidationFailureResponse($response, $expectedError);
    }

    /** @test */
    public function test_409_response_create_post_fails_media_association_conflict(): void
    {
        // 1. Generate the mock data
        $admin = $this->generateMockAdmin();
        $userUser = $this->generateMockUserUser();
        $userId = $userUser['user_id'];
        Poster::factory(['user_id' => $userId, 'admin_uuid' => $admin->getUuid()])->create();

        // 2. Mock the dependencies
        $this->mockUserAuthenticationService($admin);
        $this->generateMockUserUserSession($userUser);

        // 3. Create a post and associated media
        $post = Post::factory(['admin_uuid' => $admin->getUuid()])->create();
        $media = Media::factory(['post_id' => $post->id])->create();

        // 4. Mock the service or class that may throw a ConflictException
        $this->mock(PostService::class, function ($mock) use ($media) {
            $mock->shouldReceive('createPost')
                ->andThrow(new ConflictException(Lang::get('media::errors.media_association_conflict', ['media_id' => $media->id])));
        });

        // 5. Attempt to associate the same media with the second post
        $requestParams = $this->generateValidParameters();
        $response = $this->postJson($this->endpoint, $requestParams, $this->generateMockSocialApiHeaders($admin));

        // 6. Assertions
        $expectedErrorMessage = Lang::get('media::errors.media_association_conflict', ['media_id' => $media->id]);
        $this->assert409ResourceConflictResponse($response, $expectedErrorMessage);
    }

    /** @test */
    public function test_500_response_create_post_fails_with_fatal_error(): void
    {
        // 1. Generate the mock data
        $admin = $this->generateMockAdmin();
        $userUser = $this->generateMockUserUser();
        $userId = $userUser['user_id'];
        Poster::factory(['user_id' => $userId, 'admin_uuid' => $admin->getUuid()])->create();

        // 2. Mock the dependencies
        $this->mockUserAuthenticationService($admin);
        $this->generateMockUserUserSession($userUser);

        // 3. Mock the service or class that may throw a FatalErrorException
        $this->mock(PostService::class, function ($mock) {
            $mock->shouldReceive('createPost')
                ->andThrow(new FatalErrorException());
        });

        // 4. Attempt to create a post
        $requestParams = $this->generateValidParameters();
        $response = $this->postJson($this->endpoint, $requestParams, $this->generateMockSocialApiHeaders($admin));

        // 5. Assertions
        $this->assert500FailureResponse($response, Config::get('tranauth.exceptions.500.500001'));
    }
}
