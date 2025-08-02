<?php

namespace Modules\Post\app\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Modules\Core\app\Repositories\Repository;
use Modules\Post\app\Models\Post;

class PostRepository extends Repository implements PostRepositoryInterface
{
    /**
     * Retrieves a query builder instance for fetching Post entities based on specified parameters.
     *
     * @param array $params An associative array containing parameters for filtering the query.
     *
     * @return Builder The query builder instance for Post entities.
     */
    private function getQuery(array $params): Builder
    {
        $result = Post::query()
            ->where('admin_uuid', '=', $params['admin_uuid'])
            ->when(!empty($params), function ($query) use ($params) {
                $this->applyFilterIfExists($query, 'is_published', '=', $params);
                $this->applyFilterIfExists($query, 'type', '=', $params);
                $this->applyFilterIfExists($query, 'poster_id', '=', $params);
                $this->applyDateTimeFilterIfExists($query, 'published_start_at', '>=', $params, 'published_start_at_from');
                $this->applyDateTimeFilterIfExists($query, 'published_start_at', '<=', $params, 'published_start_at_until');
                $this->applyDateTimeFilterIfExists($query, 'published_end_at', '>=', $params, 'published_end_at_from');
                $this->applyDateTimeFilterIfExists($query, 'published_end_at', '<=', $params, 'published_end_at_until');
            });

        if (isset($params['last_id'])) {
            $result->where('id', '>', $params['last_id']);
        }

        return $result;
    }

    /**
     * Retrieve a resource by id.
     *
     * @param string $id
     * @return null|Post
     */
    public function getPost(string $id, string $adminId): ?Post
    {
        return Post::query()
            ->where('admin_uuid', '=', $adminId)
            ->where('id', '=', $id)
            ->first();
    }

    /**
     * Retrieve all resources that correspond to the condition.
     *
     * @param array $params
     * @return iterable
     */
    public function getPosts(array $params): iterable
    {
        $offset = $params['offset'];
        $limit = $params['limit'];
        unset($params['offset']);
        unset($params['limit']);

        return $this->getQuery($params)
            ->orderBy('created_at', 'DESC')
            ->skip($offset)
            ->limit($limit)
            ->get();
    }

    /**
     * Retrieves the total number of Post entities based on specified parameters.
     *
     * @param array $params An associative array containing parameters for filtering the query.
     *
     * @return int The total number of Post entities matching the given parameters.
     */
    public function getPostTotal(array $params): int
    {
        return $this->getQuery($params)->count();
    }
}
