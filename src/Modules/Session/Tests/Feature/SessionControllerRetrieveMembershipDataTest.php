<?php

namespace Modules\Session\Tests\Feature;

use Modules\Crm\Member\Constants\MemberStatuses;
use Illuminate\Testing\TestResponse;
use Modules\Poster\app\Models\Poster;
use Modules\Role\app\Entities\Role;
use Modules\Test\Tests\Feature\ApiTestCase;

/**
 * @group parallel
 */
final class SessionControllerRetrieveMembershipDataTest extends ApiTestCase
{
    private string $endpoint = '/1/session';

    /**
     * Test retrieving data for a non-registered user.
     *
     * @test
     */
    public function test_200_response_retrieve_non_registered_user_data(): void
    {
        // Mock data
        $admin = $this->generateMockAdmin();

        // Mock the dependencies
        $this->mockUserAuthenticationService($admin);

        // Execute the endpoint
        $response = $this->getJson($this->endpoint, $this->generateMockSocialApiHeaders($admin, false));

        // Assertions
        $this->assertSuccessCustomResponse($response, ['role' => Role::nonRegisteredUser()->getRole()]);
    }

    /**
     * Test retrieving data for a poster.
     *
     * @test
     */
    public function test_200_response_retrieve_poster_data(): void
    {
        // Mock data
        $admin = $this->generateMockAdmin();
        $userUser = $this->generateMockUserUser();
        $userId = $userUser['user_id'];
        $poster = Poster::factory(['admin_uuid' => $admin->getUuid(), 'user_id' => $userId])->create();

        // Mock the dependencies
        $this->mockUserAuthenticationService($admin);
        $this->generateMockUserUserSession($userUser);

        $member = $this->generateMockCrmMember(['member_status' => MemberStatuses::REGULAR_MEMBER_STATUS, 'users' => ['user_id' => $userId]]);
        $this->generateMockCrmMembers([$member]);

        // Execute the endpoint
        $response = $this->getJson($this->endpoint, $this->generateMockSocialApiHeaders($admin));

        // Assertions
        $this->assertMembershipDataResponse($response, Role::poster(), $userId, $member);
    }

    /**
     * Test retrieving data for a paid member.
     *
     * @test
     */
    public function test_200_response_retrieve_paid_member_data(): void
    {
        // Mock data
        $admin = $this->generateMockAdmin();
        $userUser = $this->generateMockUserUser();
        $userId = $userUser['user_id'];

        // Mock the dependencies
        $this->mockUserAuthenticationService($admin);
        $this->generateMockUserUserSession($userUser);

        $member = $this->generateMockCrmMember(['member_status' => MemberStatuses::REGULAR_MEMBER_STATUS, 'users' => ['user_id' => $userId]]);
        $this->generateMockCrmMembers([$member]);

        // Execute the endpoint
        $response = $this->getJson($this->endpoint, $this->generateMockSocialApiHeaders($admin));

        // Assertions
        $this->assertMembershipDataResponse($response, Role::paidMember(), $userId, $member);
    }

    /**
     * Test retrieving data for a free member.
     *
     * @test
     */
    public function test_200_response_retrieve_free_member_data(): void
    {
        // Mock data
        $admin = $this->generateMockAdmin();
        $userUser = $this->generateMockUserUser();
        $userId = $userUser['user_id'];

        // Mock the dependencies
        $this->mockUserAuthenticationService($admin);
        $this->generateMockUserUserSession($userUser);

        $member = $this->generateMockCrmMember(['member_status' => MemberStatuses::FREE_MEMBER_STATUS, 'users' => ['user_id' => $userId]]);
        $this->generateMockCrmMembers([$member]);

        // Execute the endpoint
        $response = $this->getJson($this->endpoint, $this->generateMockSocialApiHeaders($admin));

        // Assertions
        $this->assertMembershipDataResponse($response, Role::freeMember(), $userId, $member);
    }

    /**
     * Asserts the response for membership data.
     *
     * @param TestResponse $response The JSON response.
     * @param Role $role The role object.
     * @param int $userId The User ID.
     * @param array $member The member data.
     * @return void
     */
    private function assertMembershipDataResponse(TestResponse $response, Role $role, int $userId, array $member): void
    {
        // Expected data
        $expectedData = [
            'role' => $role->getRole(),
            'user_id' => $userId,
            'user_id' => $member['users']['user_id'],
            'email' => $member['users']['email'],
            'last_name' => $member['users']['last_name'],
            'first_name' => $member['users']['first_name'],
            'last_name_kana' => $member['users']['last_name_kana'],
            'first_name_kana' => $member['users']['first_name_kana'],
            'nickname' => $member['users']['nickname'],
            'avatar' => $member['users']['profile_image'],
            'member_no' => $member['member_no'],
            'member_status' => $member['member_status'],
        ];

        // Assertions
        $this->assert200ResponseWithSimpleResource($response, [
            'role',
            'user_id',
            'user_id',
            'email',
            'last_name',
            'first_name',
            'last_name_kana',
            'first_name_kana',
            'nickname',
            'avatar',
            'member_no',
            'member_status',
        ], $expectedData);
    }
}
