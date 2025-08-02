<?php

namespace Modules\Post\app\Services;

use http\Exception\InvalidArgumentException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Log;
use Modules\AWS\S3\Services\S3FileService;
use Modules\Core\app\Exceptions\ConflictException;
use Modules\Core\app\Exceptions\FatalErrorException;
use Modules\Core\app\Exceptions\ResourceNotFoundException;
use Modules\Core\app\Services\FileServiceInterface;
use Modules\Media\app\Models\Media;
use Modules\Media\app\Repositories\MediaRepositoryInterface;
use Modules\Post\app\Models\Post;
use Modules\Post\app\Repositories\PostRepositoryInterface;
use Modules\Poster\app\Models\Poster;
use Modules\Poster\app\Repositories\PosterRepositoryInterface;
use Modules\Poster\app\Services\PosterService;

class PostService
{
    protected PostRepositoryInterface $postRepository;
    protected PosterRepositoryInterface $posterRepository;
    protected MediaRepositoryInterface $mediaRepository;
    protected FileServiceInterface $fileService;
    protected PosterService $posterService;

    public function __construct(
        PostRepositoryInterface $postRepository,
        PosterRepositoryInterface $posterRepository,
        MediaRepositoryInterface $mediaRepository,
        FileServiceInterface $fileService,
        PosterService $posterService
    ) {
        $this->postRepository = $postRepository;
        $this->posterRepository = $posterRepository;
        $this->mediaRepository = $mediaRepository;
        $this->fileService = $fileService;
        $this->posterService = $posterService;
    }

    /**
     * Get a collection of posts based on specified parameters.
     *
     * @param  array  $params
     *   An array of parameters to filter and paginate the posts.
     *
     * @return array
     *   An associative array containing the 'data' key with an array of Post objects
     *   retrieved from the post repository, and the 'total' key with the total count
     *   of posts based on the provided parameters.
     */
    public function getPosts(array $params): array
    {
        $posts = $this->postRepository->getPosts($params);

        $this->loadPostersMetadata($posts);

        return [
            'data' => $posts,
            'total' => $this->postRepository->getPostTotal($params),
        ];
    }

    /**
     * Load metadata for posters associated with posts.
     *
     * @param Collection $posts A collection of posts.
     *
     * @throws InvalidArgumentException If an item in $posts is not an instance of Post.
     */
    protected function loadPostersMetadata(Collection &$posts): void
    {
        $posts->each(function ($post) {
            // Check if $post is an instance of Post
            if (!($post instanceof Post)) {
                throw new InvalidArgumentException('Each item in $posts must be an instance of Post');
            }

            // Check if $post->poster is not null and is an instance of Poster
            if ($post->poster && $post->poster instanceof Poster) {
                $this->posterService->setPosterMetadata($post->poster);
            }
        });
    }

    /**
     * Retrieve a specific post by ID for a given admin.
     *
     * @param string $id The ID of the post to retrieve.
     * @param string $adminId The ID of the admin associated with the post.
     * @return Post The retrieved post object.
     * @throws ResourceNotFoundException If no post is found with the specified ID and admin ID combination.
     */
    public function getPost(string $id, string $adminId): Post
    {
        $post = $this->postRepository->getPost($id, $adminId);

        if (!$post) {
            throw new ResourceNotFoundException(Lang::get('post::errors.post_item_not_found', ['id' => $id]));
        }

        $this->loadMediaForPost($post);

        if (isset($post->poster)) {
            $this->loadPosterMetadata($post->poster);
        }

        return $post;
    }

    /**
     * Loads associated media for a given post.
     *
     * This method retrieves media items associated with the specified post from the media repository
     * and sets them as associated media for the given post object.
     *
     * @param Post &$post The post object for which associated media needs to be loaded.
     *
     * @return void
     */
    public function loadMediaForPost(Post &$post): void
    {
        $associatedMedias = $this->mediaRepository->getMedias(['post_id' => $post->id]);
        $post->setMediaTotal($this->mediaRepository->getMediaTotal(['post_id' => $post->id]));

        $images = $this->filterMediasByType($associatedMedias, Media::IMAGE_TYPE);
        $videos = $this->filterMediasByType($associatedMedias, Media::VIDEO_TYPE);

        $post->setImages($this->getImageMediaUrls($images));
        $post->setVideos($this->getVideosWithThumbnails($videos));
    }

    /**
     * Filter media items by type.
     *
     * @param Collection $medias Collection of media items.
     * @param string|null $type Media type to filter (optional).
     *
     * @return Collection Filtered collection of media items.
     */
    protected function filterMediasByType(Collection $medias, ?string $type): Collection
    {
        return $medias->when($type, function ($query, $type) {
            return $query->where('type', $type);
        });
    }

    /**
     * Generate accessible URLs for media files using CloudFront distribution.
     *
     * This method takes a collection of media files and generates accessible URLs
     * for accessing those files via CloudFront distribution. It constructs the URLs
     * by mapping each file path to its corresponding accessible URL using the
     * `generateAccessibleFileUrl` method of the S3FileService class.
     *
     * @param Collection $medias A collection of media files.
     *
     * @return array An array of accessible URLs for the media files via CloudFront distribution.
     */
    protected function getImageMediaUrls(Collection $medias): array
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

    /**
     * Create a new post with optional media attachments.
     *
     * @param array $params The parameters for creating the post.
     *
     * @return Post The created post.
     *
     * @throws ResourceNotFoundException If a resource is not found.
     * @throws ConflictException If there is a conflict with media resource association.
     * @throws FatalErrorException If there is an unexpected error occur.
     */
    public function createPost(array $params, string $adminUuid, int $userId): Post
    {
        $params['admin_uuid'] = $adminUuid;

        // Verify the poster
        $poster = $this->posterRepository->getPosterByUserId($userId, $adminUuid);
        if (!$poster) {
            throw new ResourceNotFoundException(Lang::get('poster::errors.poster_cannot_specified'));
        }

        $params['poster_id'] = $poster->id;

        try {
            $this->postRepository->beginTransaction();

            $post = $this->postRepository->create(new Post(), $params);

            $this->associateMediaWithPost($post->id, $params['media_ids'] ?? []);

            $this->postRepository->commit();
        } catch (ConflictException|ResourceNotFoundException $e) {
            $this->postRepository->rollback();
            throw $e;
        } catch (\Exception $e) {
            $this->postRepository->rollback();
            Log::error('Failed to create a new post.', ['Params: ' => $params, 'Error: ' => $e->getMessage()]);
            throw new FatalErrorException();
        }

        $this->loadMediaForPost($post);

        if (isset($post->poster)) {
            $this->loadPosterMetadata($post->poster);
        }

        return $post;
    }

    /**
     * Update an existing post.
     *
     * @param string $id
     * @param string $adminId
     * @param array $params
     * @return Post
     *
     * @throws ResourceNotFoundException
     * @throws ConflictException
     * @throws FatalErrorException
     */
    public function updatePost(string $id, string $adminId, array $params): Post
    {
        $post = $this->postRepository->getPost($id, $adminId);

        if (!$post) {
            throw new ResourceNotFoundException(Lang::get('post::errors.post_item_not_found', ['id' => $id]));
        }

        try {
            $this->postRepository->beginTransaction();

            // Update the post
            $post = $this->postRepository->update($post, $params);

            // Retrieve associated media
            $associatedMedias = $this->mediaRepository->getMedias(['post_id' => $post->id]);

            // Convert $associatedMedias to a collection
            $associatedMediasCollection = collect([]);
            foreach ($associatedMedias as $media) {
                $associatedMediasCollection->push($media);
            }

            // Extract media IDs from the request parameters
            $mediaIds = $params['media_ids'] ?? [];

            // Update associated media
            $this->updateAssociatedMedia($post->id, $associatedMediasCollection->pluck('id')->toArray(), $mediaIds);

            $this->postRepository->commit();
        } catch (ConflictException|ResourceNotFoundException $e) {
            $this->postRepository->rollback();
            throw $e;
        } catch (\Exception $e) {
            $this->postRepository->rollback();
            Log::error('Failed to update an existing post.', ['Post: ' => $post, 'Params: ' => $params, 'Error: ' => $e->getMessage()]);
            throw new FatalErrorException();
        }

        $this->loadMediaForPost($post);

        if (isset($post->poster)) {
            $this->loadPosterMetadata($post->poster);
        }

        return $post;
    }

    /**
     * Delete a post and its associated media resources.
     *
     * @param string $id      The ID of the post to be deleted.
     * @param string $adminId The ID of the admin performing the deletion.
     *
     * @throws ResourceNotFoundException If the specified post is not found.
     * @throws FatalErrorException      If there is an error during the deletion process.
     */
    public function deletePost(string $id, string $adminId): void
    {
        $post = $this->postRepository->getPost($id, $adminId);

        if (!$post) {
            throw new ResourceNotFoundException(Lang::get('post::errors.post_item_not_found', ['id' => $id]));
        }

        // Delete associated media
        try {
            $associatedMedias = $this->mediaRepository->getMedias(['post_id' => $post->id]);

            $this->postRepository->beginTransaction();

            foreach ($associatedMedias as $media) {
                // Delete a file from s3
                if ($this->fileService->deleteFile(config('app.name') . '/' . $media->path)) {
                    if ($media->thumbnail) {
                        $this->fileService->deleteFile(config('app.name') . '/' . $media->thumbnail);
                    }

                    $this->mediaRepository->forceDelete($media);
                }

                // If deleting the media file from Amazon S3 fails for any reason, the media file will be queued for deletion
                // by the DeleteJunkMediaFileCommand command. Additionally, the corresponding media object will be force deleted.
                $this->mediaRepository->delete($media);
            }

            $this->postRepository->delete($post);

            $this->postRepository->commit();
        } catch (\Exception $e) {
            $this->postRepository->rollback();
            Log::error('Failed to delete an existing post.', ['Post: ' => $post, 'Error: ' => $e->getMessage()]);
            throw new FatalErrorException();
        }
    }

    /**
     * Associate media with a post.
     *
     * @param string $postId
     * @param array $mediaIds
     *
     * @throws ConflictException
     * @throws ResourceNotFoundException
     */
    protected function associateMediaWithPost(string $postId, array $mediaIds): void
    {
        foreach ($mediaIds as $mediaId) {
            $this->associateSingleMediaWithPost($postId, $mediaId);
        }
    }

    /**
     * Update associated media for a post.
     *
     * @param string $postId
     * @param array $associatedMedias
     * @param array $mediaIds
     *
     * @throws ConflictException
     * @throws ModelNotFoundException
     */
    protected function updateAssociatedMedia(string $postId, array $associatedMedias, array $mediaIds): void
    {
        $commonMediaIds = array_intersect($associatedMedias, $mediaIds);

        $removeMediaIds = array_diff($associatedMedias, $commonMediaIds);
        foreach ($removeMediaIds as $removeMediaId) {
            $this->disassociateSingleMediaFromPost($removeMediaId);
        }

        $addMediaIds = array_diff($mediaIds, $commonMediaIds);
        foreach ($addMediaIds as $addMediaId) {
            $this->associateSingleMediaWithPost($postId, $addMediaId);
        }
    }

    /**
     * Associate a single media item with a post.
     *
     * @param string $postId
     * @param string $mediaId
     *
     * @throws ConflictException
     */
    protected function associateSingleMediaWithPost(string $postId, string $mediaId): void
    {
        // Retrieve media item by ID
        $media = $this->getMediaById($mediaId);

        // Check for conflicts and associate media with the post
        $this->associateMedia($media, $postId);

        // Log successful association
        Log::info("Associated media ID {$mediaId} to the post {$postId}.");
    }

    /**
     * Get a media item by ID.
     *
     * @param string $mediaId The ID of the media item.
     *
     * @return Media The retrieved media item.
     *
     * @throws ResourceNotFoundException If a media resource is not found.
     */
    protected function getMediaById(string $mediaId): Media
    {
        $media = $this->mediaRepository->getMedia($mediaId);

        if (!$media) {
            throw new ResourceNotFoundException(Lang::get('media::errors.media_item_not_found', ['id' => $mediaId]));
        }

        return $media;
    }

    /**
     * Associate a media item with a post.
     *
     * This method checks for conflicts by ensuring that the media item is not already associated
     * with another post. If a conflict is detected, a ConflictException is thrown.
     *
     * @param Media $media The media item to be associated with the post.
     * @param string $postId The ID of the post with which the media item should be associated.
     *
     * @throws ConflictException If the media item is already associated with another post.
     *
     * @return void
     */
    protected function associateMedia(Media $media, string $postId): void
    {
        // Check for conflicts
        if ($media->getPostId()) {
            throw new ConflictException(Lang::get('media::errors.media_association_conflict', ['media_id' => $media->id]));
        }

        // Update the media item with the post association
        $this->mediaRepository->update($media, ['post_id' => $postId]);
    }

    /**
     * Disassociate a single media item from a post.
     *
     * @param string $mediaId
     *
     * @throws ModelNotFoundException
     */
    protected function disassociateSingleMediaFromPost(string $mediaId): void
    {
        // Attempt to retrieve media item by ID
        $media = $this->mediaRepository->getMedia($mediaId);

        // Update the media item with the post disassociation
        $this->mediaRepository->update($media, ['post_id' => null]);
    }

    /**
     * Get videos with thumbnails.
     *
     * @param Collection $videos A collection of video media files.
     *
     * @return array An array of videos with thumbnails and source URLs.
     */
    protected function getVideosWithThumbnails(Collection $videos): array
    {
        return $videos->map(function ($media) {
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
    protected function generateThumbnailUrl(string $thumbnailPath): string
    {
        return S3FileService::generateAccessibleFileUrl($thumbnailPath);
    }

    /**
     * Load metadata for the specified poster.
     *
     * @param Poster &$poster The poster object for which to load metadata.
     */
    protected function loadPosterMetadata(Poster &$poster): void
    {
        app()->make(PosterService::class)->setPosterMetadata($poster);
    }
}
