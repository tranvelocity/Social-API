<?php

namespace Modules\Poster\app\Services;

use Illuminate\Database\Eloquent\Collection;

/**
 * Class PosterRetrievalService.
 *
 * Service class responsible for handling operations related to posters.
 */
class PosterRetrievalService extends PosterService
{
    /**
     * Retrieves a collection of posters based on specified parameters.
     *
     * @param array $params An array of parameters to filter and paginate the posters.
     * @return array An array containing 'data' (posters) and 'total' (total count of posters).
     */
    public function getPosters(array $params): array
    {
        $posters = $this->posterRepository->getPosters($params);

        $this->setPostersMetadata($posters);

        return [
            'data' => $posters,
            'total' => $this->posterRepository->getPosterTotal($params),
        ];
    }

    /**
     * Sets metadata for the collection of posters.
     *
     * @param Collection $posters The collection of posters.
     */
    protected function setPostersMetadata(Collection $posters): void
    {
        $posters->each(function ($poster) {
            $this->setPosterMetadata($poster);
        });
    }
}
