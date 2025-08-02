<?php

namespace Modules\Poster\app\Repositories;

use Modules\Core\app\Repositories\RepositoryInterface;
use Modules\Poster\app\Models\Poster;

interface PosterRepositoryInterface extends RepositoryInterface
{
    /**
     * Retrieve a resource by id.
     *
     * @param string $id
     * @param string $adminId
     * @return Poster|null
     */
    public function getPoster(string $id, string $adminId): ?Poster;

    /**
     * Get the total count of posters based on the provided parameters.
     *
     * This method retrieves the total count of posters matching the provided parameters.
     *
     * @param array $params The parameters for filtering posters.
     * @return int The total count of posters.
     */
    public function getPosterTotal(array $params): int;

    /**
     * Get posters based on the provided parameters.
     *
     * This method retrieves posters based on the specified parameters. By default, it orders the posters by
     * their creation date in descending order. Optionally, it can paginate the results if the $withPagination
     * parameter is set to true, with default offset and limit values if not provided in the parameters.
     *
     * @param array $params An array of parameters for filtering and pagination (optional).
     * @param bool $withPagination Determines whether to paginate the results (default: true).
     * @return iterable A collection of posters matching the criteria.
     */
    public function getPosters(array $params, bool $withPagination = true): iterable;

    /**
     * Get a Poster by User ID and optional Admin ID.
     *
     * @param int         $userId User ID to search for
     * @param string      $adminUuid    Admin UUID (optional)
     *
     * @return Poster|null The poster instance, or null if not found.
     */
    public function getPosterByUserId(int $userId, string $adminUuid): ?Poster;
}
