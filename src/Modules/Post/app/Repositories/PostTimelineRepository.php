<?php

namespace Modules\Post\app\Repositories;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Modules\Core\app\Repositories\Repository;
use Modules\Post\app\Models\Post;

/**
 * Class PostSocialRepository.
 *
 * This class provides methods for retrieving posts based on specified parameters and user roles for the post social.
 */
class PostSocialRepository extends Repository implements PostSocialRepositoryInterface
{
    /**
     * Retrieves a base query builder instance for fetching Post entities based on specified parameters.
     *
     * @param array $params An associative array containing parameters for filtering the query.
     *
     * @return Builder The base query builder instance for Post entities.
     */
    private function getBaseQuery(array $params): Builder
    {
        $result = Post::select('posts.*')
            ->where('posts.admin_uuid', $params['admin_uuid'])
            ->whereNull('posts.deleted_at')
            ->join('posters', 'posts.poster_id', '=', 'posters.id')
            ->whereNull('posters.deleted_at');

        $this->applyFilterIfExists($result, 'is_published', '=', $params);
        $this->applyFilterIfExists($result, 'type', '=', $params);
        $this->applyFilterIfExists($result, 'poster_id', '=', $params);

        if (isset($params['last_id'])) {
            $result->where('id', '>', $params['last_id']);
        }

        return $result;
    }

    /**
     * Retrieves a query builder instance for fetching Post entities for a poster based on specified parameters.
     *
     * @param array $params An associative array containing parameters for filtering the query.
     *
     * @return Builder The query builder instance for Post entities for a poster.
     */
    private function getQueryForPoster(array $params): Builder
    {
        $query = $this->getBaseQuery($params);

        return $query->when(!empty($params), function ($query) use ($params) {
            $this->applyDateTimeFilterIfExists($query, 'published_start_at', '>=', $params, 'published_start_at_from');
            $this->applyDateTimeFilterIfExists($query, 'published_start_at', '<=', $params, 'published_start_at_until');
            $this->applyDateTimeFilterIfExists($query, 'published_end_at', '>=', $params, 'published_end_at_from');
            $this->applyDateTimeFilterIfExists($query, 'published_end_at', '<=', $params, 'published_end_at_until');
        });
    }

    /**
     * Retrieves a query builder instance for fetching Post entities for a non-poster based on specified parameters.
     *
     * @param array $params An associative array containing parameters for filtering the query.
     *
     * @return Builder The query builder instance for Post entities for a non-poster.
     */
    private function getQueryForNonPoster(array $params): Builder
    {
        $query = $this->getBaseQuery($params);

        $now = Carbon::now()->toDateTimeString();

        return $query->where(function ($query) use ($now) {
            $query->whereNull('published_start_at')->orWhere('published_start_at', '<=', $now);
        })->where(function ($query) use ($now) {
            $query->whereNull('published_end_at')->orWhere('published_end_at', '>=', $now);
        });
    }

    /**
     * Retrieve all posts that correspond to the specified parameters for a poster.
     *
     * @param array $params An associative array containing parameters for filtering the query.
     *
     * @return Collection An iterable collection of Post entities for a poster.
     */
    public function getPostsForPoster(array $params): iterable
    {
        $offset = $params['offset'];
        $limit = $params['limit'];
        unset($params['offset']);
        unset($params['limit']);

        return $this->getQueryForPoster($params)
            ->orderBy('created_at', 'DESC')
            ->skip($offset)
            ->limit($limit)
            ->get();
    }

    /**
     * Retrieve all posts that correspond to the specified parameters for a non-poster.
     *
     * @param array $params An associative array containing parameters for filtering the query.
     *
     * @return Collection An iterable collection of Post entities for a non-poster.
     */
    public function getPostsForNonPoster(array $params): iterable
    {
        $offset = $params['offset'];
        $limit = $params['limit'];
        unset($params['offset']);
        unset($params['limit']);

        return $this->getQueryForNonPoster($params)
            ->orderBy('created_at', 'DESC')
            ->skip($offset)
            ->limit($limit)
            ->get();
    }

    /**
     * Retrieves the total number of Post entities based on specified parameters for a poster.
     *
     * @param array $params An associative array containing parameters for filtering the query.
     *
     * @return int The total number of Post entities matching the given parameters for a poster.
     */
    public function getPostTotalForPoster(array $params): int
    {
        return $this->getQueryForPoster($params)->count();
    }

    /**
     * Retrieves the total number of Post entities based on specified parameters for a non-poster.
     *
     * @param array $params An associative array containing parameters for filtering the query.
     *
     * @return int The total number of Post entities matching the given parameters for a non-poster.
     */
    public function getPostTotalForNonPoster(array $params): int
    {
        return $this->getQueryForNonPoster($params)->count();
    }
}
