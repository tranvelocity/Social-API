<?php

namespace Modules\Post\app\Repositories;

use Modules\Core\app\Repositories\RepositoryInterface;
use Modules\Post\app\Models\Post;

interface PostRepositoryInterface extends RepositoryInterface
{
    /**
     * Retrieve a resource by id.
     *
     * @param string $id
     * @param string $adminId
     * @return null|Post
     */
    public function getPost(string $id, string $adminId): ?Post;

    /**
     * Retrieve all resources that correspond to the condition.
     *
     * @param array $params
     * @return iterable
     */
    public function getPosts(array $params): iterable;

    /**
     * Retrieves the total number of Post entities based on specified parameters.
     *
     * @param array $params An associative array containing parameters for filtering the query.
     *
     * @return int The total number of Post entities matching the given parameters.
     */
    public function getPostTotal(array $params): int;
}
