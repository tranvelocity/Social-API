<?php

namespace Modules\RestrictedUser\tests\Feature;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Lang;
use Modules\Poster\app\Models\Poster;
use Modules\RestrictedUser\app\Models\RestrictedUser;
use Modules\RestrictedUser\app\Resources\RestrictedUserResource;

/**
 * @group parallel
 */
final class RestrictedUserControllerCreationTest extends RestrictedUserControllerTestBase
{
    /**
     * Generate an array of valid parameters for testing post creation.
     */
    private function generateValidParameters(): array
    {
        return [
            'user_id' => $this->faker->numberBetween(1, 1000),
            'remarks' => $this->faker->sentence(),
        ];
    }

    /** @test */
    public function test_201_response_when_creating_a_new_restricted_user_successfully(): void
    {
        // 1. Generate the mock data
        $admin = $this->generateMockAdmin();
        $userUser = $this->generateMockUserUser();

        // 2. Mock the dependencies
        $this->mockUserAuthenticationService($admin);
        $this->generateMockUserUserSession($userUser);
        $member = $this->generateMockCrmMember([
            'users' => [
                'user_id' => $userUser['user_id'],
            ],
        ]);
        $user = $this->generateMockCrmUser([
            'user_id' => $userUser['user_id'],
        ]);
        $this->generateMockCrmMembers([$member]);
        $this->generateMockCrmUsers([$user]);

        // 3. Execute the endpoint
        $response = $this->postJson($this->endpoint, $this->generateValidParameters(), $this->generateMockSocialApiHeaders($admin));

        // 4. Retrieve the created post from the database
        $restrictedUser = RestrictedUser::find($response->json('data.id'));
        $restrictedUser->setAvatar($user['profile_image']);
        $restrictedUser->setNickname($user['nickname']);

        // 5. Get the expected data
        $expectedData = (new RestrictedUserResource($restrictedUser))->resolve();

        // 6. Assertions
        $this->assert201SuccessCreationResponse($response, $this->restrictedUserResourceAttributes(), $expectedData);
    }

    /** @test */
    public function test_401_response_when_non_registered_user_retrieve_collection_of_restricted_users_fails_due_to_unauthorized(): void
    {
        // 1. Generate the mock data
        $admin = $this->generateMockAdmin();

        // 2. Execute the endpoint
        $response = $this->postJson($this->endpoint, $this->generateValidParameters(), $this->generateMockSocialApiHeaders($admin, false));

        // 3. Assertions
        $this->assert401UnauthorizedResponse($response);
    }

    /** @test */
    public function test_400_response_when_creating_a_new_restricted_user_with_missing_parameters(): void
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
                    'attribute' => 'user_id',
                    'errors' => [
                        [
                            'key' => 'Required',
                            'message' => 'required|The user id field is required.',
                        ],
                    ],
                ],
            ],
        ];

        // Assert 400 response with validation error
        $this->assert400BadRequestValidationFailureResponse($response, $expectedError);
    }

    /** @test */
    public function test_400_response_when_creating_a_new_restricted_user_with_invalid_parameters(): void
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
            'user_id' => 0,
            'remarks' => $this->faker->sentence(),
        ], $this->generateMockSocialApiHeaders($admin));

        // Expected error structure for validation failure
        $expectedError = [
            'message' => Config::get('tranauth.exceptions.400.400001'),
            'validation' => [
                [
                    'attribute' => 'user_id',
                    'errors' => [
                        [
                            'key' => 'Gt',
                            'message' => 'gt|The user id must be a positive integer.',
                        ],
                    ],
                ],
            ],
        ];

        // Assert 400 response with validation error
        $this->assert400BadRequestValidationFailureResponse($response, $expectedError);
    }

    /** @test */
    public function test_409_response_when_creating_a_restricted_user_due_to_already_exist(): void
    {
        // 1. Generate the mock data
        $admin = $this->generateMockAdmin();
        $userUser = $this->generateMockUserUser();
        $userId = $userUser['user_id'];
        RestrictedUser::factory(['admin_uuid' => $admin->getUuid(), 'user_id' => $userId])->create();

        // 2. Mock the dependencies
        $this->mockUserAuthenticationService($admin);
        $this->generateMockUserUserSession($userUser);
        $member = $this->generateMockCrmMember([
            'users' => [
                'user_id' => $userUser['user_id'],
            ],
        ]);
        $user = $this->generateMockCrmUser([
            'user_id' => $userUser['user_id'],
        ]);
        $this->generateMockCrmMembers([$member]);
        $this->generateMockCrmUsers([$user]);

        // 3. Execute the endpoint
        $response = $this->postJson($this->endpoint, [
            'user_id' => $userId,
            'remarks' => $this->faker->sentence(),
        ], $this->generateMockSocialApiHeaders($admin));

        // 4. Assertions
        $this->assert409ResourceConflictResponse($response, Lang::get('restricteduser::errors.restricted_user_already_exists'));
    }

    /** @test */
    public function test_404_response_when_creating_a_restricted_user_due_to_user_account_not_exists(): void
    {
        // 1. Generate the mock data
        $admin = $this->generateMockAdmin();

        // 2. Mock the dependencies
        $this->mockUserAuthenticationService($admin);
        $this->generateMockUserUserSession($this->generateMockUserUser());

        $requestParams = $this->generateValidParameters();

        // 3. Execute the endpoint
        $response = $this->postJson($this->endpoint, $requestParams, $this->generateMockSocialApiHeaders($admin));

        // 4. Assertions
        $expectedErrorMessage = Lang::get('restricteduser::errors.user_account_not_found');
        $this->assert404NotFoundResponse($response, $expectedErrorMessage);
    }
}
