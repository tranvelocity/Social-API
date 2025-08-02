<?php

namespace Modules\Like\app\Repositories;

use Modules\Core\app\Repositories\RepositoryInterface;

interface LikeRepositoryInterface extends RepositoryInterface
{
    /**
     * Get a collection of likes based on specified parameters and admin ID.
     *
     * @param array $params An array of parameters for filtering the likes.
     * @param string $adminId The admin UUID for identifying the posts.
     * @param bool $allResults Flag to determine if all results should be retrieved.
     * @return iterable An iterable collection of likes.
     */
    public function getLikes(array $params, string $adminId, bool $allResults = true): iterable;

    /**
     * Get the total count of likes based on specified parameters and admin ID.
     *
     * @param array $params An array of parameters for filtering the likes.
     * @param string $adminId The admin UUID for identifying the posts.
     * @return int The total count of likes.
     */
    public function getLikeTotal(array $params, string $adminId): int;

    /**
     * Get the total number of comments for each post ID.
     *
     * @param array $postIds The array of post IDs.
     *
     * @return array An associative array where keys are post IDs and values are comment totals.
     */
    public function getLikeTotalWithPostIds(array $postIds): array;

    /**
     * Get the like status for each post ID by the user with the specified user ID.
     *
     * @param array $postIds   The array of post IDs.
     * @param int   $userId The user ID of the user.
     *
     * @return array An associative array where keys are post IDs and values represent whether
     *               the user has liked the corresponding post (true/false).
     */
    public function getLikeStatusWithPostIds(array $postIds, int $userId): array;
}
