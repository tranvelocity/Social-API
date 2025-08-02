<?php

namespace Modules\Poster\app\Services;

use Illuminate\Support\Facades\Lang;
use Modules\Cache\app\Services\UserCacheService;
use Modules\Core\app\Exceptions\ResourceNotFoundException;
use Modules\Crm\Member\Repositories\CrmMemberRepositoryInterface;
use Modules\Poster\app\Models\Poster;
use Modules\Poster\app\Repositories\PosterRepositoryInterface;

/**
 * Class PosterService.
 *
 * Service class responsible for handling operations related to posters.
 */
class PosterService
{
    protected CrmMemberRepositoryInterface $crmMemberRepository;
    protected PosterRepositoryInterface $posterRepository;
    protected UserCacheService $userCacheService;

    /**
     * PosterService constructor.
     *
     * @param CrmMemberRepositoryInterface $crmMemberRepository The CRM member repository.
     * @param PosterRepositoryInterface $posterRepository The poster repository.
     * @param UserCacheService $userCacheService The user cache service.
     */
    public function __construct(
        CrmMemberRepositoryInterface $crmMemberRepository,
        PosterRepositoryInterface $posterRepository,
        UserCacheService $userCacheService
    ) {
        $this->posterRepository = $posterRepository;
        $this->crmMemberRepository = $crmMemberRepository;
        $this->userCacheService = $userCacheService;
    }

    /**
     * Sets metadata for a poster.
     *
     * @param Poster $poster The poster.
     */
    public function setPosterMetadata(Poster &$poster): void
    {
        $user = $this->userCacheService->getUserDataFromCache($poster->getUserId(), $poster->getAdminUuid());
        $poster->setNickname($user->nickname ?? null);
        $poster->setAvatar($user->profile_image ?? null);
    }

    /**
     * Check if a user with the specified User ID is a poster.
     *
     * This method queries the poster repository to determine if a user with the provided
     * User ID is registered as a poster. It returns true if the user is found as a poster,
     * otherwise false.
     *
     * @param int    $userId The User ID of the user to check.
     * @param string $adminUuid  The UUID of the admin performing the check.
     *
     * @return bool True if the user with the given User ID is a poster, false otherwise.
     */
    public function isPoster(int $userId, string $adminUuid): bool
    {
        $existingPoster = $this->posterRepository->getPosterByUserId($userId, $adminUuid);

        return (bool) $existingPoster;
    }

    /**
     * Retrieves a specific poster by ID for a given admin.
     *
     * @param string $id The ID of the poster to retrieve.
     * @param string $adminId The ID of the admin associated with the poster.
     * @return Poster The retrieved poster object.
     * @throws ResourceNotFoundException If no poster is found with the specified ID and admin ID combination.
     */
    public function getPoster(string $id, string $adminId): Poster
    {
        $poster = $this->getPosterOrFail($id, $adminId);

        $this->setPosterMetadata($poster);

        return $poster;
    }

    /**
     * Retrieves a specific poster by ID for a given admin.
     *
     * @param string $id The ID of the poster to retrieve.
     * @param string $adminId The ID of the admin associated with the poster.
     * @return Poster The retrieved poster object.
     * @throws ResourceNotFoundException If no poster is found with the specified ID and admin ID combination.
     */
    public function getPosterOrFail(string $id, string $adminId): Poster
    {
        $poster = $this->posterRepository->getPoster($id, $adminId);

        if (!$poster) {
            throw new ResourceNotFoundException(Lang::get('poster::errors.poster_not_found', ['id' => $id]));
        }

        return $poster;
    }
}
