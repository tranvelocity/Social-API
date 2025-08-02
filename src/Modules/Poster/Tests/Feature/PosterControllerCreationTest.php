<?php

namespace Modules\Poster\Tests\Feature;

use Modules\Crm\Member\Constants\MemberStatuses;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Lang;
use Modules\Poster\app\Models\Poster;
use Modules\Poster\app\Resources\PosterResource;

/**
 * @group parallel
 */
final class PosterControllerCreationTest extends PosterControllerTestBase
{
    /**
     * Test creating a poster successfully.
     *
     * @test
     */
    public function test_201_response_create_a_poster_successfully(): void
    {
        // Mock data
        $admin = $this->generateMockAdmin();
        $userUser = $this->generateMockUserUser();
        $validRequestData = $this->generateValidParameters($admin);

        // Mock the dependencies
        $this->mockUserAuthenticationService($admin);
        $this->generateMockUserUserSession($userUser);

        // Mock CRM member and user
        $member = $this->generateMockCrmMember([
            'member_status' => MemberStatuses::REGULAR_MEMBER_STATUS,
            'users' => [
                'user_id' => $userUser['user_id'],
            ],
        ]);
        $user = $this->generateMockCrmUser([
            'user_id' => $userUser['user_id'],
        ]);
        $this->generateMockCrmMembers([$member]);
        $this->generateMockCrmUsers([$user]);

        // Execute the endpoint
        $response = $this->postJson($this->endpoint, $validRequestData, $this->generateMockSocialApiHeaders($admin, false));

        // Retrieve the created Poster from the database
        $createdPoster = Poster::find($response->json('data.id'));
        $createdPoster->setAvatar($user['profile_image']);
        $createdPoster->setNickname($user['nickname']);

        // Get the expected data
        $expectedData = (new PosterResource($createdPoster))->resolve();

        // Assertions
        $this->assert201SuccessCreationResponse($response, $this->posterResourceAttributes(), $expectedData);
    }

    /** @test */
    public function test_409_response_create_a_new_poster_fails_poster_already_exists(): void
    {
        // 1. Generate the mock data
        $admin = $this->generateMockAdmin();
        $poster = Poster::factory(['admin_uuid' => $admin->getUuid()])->create();
        $this->generateMockCrmMembers([$this->generateMockCrmMember(['member_status' => array_rand([MemberStatuses::REGULAR_MEMBER_STATUS, MemberStatuses::REMINDER_MEMBER_STATUS])])]);

        // 2. Mock the dependencies
        $this->mockUserAuthenticationService($admin);
        $this->generateMockUserUserSession($this->generateMockUserUser());

        $requestParams = $this->generateValidParameters();
        $requestParams['user_id'] = $poster->user_id;

        // 3. Execute the endpoint
        $response = $this->postJson($this->endpoint, $requestParams, $this->generateMockSocialApiHeaders($admin));

        // 4. Assertions
        $expectedErrorMessage = Lang::get('poster::errors.poster_already_exists');
        $this->assert409ResourceConflictResponse($response, $expectedErrorMessage);
    }

    /** @test */
    public function test_404_response_create_a_new_poster_fails_user_account_not_exists(): void
    {
        // 1. Generate the mock data
        $admin = $this->generateMockAdmin();

        // 2. Mock the dependencies
        $this->mockUserAuthenticationService($admin);
        $this->generateMockUserUserSession($this->generateMockUserUser());

        $requestParams = $this->generateValidParameters();

        // 3. Execute the endpoint
        $response = $this->postJson($this->endpoint, $requestParams, $this->generateMockSocialApiHeaders($admin, false));

        // 4. Assertions
        $expectedErrorMessage = Lang::get('poster::errors.user_account_not_found');
        $this->assert404NotFoundResponse($response, $expectedErrorMessage);
    }

    /** @test */
    public function test_400_response_create_a_new_poster_fails_request_parameters_invalid(): void
    {
        // 1. Generate the mock data
        $admin = $this->generateMockAdmin();

        // 2. Mock the dependencies
        $this->mockUserAuthenticationService($admin);
        $this->generateMockUserUserSession($this->generateMockUserUser());

        // 4. Attempt to create a Poster with invalid parameters
        $response = $this->postJson($this->endpoint, [], $this->generateMockSocialApiHeaders($admin, false));

        // 5. Expected error response
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

        // 6. Assertions
        $this->assert400BadRequestValidationFailureResponse($response, $expectedError);
    }
}
