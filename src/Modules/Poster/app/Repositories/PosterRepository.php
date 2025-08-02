<?php

namespace Modules\Poster\app\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Modules\Core\app\Constants\ResourceConstant;
use Modules\Core\app\Repositories\Repository;
use Modules\Poster\app\Models\Poster;

class PosterRepository extends Repository implements PosterRepositoryInterface
{
    /**
     * Retrieve a resource by id.
     *
     * @param string $id
     * @param string $adminId
     * @return Poster|null
     */
    public function getPoster(string $id, string $adminId): ?Poster
    {
        return Poster::query()
            ->where('admin_uuid', '=', $adminId)
            ->where('id', '=', $id)
            ->first();
    }

    /**
     * Get the query builder instance for retrieving posters.
     *
     * This method constructs a query builder instance for the Poster model.
     * It filters posters based on the provided parameters, including admin UUID,
     * user ID, and description.
     *
     * @param array $params The parameters for filtering posters.
     * @return Builder The query builder instance.
     */
    private function getQuery(array $params): Builder
    {
        $query = Poster::query()->where('admin_uuid', '=', $params['admin_uuid']);

        if (!empty($params)) {
            $this->applyFilterIfExists($query, 'user_id', '=', $params);
            $this->applyFilterIfExists($query, 'description', 'LIKE', $params);
        }

        return $query;
    }

    /**
     * Get the total count of posters based on the provided parameters.
     *
     * This method retrieves the total count of posters matching the provided parameters.
     *
     * @param array $params The parameters for filtering posters.
     * @return int The total count of posters.
     */
    public function getPosterTotal(array $params): int
    {
        return $this->getQuery($params)->count();
    }

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
    public function getPosters(array $params, bool $withPagination = true): iterable
    {
        $query = $this->getQuery($params)->orderBy('created_at', 'DESC');

        if ($withPagination) {
            $offset = $params['offset'] ?? ResourceConstant::OFFSET_DEFAULT;
            $limit = $params['limit'] ?? ResourceConstant::LIMIT_DEFAULT;

            $query->skip($offset)->limit($limit);
        }

        return $query->get();
    }

    /**
     * Get a Poster by User ID and optional Admin ID.
     *
     * @param int         $userId User ID to search for
     * @param string      $adminUuid    Admin UUID (optional)
     *
     * @return Poster|null The poster instance, or null if not found.
     */
    public function getPosterByUserId(int $userId, string $adminUuid): ?Poster
    {
        return Poster::query()
            ->when($adminUuid, function ($query) use ($adminUuid) {
                return $query->where('admin_uuid', $adminUuid);
            })
            ->where('user_id', $userId)
            ->first();
    }
}
