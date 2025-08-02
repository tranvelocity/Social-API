<?php

namespace Modules\RestrictedUser\app\Repositories;

use Modules\Core\app\Repositories\RepositoryInterface;
use Modules\RestrictedUser\app\Models\RestrictedUser;

interface RestrictedUserRepositoryInterface extends RepositoryInterface
{
    /**
     * Retrieves a RestrictedUser entity by its unique ID, scoped by admin UUID.
     *
     * @param string $id The unique identifier of the RestrictedUser entity.
     * @param string $adminUuid The UUID of the admin associated with the RestrictedUser entity.
     *
     * @return RestrictedUser|null The RestrictedUser entity if found, or null if not found.
     */
    public function getRestrictedUser(string $id, string $adminUuid): ?RestrictedUser;

    /**
     * Retrieves a collection of RestrictedUser entities based on the provided parameters.
     * Supports pagination via 'offset' and 'limit' parameters.
     *
     * @param array $params An associative array containing filtering and pagination parameters.
     *
     * @return iterable A collection of RestrictedUser entities matching the specified criteria.
     */
    public function getRestrictedUsers(array $params): iterable;

    /**
     * Counts the total number of RestrictedUser entities that match the specified filtering parameters.
     *
     * @param array $params An associative array containing filtering parameters.
     *
     * @return int The total number of RestrictedUser entities matching the given parameters.
     */
    public function getRestrictedUserTotal(array $params): int;

    /**
     * Retrieves a RestrictedUser entity by its user ID, scoped by admin UUID.
     *
     * @param int $userId The User ID of the RestrictedUser entity.
     * @param string $adminUuid The UUID of the admin associated with the RestrictedUser entity.
     *
     * @return RestrictedUser|null The RestrictedUser entity if found, or null if not found.
     */
    public function getRestrictedUserByUserId(int $userId, string $adminUuid): ?RestrictedUser;
}
