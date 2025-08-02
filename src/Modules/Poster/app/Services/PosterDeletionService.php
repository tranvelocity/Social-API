<?php

namespace Modules\Poster\app\Services;

use Modules\Cache\app\Services\MemberCacheService;
use Modules\Core\app\Exceptions\ResourceNotFoundException;
use Modules\Core\app\Repositories\CacheRepository;

/**
 * Class PosterDeletionService.
 *
 * Service class responsible for handling operations related to posters.
 */
class PosterDeletionService extends PosterService
{
    /**
     * Deletes a poster with the given ID.
     *
     * @param string $id The ID of the poster to delete.
     * @param string $adminUuid The UUID of the admin performing the deletion.
     * @throws ResourceNotFoundException If the poster with the specified ID and admin UUID is not found.
     */
    public function __invoke(string $id, string $adminUuid): void
    {
        $poster = $this->getPosterOrFail($id, $adminUuid);

        // Delete cached member role if exists
        $memberRoleCachedKey = MemberCacheService::getMemberRoleCacheKey($adminUuid, $poster->getUserId());
        if (CacheRepository::has($memberRoleCachedKey)) {
            CacheRepository::forget($memberRoleCachedKey);
        }

        $this->posterRepository->delete($this->getPoster($id, $adminUuid));
    }
}
