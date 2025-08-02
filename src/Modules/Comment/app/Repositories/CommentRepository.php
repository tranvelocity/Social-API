<?php

namespace Modules\Comment\app\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Comment\app\Models\Comment;
use Modules\Core\app\Constants\ResourceConstant;
use Modules\Core\app\Repositories\Repository;

class CommentRepository extends Repository implements CommentRepositoryInterface
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
    public function getComment(string $id, string $adminUuid, ?string $postId = null): ?Comment
    {
        return Comment::select('comments.*')
            ->join('posts', 'posts.id', '=', 'comments.post_id')
            ->where('posts.admin_uuid', $adminUuid)
            ->where('comments.id', $id)
            ->whereNull('posts.deleted_at')
            ->whereNull('comments.deleted_at')
            ->when($postId, function ($query) use ($postId) {
                $query->where('post_id', $postId);
            })
            ->first();
    }

    /**
     * Get the base query for retrieving comments based on provided parameters.
     *
     * @param array $params The parameters to filter the query.
     * @param string $adminUuid The admin ID associated with the comments.
     *
     * @return Builder The base query builder.
     */
    private function getQuery(array $params, string $adminUuid): Builder
    {
        $query = Comment::select('comments.*')
            ->join('posts', 'posts.id', '=', 'comments.post_id')
            ->where('posts.admin_uuid', $adminUuid)
            ->whereNull('posts.deleted_at')
            ->whereNull('comments.deleted_at')
            ->when(isset($params['is_hidden']), function ($query) use ($params) {
                $query->where('comments.is_hidden', $params['is_hidden']);
            })
            ->when(isset($params['post_id']), function ($query) use ($params) {
                $query->where('comments.post_id', $params['post_id']);
            })
            ->when(isset($params['user_id']), function ($query) use ($params) {
                $query->where('comments.user_id', $params['user_id']);
            })
            ->when(isset($params['comment']), function ($query) use ($params) {
                $query->where('comments.comment', 'LIKE', $params['comment'] . '%');
            })
            ->when(!empty($params), function ($query) use ($params) {
                $this->applyDateTimeFilterIfExists($query, 'comments.created_at', '>=', $params, 'comment_creation_from');
                $this->applyDateTimeFilterIfExists($query, 'comments.created_at', '<=', $params, 'comment_creation_to');
            });

        if (isset($params['last_id'])) {
            $query->where('comments.id', '>', $params['last_id']);
        }

        return $query;
    }

    /**
     * Retrieve comments based on the provided parameters.
     *
     * @param array $params The parameters to filter the query.
     * @param string $adminUuid The admin ID associated with the comments.
     * @param bool $withPagination
     * @return iterable The collection of retrieved comments.
     */
    public function getComments(array $params, string $adminUuid, bool $withPagination = true): iterable
    {
        $query = $this->getQuery($params, $adminUuid)->orderBy('created_at', 'DESC');

        if ($withPagination) {
            $offset = $params['offset'] ?? ResourceConstant::OFFSET_DEFAULT;
            $limit = $params['limit'] ?? ResourceConstant::LIMIT_DEFAULT;

            $query->skip($offset)->limit($limit);
        }

        return $query->get();
    }

    /**
     * Get the total count of resources based on the provided parameters.
     *
     * @param array $params The parameters to filter the query.
     * @param string $adminUuid The admin ID.
     *
     * @return int The total count of resources.
     */
    public function getCommentTotal(array $params, string $adminUuid): int
    {
        return $this->getQuery($params, $adminUuid)->count();
    }

    /**
     * Retrieves the latest comments grouped by commenter (user_id) for a given admin.
     *
     * @param string $adminUuid The UUID of the admin.
     * @param int $limit The maximum number of commenters to retrieve. Defaults to 30.
     * @return Collection Returns a collection of comments, each representing the latest comment for a unique commenter (user_id).
     */
    public function getLatestCommentByCommenter(string $adminUuid, int $limit = 30): iterable
    {
        return Comment::select('comments.user_id', DB::raw('MAX(comments.created_at) as latest_created_at'))
            ->join('posts', 'posts.id', '=', 'comments.post_id')
            ->where('posts.admin_uuid', $adminUuid)
            ->whereNull('posts.deleted_at')
            ->whereNull('comments.deleted_at')
            ->groupBy('comments.user_id')
            ->orderByDesc('latest_created_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Get the total number of comments for each post ID.
     *
     * @param array $postIds The array of post IDs.
     *
     * @return array An associative array where keys are post IDs and values are comment totals.
     */
    public function getCommentTotalWithPostIds(array $postIds): array
    {
        return Comment::select('post_id', DB::raw('count(*) as total_comments'))
            ->whereIn('post_id', $postIds)
            ->groupBy('post_id')
            ->get()
            ->pluck('total_comments', 'post_id')
            ->toArray();
    }
}
