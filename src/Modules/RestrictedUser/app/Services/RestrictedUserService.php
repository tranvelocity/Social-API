<?php

namespace Modules\RestrictedUser\app\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Lang;
use Modules\Cache\app\Services\UserCacheService;
use Modules\Core\app\Exceptions\ConflictException;
use Modules\Core\app\Exceptions\FatalErrorException;
use Modules\Core\app\Exceptions\ResourceNotFoundException;
use Modules\Crm\Member\Repositories\CrmMemberRepositoryInterface;
use Modules\RestrictedUser\app\Models\RestrictedUser;
use Modules\RestrictedUser\app\Repositories\RestrictedUserRepositoryInterface;

/**
 * Service class for managing RestrictedUser resources.
 *
 * This service interacts with the RestrictedUserRepositoryInterface to handle
 * business logic for creating, retrieving, updating, and deleting RestrictedUsers.
 */
class RestrictedUserService
{
    /**
     * The repository interface for RestrictedUser operations.
     *
     * @var RestrictedUserRepositoryInterface
     */
    private RestrictedUserRepositoryInterface $restrictedUserRepository;

    /**
     * The repository interface for User cache operations.
     *
     * @var UserCacheService
     */
    private UserCacheService $userCacheService;

    /**
     * The repository interface for Crm Member operations.
     *
     * @var CrmMemberRepositoryInterface
     */
    private CrmMemberRepositoryInterface $crmMemberRepository;

    /**
     * Constructor.
     *
     * @param CrmMemberRepositoryInterface $crmMemberRepository The CRM member repository.
     * @param RestrictedUserRepositoryInterface $restrictedUserRepository The repository for accessing RestrictedUser data.
     * @param UserCacheService $userCacheService The user cache service for caching user data.
     */
    public function __construct(
        CrmMemberRepositoryInterface $crmMemberRepository,
        RestrictedUserRepositoryInterface $restrictedUserRepository,
        UserCacheService $userCacheService
    ) {
        $this->restrictedUserRepository = $restrictedUserRepository;
        $this->userCacheService = $userCacheService;
        $this->crmMemberRepository = $crmMemberRepository;
    }

    /**
     * Retrieve a collection of RestrictedUsers based on given filter and pagination parameters.
     *
     * @param array $params An array of parameters for filtering and paginating the RestrictedUser results.
     *
     * @return array An associative array with the following keys:
     *   - 'data': An array of RestrictedUser objects.
     *   - 'total': The total number of RestrictedUsers matching the criteria.
     */
    public function getRestrictedUsers(array $params): array
    {
        $restrictedUsers = $this->restrictedUserRepository->getRestrictedUsers($params);
        $this->setRestrictedUsersInfo($restrictedUsers, $params['admin_uuid']);

        return [
            'data' => $restrictedUsers,
            'total' => $this->restrictedUserRepository->getRestrictedUserTotal($params),
        ];
    }

    /**
     * Retrieve a specific RestrictedUser by its ID and associated admin ID.
     *
     * @param string $id The ID of the RestrictedUser to retrieve.
     * @param string $adminUuid The ID of the admin performing the retrieval.
     *
     * @return RestrictedUser The RestrictedUser object retrieved from the repository.
     *
     * @throws ResourceNotFoundException If no RestrictedUser is found with the specified ID and admin UUID.
     */
    public function getRestrictedUser(string $id, string $adminUuid): RestrictedUser
    {
        $restrictedUser = $this->restrictedUserRepository->getRestrictedUser($id, $adminUuid);

        if (!$restrictedUser) {
            throw new ResourceNotFoundException(Lang::get('restricteduser::errors.restricted_user_item_not_found', ['id' => $id]));
        }

        $this->setRestrictedUserInfo($restrictedUser, $adminUuid);

        return $restrictedUser;
    }

    /**
     * Create a new RestrictedUser entry in the system.
     *
     * @param array  $params    The data required to create the RestrictedUser
     * @param string $adminUuid The ID of the admin creating the RestrictedUser.
     *
     * @return RestrictedUser The newly created RestrictedUser object.
     *
     * @throws ConflictException If a RestrictedUser with the same 'user_id' already exists.
     * @throws FatalErrorException If an unexpected error occurs during creation.
     */
    public function createRestrictedUser(array $params, string $adminUuid): RestrictedUser
    {
        $params['admin_uuid'] = $adminUuid;

        $userId = $params['user_id'];

        $this->validateAccount($userId);

        $existingRestrictedUser = $this->restrictedUserRepository->getRestrictedUserByUserId($userId, $adminUuid);

        if ($existingRestrictedUser) {
            throw new ConflictException(Lang::get('restricteduser::errors.restricted_user_already_exists'));
        }

        $restrictedUser = $this->restrictedUserRepository->create(new RestrictedUser(), $params);

        $this->setRestrictedUserInfo($restrictedUser, $adminUuid);

        return $restrictedUser;
    }

    /**
     * Validates whether the associated account exists.
     *
     * @param int $userId The User ID of the account.
     * @throws ResourceNotFoundException If the associated account is not found.
     */
    private function validateAccount(int $userId): void
    {
        $members = $this->crmMemberRepository->getMembers(['user_id' => $userId, 'is_resigned' => false]);

        if (!$members) {
            throw new ResourceNotFoundException(Lang::get('restricteduser::errors.user_account_not_found', ['user_id' => $userId]));
        }
    }

    /**
     * Update an existing RestrictedUser.
     *
     * @param string $id        The ID of the RestrictedUser to update.
     * @param string $adminUuid The ID of the admin performing the update.
     * @param array  $params    The updated data for the RestrictedUser.
     *
     * @return RestrictedUser The updated RestrictedUser object.
     *
     * @throws ResourceNotFoundException If the specified RestrictedUser is not found.
     * @throws ConflictException If a conflict occurs during the update process.
     * @throws FatalErrorException If an unexpected error occurs during the update.
     */
    public function updateRestrictedUser(string $id, string $adminUuid, array $params): RestrictedUser
    {
        $restrictedUser = $this->restrictedUserRepository->getRestrictedUser($id, $adminUuid);

        if (!$restrictedUser) {
            throw new ResourceNotFoundException(Lang::get('restricteduser::errors.restricted_user_item_not_found', ['id' => $id]));
        }

        $restrictedUser = $this->restrictedUserRepository->update($restrictedUser, $params);

        $this->setRestrictedUserInfo($restrictedUser, $adminUuid);

        return $restrictedUser;
    }

    /**
     * Delete a RestrictedUser and its associated media.
     *
     * @param string $id        The ID of the RestrictedUser to delete.
     * @param string $adminUuid The ID of the admin performing the deletion.
     *
     * @return void
     *
     * @throws ResourceNotFoundException If the specified RestrictedUser is not found.
     * @throws FatalErrorException If an error occurs during the deletion process.
     */
    public function deleteRestrictedUser(string $id, string $adminUuid): void
    {
        $restrictedUser = $this->restrictedUserRepository->getRestrictedUser($id, $adminUuid);

        if (!$restrictedUser) {
            throw new ResourceNotFoundException(Lang::get('restricteduser::errors.restricted_user_item_not_found', ['id' => $id]));
        }

        $this->restrictedUserRepository->delete($restrictedUser);
    }

    /**
     * Sets the additional user information (nickname, avatar) for a collection of RestrictedUser entities
     * by retrieving data from the cache using their unique user IDs.
     *
     * @param Collection $restrictedUsers The collection of RestrictedUser entities to update with additional user information.
     * @param string $adminUuid The UUID of the admin responsible for the action.
     *
     * @return void
     */
    private function setRestrictedUsersInfo(Collection &$restrictedUsers, string $adminUuid): void
    {
        // Retrieve unique user IDs from the restricted users collection
        $userIds = $restrictedUsers->pluck('user_id')->unique()->toArray();

        // Fetch corresponding users from the cache based on the user IDs
        $users = $this->userCacheService->getUsersFromCache($adminUuid, $userIds);

        // Update each RestrictedUser with the nickname and avatar retrieved from the cache
        $restrictedUsers->each(function ($restrictedUser) use ($users) {
            if ($restrictedUser instanceof RestrictedUser) {
                $userId = $restrictedUser->getUserId();
                $user = $users[$userId] ?? null;

                if ($user) {
                    $restrictedUser->setNickname($user->nickname);
                    $restrictedUser->setAvatar($user->profile_image);
                }
            }
        });
    }

    /**
     * Sets the additional user information (nickname, avatar) for a single RestrictedUser entity
     * by retrieving the data from the cache using the user ID.
     *
     * @param RestrictedUser $restrictedUser The RestrictedUser entity to update with additional user information.
     * @param string $adminUuid The UUID of the admin responsible for the action.
     *
     * @return void
     */
    public function setRestrictedUserInfo(RestrictedUser &$restrictedUser, string $adminUuid): void
    {
        // Fetch the user data from the cache using the user ID of the RestrictedUser
        $user = $this->userCacheService->getUserDataFromCache($restrictedUser->getUserId(), $adminUuid);

        // Update the RestrictedUser entity with the nickname and avatar, if available
        $restrictedUser->setNickname($user->nickname ?? null);
        $restrictedUser->setAvatar($user->profile_image ?? null);
    }
}
