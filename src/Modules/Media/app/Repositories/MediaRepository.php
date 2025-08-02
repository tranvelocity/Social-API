<?php

namespace Modules\Media\app\Repositories;

use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Modules\Core\app\Constants\ResourceConstant;
use Modules\Core\app\Repositories\Repository;
use Modules\Media\app\Models\Media;

/**
 * Class MediaRepository.
 *
 * This class handles the database operations for the Media model.
 */
class MediaRepository extends Repository implements MediaRepositoryInterface
{
    /**
     * Retrieve a Media resource by its unique identifier.
     *
     * @param string $id The unique identifier of the Media resource.
     *
     * @return Media|null The retrieved Media resource or null if not found.
     */
    public function getMedia(string $id): ?Media
    {
        return Media::query()
            ->where('id', '=', $id)
            ->first();
    }

    /**
     * Get the base query for retrieving Media resources based on provided parameters.
     *
     * @param array $params The parameters to filter the query.
     *
     * @return Builder The base query builder.
     */
    private function getQuery(array $params): Builder
    {
        $query = Media::query();

        if (!empty($params)) {
            $this->applyFilterIfExists($query, 'post_id', '=', $params);
            $this->applyFilterIfExists($query, 'type', '=', $params);
        }

        return $query;
    }

    /**
     * Retrieve Media resources based on the provided parameters.
     *
     * @param array $params The parameters to filter the query.
     * @param bool $withPagination Whether to retrieve all results or paginate.
     *
     * @return iterable The collection of retrieved Media resources.
     */
    public function getMedias(array $params, bool $withPagination = false): iterable
    {
        $query = $this->getQuery($params)->orderBy('created_at', 'DESC');

        if ($withPagination) {
            $offset = $params['offset'] ?? ResourceConstant::OFFSET_DEFAULT;
            $limit = $params['limit'] ?? ResourceConstant::LIMIT_DEFAULT;

            $query->skip($offset)->limit($limit);
        }

        return $query->get();
    }

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
    public function getMediasWithPostIds(array $postIds): iterable
    {
        return $this->getQuery([])
            ->whereIn('post_id', $postIds)
            ->get()
            ->groupBy('post_id');
    }

    /**
     * Get the total count of Media resources based on the provided parameters.
     *
     * @param array $params The parameters to filter the query.
     *
     * @return int The total count of Media resources.
     */
    public function getMediaTotal(array $params): int
    {
        return $this->getQuery($params)->count();
    }

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
    public function getJunkMedias(): iterable
    {
        $trashedMedias = Media::onlyTrashed()->get();

        $junkMedias = Media::query()
            ->whereNull('post_id')
            ->whereDate('created_at', '<=', now()->subDay())
            ->get();

        return $junkMedias->merge($trashedMedias);
    }
}
