<?php

namespace Modules\Media\app\Services;

use Exception;
use Modules\Core\app\Constants\StatusCodeConstant;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\Core\app\Exceptions\ConflictException;
use Modules\Core\app\Exceptions\FatalErrorException;
use Modules\Core\app\Exceptions\ResourceNotFoundException;
use Modules\Core\app\Services\FileServiceInterface;
use Modules\Media\app\Models\Media;
use Modules\Media\app\Repositories\MediaRepositoryInterface;
use Modules\Media\app\Utils\VideoCodecHelper;
use Modules\Post\app\Repositories\PostRepositoryInterface;
use RuntimeException;
use Throwable;

/**
 * Class MediaService.
 *
 * Service for managing media files and interacting with the media repository.
 *
 * Responsibilities:
 * - Handles media upload (image/video), including codec detection and conversion for videos.
 * - Uploads media files to S3, using multipart upload for large files.
 * - Manages media metadata in the database.
 * - Provides methods for CRUD operations and junk file cleanup.
 */
class MediaService
{
    /**
     * @var MediaRepositoryInterface The media repository instance.
     */
    private MediaRepositoryInterface $mediaRepository;

    /**
     * @var FileServiceInterface The file service instance.
     */
    private FileServiceInterface $fileService;
    private PostRepositoryInterface $postRepository;

    /**
     * MediaService constructor.
     *
     * @param FileServiceInterface $fileService The file service to handle file-related operations.
     * @param MediaRepositoryInterface $mediaRepository The media repository for interacting with media data.
     * @param PostRepositoryInterface $postRepository The post repository for interacting with post data.
     */
    public function __construct(
        FileServiceInterface $fileService,
        MediaRepositoryInterface $mediaRepository,
        PostRepositoryInterface $postRepository
    ) {
        $this->fileService = $fileService;
        $this->mediaRepository = $mediaRepository;
        $this->postRepository = $postRepository;
    }

    /**
     * Get media items based on specified parameters.
     *
     * @param array $params Additional parameters for filtering media items.
     * @return array Associative array containing 'data' (media items) and 'total' (total count of media items).
     */
    public function getMedias(array $params): array
    {
        return [
            'data' => $this->mediaRepository->getMedias($params, true),
            'total' => $this->mediaRepository->getMediaTotal($params),
        ];
    }

    /**
     * Retrieves a Media item by its ID.
     *
     * @param string $id The unique identifier of the Media item.
     * @return Media The retrieved Media item.
     * @throws ResourceNotFoundException If no Media item is found with the provided ID.
     */
    public function getMedia(string $id): Media
    {
        $media = $this->mediaRepository->getMedia($id);

        if (!$media) {
            throw new ResourceNotFoundException(Lang::get('media::errors.media_item_not_found', ['id' => $id]));
        }

        return $media;
    }

    /**
     * Updates a Media item by its ID.
     *
     * @param string $id The unique identifier of the Media item.
     * @param array $params The parameters to update in the Media item.
     * @param string $adminId The identifier of the admin performing the update.
     * @return Media The updated Media item.
     * @throws ResourceNotFoundException If no Media item is found with the provided ID.
     * @throws ConflictException If the Media item is associated with a post, preventing the update.
     */
    public function updateMedia(string $id, array $params, string $adminId): Media
    {
        $media = $this->mediaRepository->getMedia($id);

        if (!$media) {
            throw new ResourceNotFoundException(Lang::get('media::errors.media_item_not_found', ['id' => $id]));
        }

        if ($media->getPostId()) {
            throw new ConflictException(Lang::get('media::errors.media_association_conflict', ['media_id' => $media->id]));
        }

        $postId = $params['post_id'];
        $post = $this->postRepository->getPost($postId, $adminId);
        if (!$post) {
            throw new ResourceNotFoundException(Lang::get('post::errors.post_item_not_found', ['id' => $postId]));
        }

        $this->mediaRepository->update($media, $params);

        return $media;
    }

    /**
     * Deletes a Media item by its ID, including associated file and transactional database handling.
     *
     * @param string $id The unique identifier of the Media item.
     * @throws ResourceNotFoundException If no Media item is found with the provided ID.
     * @throws FatalErrorException If an error occurs during the deletion process.
     */
    public function deleteMedia(string $id): void
    {
        $media = $this->mediaRepository->getMedia($id);

        if (!$media) {
            throw new ResourceNotFoundException(Lang::get('media::errors.media_item_not_found', ['id' => $id]));
        }

        $mediaPath = $media->getPath();

        $this->mediaRepository->delete($media);

        // Delete file from s3
        $s3Path = config('app.name') . '/' . config('app.env') . '/' . $mediaPath;
        $this->fileService->deleteFile($s3Path);
    }

    /**
     * Create a new media item based on the provided file.
     *
     * Handles video codec detection and conversion (HEVC to H.264), uploads to S3,
     * and stores metadata in the database.
     *
     * @param UploadedFile $file The uploaded file instance (image or video).
     * @param UploadedFile|null $thumbnail Optional thumbnail image for video.
     * @return Media The created media item.
     * @throws RuntimeException|Throwable If a file is not accessible or conversion fails.
     */
    public function createMedia(UploadedFile $file, ?UploadedFile $thumbnail = null): Media
    {
        $mediaType = $this->detectMediaType($file);
        Log::debug('Detected media type: ' . $mediaType);
        $s3Path = $this->buildS3Path($file, $mediaType);
        $params = [
            'type' => $mediaType,
            'path' => $s3Path,
        ];

        if ($mediaType === Media::VIDEO_TYPE) {
            $file = $this->handleVideoDetectionAndConversion($file);
        }

        $this->uploadMediaFile($file, $s3Path);

        if ($mediaType === Media::VIDEO_TYPE && $thumbnail) {
            $s3ThumbnailPath = $this->buildS3Path($thumbnail, 'thumbnail');
            $this->uploadThumbnailFile($thumbnail, $s3ThumbnailPath);
            $params['thumbnail'] = $s3ThumbnailPath;
        }

        return $this->storeMediaRecord($params);
    }

    /**
     * Detects video codec and converts to H.264 if necessary.
     *
     * @param UploadedFile $file
     * @return UploadedFile The original or converted file ready for upload.
     * @throws RuntimeException|Throwable
     */
    private function handleVideoDetectionAndConversion(UploadedFile $file): UploadedFile
    {
        $filePath = $file->getPathname();
        Log::info('Video upload: file path is ' . $filePath);
        if (!file_exists($filePath)) {
            Log::error('Uploaded video file does not exist: ' . $filePath);
            throw new RuntimeException('Uploaded video file does not exist: ' . $filePath);
        }
        $codec = VideoCodecHelper::getVideoCodec($filePath);
        Log::info('Detected video codec: ' . var_export($codec, true) . ' for file: ' . $filePath);
        $codec = strtolower($codec ?? '');
        $isHevc = in_array($codec, ['hevc', 'h265'], true);
        if (!$isHevc) {
            Log::info('Video is not HEVC/H.265, no conversion needed.');

            return $file;
        }
        $convertedPath = null;
        try {
            Log::info('HEVC codec detected, converting to H.264 for file: ' . $file->getClientOriginalName());
            $convertedPath = VideoCodecHelper::convertToH264($filePath);
            Log::info('Conversion complete. Converted file path: ' . $convertedPath);
            if (!file_exists($convertedPath)) {
                Log::error('Converted file does not exist after conversion: ' . $convertedPath);
                throw new RuntimeException('Converted file does not exist after conversion: ' . $convertedPath);
            }

            return new UploadedFile(
                $convertedPath,
                pathinfo($convertedPath, PATHINFO_BASENAME),
                'video/mp4',
                null,
                true
            );
        } catch (Throwable $e) {
            Log::error('Error during HEVC to H.264 conversion: ' . $e->getMessage(), ['exception' => $e]);
            if ($convertedPath && file_exists($convertedPath)) {
                @unlink($convertedPath);
                Log::info('Temporary converted file cleaned up after error: ' . $convertedPath);
            }
            throw $e;
        }
    }

    /**
     * Uploads the main media file to S3.
     *
     * @param UploadedFile $file
     * @param string $s3Path
     * @return void
     */
    private function uploadMediaFile(UploadedFile $file, string $s3Path): void
    {
        $this->fileService->uploadFile($file, $s3Path);
    }

    /**
     * Uploads the thumbnail file to S3.
     *
     * @param UploadedFile $thumbnail
     * @param string $s3ThumbnailPath
     * @return void
     */
    private function uploadThumbnailFile(UploadedFile $thumbnail, string $s3ThumbnailPath): void
    {
        $this->fileService->uploadFile($thumbnail, $s3ThumbnailPath);
    }

    /**
     * Stores the media record in the database.
     *
     * @param array $params
     * @return Media
     */
    private function storeMediaRecord(array $params): Media
    {
        return $this->mediaRepository->create(new Media(), $params);
    }

    /**
     * Detect the media type based on the file MIME type.
     *
     * @param UploadedFile $file
     * @return string 'video' or 'image'
     */
    private function detectMediaType(UploadedFile $file): string
    {
        $mimeType = $file->getMimeType();

        return str_starts_with($mimeType, Media::VIDEO_TYPE) ? Media::VIDEO_TYPE : Media::IMAGE_TYPE;
    }

    /**
     * Build the S3 path based on the media type.
     *
     * @param UploadedFile $file
     * @param string $mediaType
     * @return string S3 path for the file
     */
    private function buildS3Path(UploadedFile $file, string $mediaType): string
    {
        $mediaDirectory = Config::get('media.s3_media_directory', 'medias');
        $subDirectory = Config::get('media.s3_' . $mediaType . '_directory', $mediaType . 's');

        return $mediaDirectory . '/' . $subDirectory . '/' . $this->generateS3FileName($file);
    }

    /**
     * Generate a new file name based on UUID and current timestamp.
     *
     * @param UploadedFile $file
     * @return string Generated file name
     */
    private function generateS3FileName(UploadedFile $file): string
    {
        return Str::uuid() . '_' . time() . '.' . $file->getClientOriginalExtension();
    }

    /**
     * Delete junk media files from the S3 bucket and physically from the dataset.
     *
     * Junk media files include soft-deleted media items and media items without a post ID
     * that were created more than a day ago.
     *
     * @throws Exception|FatalErrorException If an error occurs during the deletion process.
     */
    public function deleteJunkFiles(): void
    {
        try {
            // Retrieve junk media items.
            $junkMedias = $this->mediaRepository->getJunkMedias();

            // Check if there are any junk media items to delete.
            if ($junkMedias->isEmpty()) {
                return;
            }

            foreach ($junkMedias as $media) {
                // Construct the S3 path for the media file.
                $s3Path = config('app.name') . '/' . config('app.env') . '/' . $media->path;

                // Check if the file exists in the S3 bucket.
                if ($this->fileService->fileExists($s3Path)) {
                    // Delete the media file from the S3 bucket.
                    if (!$this->fileService->deleteFile($s3Path)) {
                        continue;
                    }
                }
                // Physically delete the media item from the database.
                $this->mediaRepository->forceDelete($media);
                Log::info('Succeed to delete the media file ' . $s3Path);
            }
        } catch (Exception $exception) {
            // Handle any exceptions that may occur during the deletion process.
            $errorMessage = "Failed in deleting junk media files. Error content: {$exception->getMessage()}";
            Log::error($errorMessage);
            throw new FatalErrorException(StatusCodeConstant::EXTERNAL_API_FAILED_CODE, $errorMessage);
        }
    }
}
