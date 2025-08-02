<?php

namespace Modules\Comment\app\Repositories;

use Illuminate\Support\Collection;
use Modules\Comment\app\Models\Comment;
use Modules\Core\app\Repositories\RepositoryInterface;

interface CommentRepositoryInterface extends RepositoryInterface
{
    /**
     * Retrieve a comment by its ID, optionally filtering by post ID.
     *
     * @param string $id The ID of the comment to retrieve.
     * @param string $adminUuid The admin ID associated with the comment.
     * @param string|null $postId The optional post ID to filter comments by.
     *
     * @return Comment|null The retrieved comment or null if not found.
     */
    public function getComment(string $id, string $adminUuid, ?string $postId = null): ?Comment;

    /**
     * Retrieve comments based on the provided parameters.
     *
     * @param array $params The parameters to filter the query.
     * @param string $adminUuid The admin ID associated with the comments.
     * @param bool $withPagination
     * @return iterable The collection of retrieved comments.
     */
    public function getComments(array $params, string $adminUuid, bool $withPagination = true): iterable;

    /**
     * Get the total count of resources based on the provided parameters.
     *
     * @param array $params The parameters to filter the query.
     * @param string $adminUuid The admin ID.
     *
     * @return int The total count of resources.
     */
    public function getCommentTotal(array $params, string $adminUuid): int;

    /**
     * Retrieves the latest comments grouped by commenter (user_id) for a given admin.
     *
     * @param string $adminUuid The UUID of the admin.
     * @param int $limit The maximum number of commenters to retrieve. Defaults to 30.
     * @return Collection Returns a collection of comments, each representing the latest comment for a unique commenter (user_id).
     */
    public function getLatestCommentByCommenter(string $adminUuid, int $limit = 30): iterable;

    /**
     * Get the total number of comments for each post ID.
     *
     * @param array $postIds The array of post IDs.
     *
     * @return array An associative array where keys are post IDs and values are comment totals.
     */
    public function getCommentTotalWithPostIds(array $postIds): array;
}
