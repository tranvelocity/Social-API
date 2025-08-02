<?php

namespace Modules\Poster\app\Services;

use Modules\Poster\app\Models\Poster;

/**
 * Class PosterUpdateService.
 *
 * Service class responsible for handling operations related to posters.
 */
class PosterUpdateService extends PosterService
{
    public function __invoke(string $id, array $params, string $adminUuid): Poster
    {
        $poster = $this->getPosterOrFail($id, $adminUuid);

        $poster = $this->posterRepository->update($poster, $params);

        $this->setPosterMetadata($poster);

        return $poster;
    }
}
