<?php

namespace Modules\Post\app\Repositories;

use Illuminate\Support\Collection;
use Modules\Core\app\Repositories\RepositoryInterface;

interface PostSocialRepositoryInterface extends RepositoryInterface
{
    /**
     * Retrieve all posts that correspond to the specified parameters for a poster.
     *
     * @param array $params An associative array containing parameters for filtering the query.
     *
     * @return Collection An iterable collection of Post entities for a poster.
     */
    public function getPostsForPoster(array $params): iterable;

    /**
     * Retrieve all posts that correspond to the specified parameters for a non-poster.
     *
     * @param array $params An associative array containing parameters for filtering the query.
     *
     * @return Collection An iterable collection of Post entities for a non-poster.
     */
    public function getPostsForNonPoster(array $params): iterable;

    /**
     * Retrieves the total number of Post entities based on specified parameters for a poster.
     *
     * @param array $params An associative array containing parameters for filtering the query.
     *
     * @return int The total number of Post entities matching the given parameters for a poster.
     */
    public function getPostTotalForPoster(array $params): int;

    /**
     * Retrieves the total number of Post entities based on specified parameters for a non-poster.
     *
     * @param array $params An associative array containing parameters for filtering the query.
     *
     * @return int The total number of Post entities matching the given parameters for a non-poster.
     */
    public function getPostTotalForNonPoster(array $params): int;
}
