<?php

namespace Modules\Role\app\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Modules\Cache\app\Services\MemberCacheService;
use Modules\Core\app\Repositories\CacheRepository;
use Modules\Crm\Member\Constants\MemberStatuses;
use Modules\Crm\Member\Repositories\CrmMemberRepositoryInterface;
use Modules\Poster\app\Repositories\PosterRepositoryInterface;
use Modules\Role\app\Entities\Role;

/**
 * Class RoleMiddleware.
 *
 * Middleware to determine the role of the user based on authorization data.
 */
class RoleMiddleware
{
    /**
     * The CRM Member Repository instance.
     *
     * @var CrmMemberRepositoryInterface
     */
    private CrmMemberRepositoryInterface $crmMemberRepository;

    /**
     * The Poster Repository instance.
     *
     * @var PosterRepositoryInterface
     */
    private PosterRepositoryInterface $posterRepository;

    /**
     * Create a new RoleMiddleware instance.
     *
     * @param CrmMemberRepositoryInterface $crmMemberRepository
     * @param PosterRepositoryInterface   $posterRepository
     */
    public function __construct(
        CrmMemberRepositoryInterface $crmMemberRepository,
        PosterRepositoryInterface $posterRepository
    ) {
        $this->crmMemberRepository = $crmMemberRepository;
        $this->posterRepository = $posterRepository;
    }

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $adminUuid = $this->getAuthorizedAdminId($request);
        $userId = $this->getAuthorizedUserId($request);

        $role = $this->determineUserRole($adminUuid, $userId);

        $this->setRequestAttributes($request, $role);

        return $next($request);
    }

    /**
     * Determines the user role based on provided parameters.
     *
     * @param string $adminUuid   The admin UUID.
     * @param int|null    $userId  The user ID.
     *
     * @return int Returns the user role as an integer.
     */
    private function determineUserRole(string $adminUuid, ?int $userId): int
    {
        if ($userId) {
            $cacheKey = MemberCacheService::getMemberRoleCacheKey($adminUuid, $userId);

            if (!CacheRepository::has($cacheKey)) {
                $role = $this->calculateUserRole($userId, $adminUuid);
                CacheRepository::store($cacheKey, $role, Config::get('cache.cache_expiration_default', 3600));
            }

            return (int) CacheRepository::get($cacheKey);
        }

        return Role::nonRegisteredUser()->getRole();
    }

    /**
     * Calculates the user role based on user ID and admin UUID.
     *
     * @param int $userId The user ID.
     * @param string $adminUuid  The admin UUID.
     *
     * @return int Returns the user role as a int.
     */
    private function calculateUserRole(int $userId, string $adminUuid): int
    {
        if ($this->isPoster($userId, $adminUuid)) {
            return Role::poster()->getRole();
        } elseif ($this->isPremiumMember($userId)) {
            return Role::paidMember()->getRole();
        } else {
            return Role::freeMember()->getRole();
        }
    }

    /**
     * Set role and user_id attributes in the request.
     *
     * @param Request $request
     * @param  int $role
     * @return void
     */
    private function setRequestAttributes(Request &$request, int $role): void
    {
        $request->merge([
            'role' => $role,
        ]);
    }

    /**
     * Check if the user with the given user ID and admin UUID is a poster.
     *
     * @param  int    $userId The User ID of the user.
     * @param  string $adminUuid  The admin UUID associated with the user.
     * @return bool               True if the user is a poster, false otherwise.
     */
    private function isPoster(int $userId, string $adminUuid): bool
    {
        $poster = $this->posterRepository->getPosterByUserId($userId, $adminUuid);

        return (bool) $poster;
    }

    /**
     * Check if the user with the given user ID is a premium member.
     *
     * @param  int $userId The User ID of the user.
     * @return bool            True if the user is a premium member, false otherwise.
     */
    private function isPremiumMember(int $userId): bool
    {
        $members = $this->crmMemberRepository->getMembers(['user_id' => $userId, 'is_resigned' => false]);

        return !empty($members) && $this->hasRegularOrReminderStatus($members[0]);
    }

    /**
     * Check if the member has a regular or reminder status.
     *
     * @param array $member The member data.
     *
     * @return bool Returns true if the member has a regular or reminder status, false otherwise.
     */
    private function hasRegularOrReminderStatus(array $member): bool
    {
        return isset($member['member_status']) && in_array($member['member_status'], [MemberStatuses::REGULAR_MEMBER_STATUS, MemberStatuses::REMINDER_MEMBER_STATUS]);
    }

    /**
     * Get the authorized admin ID from the request.
     *
     * @param Request $request
     *
     * @return mixed
     */
    protected function getAuthorizedAdminId(Request $request): mixed
    {
        return $request->get(Config::get('tranauth.authorized_admin_id'));
    }

    /**
     * Get the authorized administrator ID from the request.
     *
     * @param Request $request
     *
     * @return mixed
     */
    protected function getAuthorizedMerchantId(Request $request): mixed
    {
        return $request->get(Config::get('tranauth.authorized_merchant_id'));
    }

    /**
     * Get the authorized User ID from the request.
     *
     * @param Request $request
     *
     * @return mixed
     */
    protected function getAuthorizedUserId(Request $request): mixed
    {
        return $request->get(Config::get('tranauth.authorized_user_id'));
    }
}
