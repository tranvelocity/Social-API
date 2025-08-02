<?php

namespace Modules\Cache\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Testing\TestResponse;
use Modules\Admin\Entities\Admin;
use Modules\Cache\app\Services\MemberCacheService;
use Modules\Cache\app\Services\NGWordCacheService;
use Modules\Cache\app\Services\UserCacheService;
use Modules\Core\app\Repositories\CacheRepository;
use Modules\Crm\Member\Constants\MemberStatuses;
use Modules\Crm\NGWord\Traits\NGWordGenerator;
use Modules\Role\app\Entities\Role;
use Modules\Test\Tests\Feature\ApiTestCase;

/**
 * @group parallel
 */
class UserRoleCacheControllerTest extends ApiTestCase
{
    use DatabaseTransactions;

    private string $endpoint = '/1/clear-caches';

    /**
     * Clear the user cache for a specific user and return the response.
     *
     * @param Admin $admin The admin object.
     * @param int $userId The User ID of the user.
     * @return TestResponse The test response.
     */
    private function destroyCachedUserData(Admin $admin, int $userId): TestResponse
    {
        $headers = $this->generateMockSocialApiHeaders($admin, false);

        return $this->deleteJson("{$this->endpoint}/member/{$userId}", [], $headers);
    }

    /**
     * Clear the cached NG words and return the response.
     *
     * @param Admin $admin The admin object.
     * @return TestResponse The test response.
     */
    private function destroyCachedNGWords(Admin $admin, bool $withLoginSession = true): TestResponse
    {
        $headers = $this->generateMockSocialApiHeaders($admin, $withLoginSession);

        return $this->deleteJson("{$this->endpoint}/ng-words", [], $headers);
    }

    /**
     * Test clearing the cache with a poster role.
     *
     * @return void
     * @test
     */
    public function test_204_response_clear_cached_crm_api_member_data(): void
    {
        // 1. Generate the mock data
        $admin = $this->generateMockAdmin();
        $userUser = $this->generateMockUserUser();
        $userId = $userUser['user_id'];
        $member = $this->generateMockCrmMember(['member_status' => MemberStatuses::REGULAR_MEMBER_STATUS]);
        $user = $this->generateMockCrmUser();

        // Store role in cache
        $memberDataCacheKey = MemberCacheService::getMemberDataCacheKey($admin->getUuid(), $userId);
        $userDataCacheKey = UserCacheService::getUserDataCacheKey($admin->getUuid(), $userId);
        $memberRoleCacheKey = MemberCacheService::getMemberRoleCacheKey($admin->getUuid(), $userId);
        CacheRepository::store($memberRoleCacheKey, Role::paidMember()->getRole());
        CacheRepository::store($memberDataCacheKey, $member);
        CacheRepository::store($userDataCacheKey, $user);

        // 2. Mock the dependencies
        $this->mockUserAuthenticationService($admin);
        $this->generateMockUserUserSession($userUser);

        // 3.Execute the endpoint
        $response = $this->destroyCachedUserData($admin, $userId);

        // 4. Assertions
        $this->assert204NoContentResponse($response);
    }

    /** @test */
    public function test_204_response_clear_cached_ng_words(): void
    {
        // Mock Admin and User User
        $admin = $this->generateMockAdmin();
        $this->mockUserAuthenticationService($admin);

        $userUser = $this->generateMockUserUser();
        $userId = $userUser['user_id'];
        $this->generateMockUserUserSession($userUser);

        // Mock CRM User and Member
        $user = $this->generateMockCrmUser(['user_id' => $userId]);
        $this->generateMockCrmUsers([$user]);
        $member = $this->generateMockCrmMember(['users' => ['user_id' => $userId]]);
        $this->generateMockCrmMembers([$member]);

        // Mock Prohibited Words
        $cacheKey = NGWordCacheService::getNGWordsCacheKey($admin->getUuid());
        $ngWords = NGWordGenerator::generate();
        CacheRepository::store($cacheKey, $ngWords);

        // Execute the endpoint
        $response = $this->destroyCachedNGWords($admin);

        // Assertions
        $this->assert204NoContentResponse($response);
    }
}
