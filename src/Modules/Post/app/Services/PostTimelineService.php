<?php

namespace Modules\Post\app\Services;

use Modules\AWS\S3\Services\S3FileService;
use http\Exception\InvalidArgumentException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Modules\Comment\app\Repositories\CommentRepositoryInterface;
use Modules\Like\app\Repositories\LikeRepositoryInterface;
use Modules\Media\app\Models\Media;
use Modules\Media\app\Repositories\MediaRepositoryInterface;
use Modules\Post\app\Models\Post;
use Modules\Post\app\Repositories\PostSocialRepositoryInterface;
use Modules\Poster\app\Models\Poster;
use Modules\Poster\app\Services\PosterService;
use Modules\Role\app\Entities\Role;

class PostSocialService
{
    private MediaRepositoryInterface $mediaRepository;
    private PostSocialRepositoryInterface $postSocialRepository;
    private PosterService $posterService;
    private CommentRepositoryInterface $commentRepository;
    private LikeRepositoryInterface $likeRepository;

    public function __construct(
        PostSocialRepositoryInterface $postSocialRepository,
        MediaRepositoryInterface $mediaRepository,
        CommentRepositoryInterface $commentRepository,
        LikeRepositoryInterface $likeRepository,
        PosterService $posterService
    ) {
        $this->mediaRepository = $mediaRepository;
        $this->commentRepository = $commentRepository;
        $this->likeRepository = $likeRepository;
        $this->postSocialRepository = $postSocialRepository;
        $this->posterService = $posterService;
    }

    /**
     * Retrieves the social of posts based on search criteria and user role.
     *
     * @param array  $params    An associative array containing search criteria parameters.
     * @param string $adminUuid The UUID of the admin for authorization purposes.
     * @param int    $role      The role of the user accessing the social.
     *
     * @return array An array containing 'data' with posts and 'total' with the total count of posts.
     */
    public function getSocialPosts(array $params, string $adminUuid, int $role, ?int $userId): array
    {
        $params['admin_uuid'] = $adminUuid;

        $mediaLimit = $params['media_limit'] ?? Config::get('post.media_limit_default');

        if (in_array($role, [
            Role::nonRegisteredUser()->getRole(),
            Role::freeMember()->getRole(),
            Role::paidMember()->getRole(),
        ])) {
            $posts = $this->postSocialRepository->getPostsForNonPoster($params);
            $total = $this->postSocialRepository->getPostTotalForNonPoster($params);
        } else {
            $posts = $this->postSocialRepository->getPostsForPoster($params);
            $total = $this->postSocialRepository->getPostTotalForPoster($params);
        }

        $this->loadMetadataForPosts($posts, $userId, $mediaLimit);

        $this->loadPosterMetadata($posts);

        return [
            'data' => $posts,
            'total' => $total,
        ];
    }

    /**
     * Load metadata for each post in the collection.
     *
     * This method associates media items, comment totals, like totals, and like statuses
     * with their respective posts. Optionally, it retrieves the like status of the posts
     * by a specific user identified by their user ID.
     *
     * @param Collection $posts      The collection of posts.
     * @param int|null   $userId The user ID of the user (optional).
     * @param int|null   $mediaLimit The maximum number of media items to retrieve (optional).
     *
     * @return void
     */
    private function loadMetadataForPosts(Collection $posts, ?int $userId, ?int $mediaLimit): void
    {
        // Extract post IDs from the collection
        $postIds = $posts->pluck('id')->toArray();

        // Retrieve media items associated with the post IDs
        $mediaItems = $this->mediaRepository->getMediasWithPostIds($postIds);

        // Retrieve comment totals for each post ID
        $commentStatistics = $this->commentRepository->getCommentTotalWithPostIds($postIds);

        // Retrieve like totals for each post ID
        $likeStatistics = $this->likeRepository->getLikeTotalWithPostIds($postIds);

        // Retrieve like statuses for each post ID by the specified user
        $likeStatuses = [];
        if (!is_null($userId)) {
            $likeStatuses = $this->likeRepository->getLikeStatusWithPostIds($postIds, $userId);
        }

        // Iterate through each post and associate metadata
        $posts->each(function ($post) use (
            $mediaItems,
            $mediaLimit,
            $commentStatistics,
            $likeStatistics,
            $likeStatuses
        ) {
            // Associate media items with the post
            $associatedMedias = $mediaItems[$post->id] ?? collect();
            $post->setMediaTotal($associatedMedias->count());

            // Set comment total for the post
            $commentTotal = $commentStatistics[$post->id] ?? 0;
            $post->setCommentTotal($commentTotal);

            // Set like total for the post
            $likeTotal = $likeStatistics[$post->id] ?? 0;
            $post->setLikeTotal($likeTotal);

            // Set like status for the post if provided
            $isLike = !empty($likeStatuses) ? ($likeStatuses[$post->id] ?? false) : false;
            $post->is_liked = $isLike;

            // Filter and set image and video media URLs for the post
            $imageMedias = $this->filterMediasByType($associatedMedias, Media::IMAGE_TYPE);
            $videoMedias = $this->filterMediasByType($associatedMedias, Media::VIDEO_TYPE);
            $post->setImages($this->getLimitedImageMediaUrls($imageMedias, $mediaLimit));
            $post->setVideos($this->getLimitedVideoMediaUrls($videoMedias, $mediaLimit));
        });
    }

    /**
     * Retrieves limited media URLs from the given media collection based on the specified limit.
     *
     * @param Collection $mediaCollection The collection of media items.
     * @param int|null   $limit The maximum number of media items to retrieve.
     *
     * @return array The array of media URLs.
     */
    private function getLimitedImageMediaUrls(Collection $mediaCollection, ?int $limit): array
    {
        if ($limit && $limit < $mediaCollection->count()) {
            $mediaCollection = $mediaCollection->take($limit);
        }

        return $this->getImageMediaUrls($mediaCollection);
    }

    /**
     * Retrieves limited video media URLs from the given media collection based on the specified limit.
     *
     * @param Collection $mediaCollection The collection of video media items.
     * @param int|null   $limit The maximum number of video media items to retrieve.
     *
     * @return array The array of video media URLs.
     */
    private function getLimitedVideoMediaUrls(Collection $mediaCollection, ?int $limit): array
    {
        if ($limit && $limit < $mediaCollection->count()) {
            $mediaCollection = $mediaCollection->take($limit);
        }

        return $this->getVideosWithThumbnails($mediaCollection);
    }

    /**
     * Filter media items by type.
     *
     * @param Collection $medias Collection of media items.
     * @param string|null $type Media type to filter (optional).
     *
     * @return Collection Filtered collection of media items.
     */
    private function filterMediasByType(Collection $medias, ?string $type): Collection
    {
        return $medias->when($type, function ($query, $type) {
            return $query->where('type', $type);
        });
    }

    /**
     * Retrieve the image media URLs from a collection of media objects.
     *
     * @param Collection $medias The collection of media objects.
     *
     * @return array An array containing the IDs and URLs of the image media.
     *               Each element in the array is an associative array with keys 'id' and 'src'.
     *               - 'id': The ID of the media object.
     *               - 'src': The URL of the image media.
     */
    private function getImageMediaUrls(Collection $medias): array
    {
        return $medias->map(function ($media) {
            if ($media instanceof Media) {
                $mediaPath = $media->path; /* @phpstan-ignore-line */

                $imageUrl = ($mediaPath !== null) ? S3FileService::generateAccessibleFileUrl($mediaPath) : null;

                return [
                    'id' => $media->id,
                    'src' => $imageUrl,
                ];
            }
        })->reject(function ($item) {
            // Remove null values
            return $item == null;
        })->toArray();
    }

    /** Get videos with thumbnails.
     *
     * @param Collection $videoMedias A collection of video media files.
     *
     * @return array An array of videos with thumbnails and source URLs.
     */
    private function getVideosWithThumbnails(Collection $videoMedias): array
    {
        return $videoMedias->map(function ($media) {
            if ($media instanceof Media) {
                $mediaPath = $media->path; /* @phpstan-ignore-line */
                $thumbnail = $media->thumbnail;

                $thumbnailUrl = ($thumbnail !== null) ? $this->generateThumbnailUrl($thumbnail) : null;
                $videoUrl = ($mediaPath !== null) ? S3FileService::generateAccessibleFileUrl($mediaPath) : null;

                return [
                    'id' => $media->id,
                    'thumbnail' => $thumbnailUrl,
                    'src' => $videoUrl,
                ];
            }
        })->reject(function ($item) {
            // Remove null values
            return $item == null;
        })->toArray();
    }

    /**
     * Generate the URL for a video thumbnail.
     *
     * @param string $thumbnailPath The path of the thumbnail image.
     *
     * @return string The URL of the thumbnail image.
     */
    private function generateThumbnailUrl(string $thumbnailPath): string
    {
        return S3FileService::generateAccessibleFileUrl($thumbnailPath);
    }

    /**
     * Load metadata for posters associated with posts.
     *
     * @param Collection $posts A collection of posts.
     *
     * @throws InvalidArgumentException If an item in $posts is not an instance of Post.
     */
    private function loadPosterMetadata(Collection &$posts): void
    {
        // Ensure all items in the collection are instances of Post
        if (!$posts->every(fn ($post) => $post instanceof Post)) {
            throw new InvalidArgumentException('Each item in $posts must be an instance of Post.');
        }

        // Iterate through each post and load poster metadata if applicable
        $posts->each(function (Post $post) {
            $poster = $post->poster;

            if ($poster instanceof Poster) {
                $this->posterService->setPosterMetadata($poster);
            }
        });
    }
}
