<?php

namespace Modules\Poster\app\Services;

use Illuminate\Support\Facades\Lang;
use Modules\Cache\app\Services\MemberCacheService;
use Modules\Core\app\Exceptions\ConflictException;
use Modules\Core\app\Exceptions\ResourceNotFoundException;
use Modules\Core\app\Repositories\CacheRepository;
use Modules\Poster\app\Models\Poster;

/**
 * Class PosterCreationService.
 *
 * Service class responsible for handling operations related to posters.
 */
class PosterCreationService extends PosterService
{
    /**
     * Creates a new poster with the given parameters.
     *
     * @param array $params An array containing parameters for creating the poster.
     * @param string $adminUuid The UUID of the admin creating the poster.
     * @return Poster The newly created poster object.
     * @throws ResourceNotFoundException If the user account associated with the provided User ID is not found.
     * @throws ConflictException If a poster already exists for the given admin UUID and User ID combination.
     */
    public function __invoke(array $params, string $adminUuid): Poster
    {
        $userId = $params['user_id'];

        $this->validateAccount($userId);

        $this->checkUserExistence($userId, $adminUuid);

        $poster = $this->posterRepository->create(new Poster(), array_merge($params, ['admin_uuid' => $adminUuid]));

        $this->setPosterMetadata($poster);

        // Delete cached member role if exists
        $memberRoleCachedKey = MemberCacheService::getMemberRoleCacheKey($adminUuid, $userId);
        if (CacheRepository::has($memberRoleCachedKey)) {
            CacheRepository::forget($memberRoleCachedKey);
        }

        return $poster;
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
            throw new ResourceNotFoundException(Lang::get('poster::errors.user_account_not_found', ['user_id' => $userId]));
        }
    }

    /**
     * Check if a poster with the given user ID already exists.
     *
     * @param int    $userId The user ID to check for.
     * @param string $adminUuid  The admin UUID for filtering.
     *
     * @throws ConflictException If a poster with the given user ID already exists.
     */
    private function checkUserExistence(int $userId, string $adminUuid): void
    {
        $existingPoster = $this->posterRepository->getPosterByUserId($userId, $adminUuid);
        if ($existingPoster) {
            throw new ConflictException(Lang::get('poster::errors.poster_already_exists'));
        }
    }
}
