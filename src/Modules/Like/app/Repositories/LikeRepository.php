<?php

namespace Modules\Like\app\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Modules\Core\app\Constants\ResourceConstant;
use Modules\Core\app\Repositories\Repository;
use Modules\Like\app\Models\Like;

class LikeRepository extends Repository implements LikeRepositoryInterface
{
    /**
     * Get the base query builder for retrieving likes based on specified parameters and admin ID.
     *
     * @param array $params An array of parameters for filtering the likes.
     * @param string $adminId The admin UUID for identifying the posts.
     * @return Builder The base query builder for likes.
     */
    private function getQuery(array $params, string $adminId): Builder
    {
        return Like::select('likes.*')
            ->join('posts', 'posts.id', '=', 'likes.post_id')
            ->where('posts.admin_uuid', $adminId)
            ->whereNull('posts.deleted_at')
            ->when(isset($params['post_id']), function ($query) use ($params) {
                $query->where('likes.post_id', $params['post_id']);
            })
            ->when(isset($params['user_id']), function ($query) use ($params) {
                $query->where('likes.user_id', $params['user_id']);
            });
    }

    /**
     * Get a collection of likes based on specified parameters and admin ID.
     *
     * @param array $params An array of parameters for filtering the likes.
     * @param string $adminId The admin UUID for identifying the posts.
     * @param bool $allResults Flag to determine if all results should be retrieved.
     * @return iterable An iterable collection of likes.
     */
    public function getLikes(array $params, string $adminId, bool $allResults = true): iterable
    {
        $query = $this->getQuery($params, $adminId)->orderBy('created_at', 'DESC');

        if (!$allResults) {
            $offset = $params['offset'] ?? ResourceConstant::OFFSET_DEFAULT;
            $limit = $params['limit'] ?? ResourceConstant::LIMIT_DEFAULT;

            $query->skip($offset)->limit($limit);
        }

        return $query->get();
    }

    /**
     * Get the total count of likes based on specified parameters and admin ID.
     *
     * @param array $params An array of parameters for filtering the likes.
     * @param string $adminId The admin UUID for identifying the posts.
     * @return int The total count of likes.
     */
    public function getLikeTotal(array $params, string $adminId): int
    {
        return $this->getQuery($params, $adminId)->count();
    }

    /**
     * Get the total number of comments for each post ID.
     *
     * @param array $postIds The array of post IDs.
     *
     * @return array An associative array where keys are post IDs and values are comment totals.
     */
    public function getLikeTotalWithPostIds(array $postIds): array
    {
        return Like::select('post_id', DB::raw('count(*) as total_likes'))
            ->whereIn('post_id', $postIds)
            ->groupBy('post_id')
            ->get()
            ->pluck('total_likes', 'post_id')
            ->toArray();
    }

    /**
     * Get the like status for each post ID by the user with the specified user ID.
     *
     * @param array $postIds   The array of post IDs.
     * @param int   $userId The user ID of the user.
     *
     * @return array An associative array where keys are post IDs and values represent whether
     *               the user has liked the corresponding post (true/false).
     */
    public function getLikeStatusWithPostIds(array $postIds, int $userId): array
    {
        // Initialize the result array
        $likeResults = [];

        // Query the likes table to check if the user has liked each post
        $likes = Like::whereIn('post_id', $postIds)
            ->where('user_id', $userId)
            ->pluck('post_id');

        // Iterate through each post ID and set the like status in the result array
        foreach ($postIds as $postId) {
            // Check if the user has liked the current post
            $liked = $likes->contains($postId);

            // Set the like status in the result array
            $likeResults[$postId] = $liked;
        }

        return $likeResults;
    }
}
