<?php

namespace Modules\Media\app\Repositories;

use Exception;
use Illuminate\Support\Collection;
use Modules\Core\app\Repositories\RepositoryInterface;
use Modules\Media\app\Models\Media;

interface MediaRepositoryInterface extends RepositoryInterface
{
    /**
     * Retrieve a Media resource by its unique identifier.
     *
     * @param string $id The unique identifier of the Media resource.
     *
     * @return Media|null The retrieved Media resource or null if not found.
     */
    public function getMedia(string $id): ?Media;

    /**
     * Retrieve Media resources based on the provided parameters.
     *
     * @param array $params The parameters to filter the query.
     * @param bool $withPagination Whether to retrieve all results or paginate.
     *
     * @return iterable The collection of retrieved Media resources.
     */
    public function getMedias(array $params, bool $withPagination = false): iterable;

    /**
     * Get the total count of Media resources based on the provided parameters.
     *
     * @param array $params The parameters to filter the query.
     *
     * @return int The total count of Media resources.
     */
    public function getMediaTotal(array $params): int;

    /**
     * Retrieve a collection of junk media items.
     *
     * Junk media items include:
     * - Soft-deleted media items.
     * - Media items without a post ID that were created more than a day ago.
     *
     * @return Collection A collection of junk media items.
     * @throws Exception If an error occurs during the retrieval process.
     */
    public function getJunkMedias(): iterable;

    /**
     * Get media items grouped by post IDs.
     *
     * Retrieves media items associated with the given post IDs and groups them by post ID.
     *
     * @param array $postIds An array of post IDs.
     *
     * @return Collection An associative array where keys are post IDs
     *                                         and values are collections of media items associated with each post.
     */
    public function getMediasWithPostIds(array $postIds): iterable;
}
