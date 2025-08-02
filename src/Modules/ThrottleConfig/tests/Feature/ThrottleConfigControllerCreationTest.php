<?php

namespace Modules\ThrottleConfig\tests\Feature;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Lang;
use Modules\Poster\app\Models\Poster;
use Modules\ThrottleConfig\app\Models\ThrottleConfig;
use Modules\ThrottleConfig\app\Resources\ThrottleConfigResource;

/**
 * @group parallel
 */
final class ThrottleConfigControllerCreationTest extends ThrottleConfigControllerTestBase
{
    /**
     * Generate an array of valid parameters for testing post creation.
     */
    private function generateValidParameters(): array
    {
        return [
            'time_frame_minutes' => $this->faker->numberBetween(1, 24),
            'max_comments' => $this->faker->numberBetween(1, 100),
        ];
    }

    /** @test */
    public function test_201_response_creating_a_new_throttle_config_successfully(): void
    {
        // 1. Generate the mock data
        $admin = $this->generateMockAdmin();
        $userUser = $this->generateMockUserUser();
        $userId = $userUser['user_id'];

        // 2. Mock the dependencies
        $this->mockUserAuthenticationService($admin);
        $this->generateMockUserUserSession($userUser);

        // 3. Execute the endpoint
        $response = $this->postJson($this->endpoint, $this->generateValidParameters(), $this->generateMockSocialApiHeaders($admin));

        // 4. Retrieve the created post from the database
        $createdThrottleConfig = ThrottleConfig::find($response->json('data.id'));

        // 5. Get the expected data
        $expectedData = (new ThrottleConfigResource($createdThrottleConfig))->resolve();

        // 6. Assertions
        $this->assert201SuccessCreationResponse($response, $this->throttleConfigResourceAttributes(), $expectedData);
    }

    /** @test */
    public function test_401_response_non_registered_user_create_a_new_throttle_config_fails_due_to_unauthorized(): void
    {
        // 1. Generate the mock data
        $admin = $this->generateMockAdmin();

        // 3. Execute the endpoint
        $response = $this->postJson(
            $this->endpoint,
            $this->generateValidParameters(),
            $this->generateMockSocialApiHeaders($admin, false)
        );

        // 6. Assertions
        $this->assert401UnauthorizedResponse($response);
    }

    /** @test */
    public function test_400_response_when_creating_a_throttle_config_with_missing_parameters(): void
    {
        $admin = $this->generateMockAdmin();
        $userUser = $this->generateMockUserUser();
        $userId = $userUser['user_id'];

        Poster::factory(['user_id' => $userId, 'admin_uuid' => $admin->getUuid()])->create();

        // Mock necessary services
        $this->mockUserAuthenticationService($admin);
        $this->generateMockUserUserSession($userUser);

        // Send request with missing parameters
        $response = $this->postJson($this->endpoint, [], $this->generateMockSocialApiHeaders($admin));

        // Expected error structure for validation failure
        $expectedError = [
            'message' => Config::get('tranauth.exceptions.400.400001'),
            'validation' => [
                [
                    'attribute' => 'time_frame_minutes',
                    'errors' => [
                        [
                            'key' => 'Required',
                            'message' => 'required|The time frame minutes field is required.',
                        ],
                    ],
                ],
                [
                    'attribute' => 'max_comments',
                    'errors' => [
                        [
                            'key' => 'Required',
                            'message' => 'required|The max comments field is required.',
                        ],
                    ],
                ],
            ],
        ];

        // Assert 400 response with validation error
        $this->assert400BadRequestValidationFailureResponse($response, $expectedError);
    }

    /** @test */
    public function test_400_response_when_creating_a_throttle_config_with_invalid_parameters(): void
    {
        $admin = $this->generateMockAdmin();
        $userUser = $this->generateMockUserUser();
        $userId = $userUser['user_id'];

        Poster::factory(['user_id' => $userId, 'admin_uuid' => $admin->getUuid()])->create();

        // Mock necessary services
        $this->mockUserAuthenticationService($admin);
        $this->generateMockUserUserSession($userUser);

        // Send request with invalid parameters
        $response = $this->postJson($this->endpoint, [
            'time_frame_minutes' => 0,
            'max_comments' => 0,
        ], $this->generateMockSocialApiHeaders($admin));

        // Expected error structure for validation failure
        $expectedError = [
            'message' => Config::get('tranauth.exceptions.400.400001'),
            'validation' => [
                [
                    'attribute' => 'time_frame_minutes',
                    'errors' => [
                        [
                            'key' => 'Gt',
                            'message' => 'gt|The time frame minutes must be a positive integer.',
                        ],
                    ],
                ],
                [
                    'attribute' => 'max_comments',
                    'errors' => [
                        [
                            'key' => 'Gt',
                            'message' => 'gt|The max comments must be a positive integer.',
                        ],
                    ],
                ],
            ],
        ];

        // Assert 400 response with validation error
        $this->assert400BadRequestValidationFailureResponse($response, $expectedError);
    }

    /** @test */
    public function test_409_response_when_creating_a_throttle_config_due_to_already_exist(): void
    {
        // 1. Generate the mock data
        $admin = $this->generateMockAdmin();
        $userUser = $this->generateMockUserUser();
        $userId = $userUser['user_id'];
        Poster::factory(['user_id' => $userId, 'admin_uuid' => $admin->getUuid()])->create();
        ThrottleConfig::factory(['admin_uuid' => $admin->getUuid()])->create();

        // 2. Mock the dependencies
        $this->mockUserAuthenticationService($admin);
        $this->generateMockUserUserSession($userUser);

        // 3. Execute the endpoint
        $response = $this->postJson($this->endpoint, $this->generateValidParameters(), $this->generateMockSocialApiHeaders($admin));

        // 4. Assertions
        $this->assert409ResourceConflictResponse($response, Lang::get('throttleconfig::errors.throttle_config_already_exists'));
    }
}
