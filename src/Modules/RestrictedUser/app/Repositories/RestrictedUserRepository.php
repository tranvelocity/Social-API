<?php

namespace Modules\RestrictedUser\app\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Modules\Core\app\Repositories\Repository;
use Modules\RestrictedUser\app\Models\RestrictedUser;

class RestrictedUserRepository extends Repository implements RestrictedUserRepositoryInterface
{
    /**
     * Builds and returns a query builder instance for fetching RestrictedUser entities
     * based on the provided filtering parameters.
     *
     * @param array $params An associative array containing filtering parameters for the query
     *
     * @return Builder The query builder instance configured for fetching RestrictedUser entities.
     */
    private function getQuery(array $params): Builder
    {
        return RestrictedUser::query()
            ->where('admin_uuid', '=', $params['admin_uuid'])
            ->when(!empty($params), function ($query) use ($params) {
                $this->applyFilterIfExists($query, 'user_id', '=', $params);
                $this->applyFilterIfExists($query, 'remarks', 'LIKE', $params);
            });
    }

    /**
     * Retrieves a RestrictedUser entity by its unique ID, scoped by admin UUID.
     *
     * @param string $id The unique identifier of the RestrictedUser entity.
     * @param string $adminUuid The UUID of the admin associated with the RestrictedUser entity.
     *
     * @return RestrictedUser|null The RestrictedUser entity if found, or null if not found.
     */
    public function getRestrictedUser(string $id, string $adminUuid): ?RestrictedUser
    {
        return RestrictedUser::query()
            ->where('admin_uuid', '=', $adminUuid)
            ->where('id', '=', $id)
            ->first();
    }

    /**
     * Retrieves a collection of RestrictedUser entities based on the provided parameters.
     * Supports pagination via 'offset' and 'limit' parameters.
     *
     * @param array $params An associative array containing filtering and pagination parameters
     *
     * @return iterable A collection of RestrictedUser entities matching the specified criteria.
     */
    public function getRestrictedUsers(array $params): iterable
    {
        $offset = $params['offset'];
        $limit = $params['limit'];
        unset($params['offset'], $params['limit']);

        return $this->getQuery($params)
            ->orderBy('created_at', 'DESC')
            ->skip($offset)
            ->limit($limit)
            ->get();
    }

    /**
     * Counts the total number of RestrictedUser entities that match the specified filtering parameters.
     *
     * @param array $params An associative array containing filtering parameters
     *
     * @return int The total number of RestrictedUser entities matching the given parameters.
     */
    public function getRestrictedUserTotal(array $params): int
    {
        return $this->getQuery($params)->count();
    }

    /**
     * Retrieves a RestrictedUser entity by its user ID, scoped by admin UUID.
     *
     * @param int $userId The User ID of the RestrictedUser entity.
     * @param string $adminUuid The UUID of the admin associated with the RestrictedUser entity.
     *
     * @return RestrictedUser|null The RestrictedUser entity if found, or null if not found.
     */
    public function getRestrictedUserByUserId(int $userId, string $adminUuid): ?RestrictedUser
    {
        return RestrictedUser::query()
            ->where('admin_uuid', '=', $adminUuid)
            ->where('user_id', '=', $userId)
            ->first();
    }
}
