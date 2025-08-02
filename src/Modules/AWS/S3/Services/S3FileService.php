<?php

declare(strict_types=1);

namespace Modules\AWS\S3\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Intervention\Image\ImageManagerStatic as Image;
use Modules\AWS\S3\Adapters\S3FileAdapter;
use Modules\AWS\S3\Adapters\S3MultipartUploader;
use Modules\Core\app\Exceptions\FatalErrorException;
use Modules\Core\app\Services\FileServiceInterface;

class S3FileService implements FileServiceInterface
{
    private S3FileAdapter $s3FileAdapter;
    private S3MultipartUploader $s3MultipartUploader;

    /**
     * S3FileService constructor.
     *
     * @param S3MultipartUploader $s3MultipartUploader The uploader for large files using multipart upload.
     * @param S3FileAdapter $s3FileAdapter The adapter for regular S3 file operations.
     */
    public function __construct(
        S3MultipartUploader $s3MultipartUploader,
        S3FileAdapter $s3FileAdapter
    ) {
        $this->s3FileAdapter = $s3FileAdapter;
        $this->s3MultipartUploader = $s3MultipartUploader;
    }

    /**
     * Uploads a file to Amazon S3, removing EXIF data if the file is an image.
     * Uses multipart upload for large files. Deletes the temporary file used for processing.
     *
     * @param UploadedFile $file The file to be uploaded.
     * @param string $s3Path The desired path in the S3 bucket.
     *
     * @return string|null The S3 URL of the uploaded file on success, or null on failure.
     *
     * @throws FatalErrorException If there is an error during the EXIF data removal process.
     */
    public function uploadFile(UploadedFile $file, string $s3Path): ?string
    {
        // Build the full S3 path including the app name and environment
        $s3Path = Config::get('app.name') . '/' . $s3Path;

        // Ensure the S3 path does not start with a slash
        $s3Path = ltrim($s3Path, '/');

        // Log the S3 path being used for the upload
        Log::info('[S3FileService] Uploading file to S3 path: ' . $s3Path);
        // Validate the file is an instance of UploadedFile
        if (!$file instanceof UploadedFile) {
            Log::error('[S3FileService] The provided file is not an instance of UploadedFile.');
            throw new \InvalidArgumentException('The provided file is not a valid UploadedFile instance.');
        }

        // Initialize a variable to hold the temporary file path, if created
        $tempFile = null;

        // Check the file size to determine if multipart upload is needed
        $fileSize = $file->getSize();

        // Remove EXIF data only if the file is an image
        if (str_starts_with($file->getMimeType(), 'image/')) {
            try {
                // Create an image instance from the uploaded file
                $image = Image::make($file->getPathname())->orientate();

                // Encode the image to remove EXIF data
                $image->encode($file->getClientOriginalExtension());

                // Create a temporary file to store the processed image
                $tempFile = tempnam(sys_get_temp_dir(), 'image') . '.' . $file->getClientOriginalExtension();
                $image->save($tempFile);

                // Replace the original file with the processed one
                $file = new UploadedFile(
                    $tempFile,
                    $file->getClientOriginalName(),
                    $file->getClientMimeType(),
                    null,
                    true // Indicate that this file is already "moved"
                );
            } catch (\Exception $e) {
                // Log and throw an exception if the image processing fails
                $errorMessage = '[EXIF data removing process] Failed in creating a temporary image: ' . $e->getMessage();
                Log::error($errorMessage);
                throw new FatalErrorException($errorMessage);
            }
        }

        // Perform the upload
        if ($fileSize > Config::get('aws.s3.min_multipart_upload_size')) {
            // Use multipart upload for large files
            $s3Url = $this->s3MultipartUploader->multipartUpload($file, $s3Path);
        } else {
            // For smaller files, use standard upload
            $s3Url = $this->s3FileAdapter->uploadFile($file, $s3Path);
        }

        // If a temporary file was created, delete it
        if ($tempFile && file_exists($tempFile)) {
            unlink($tempFile);
        }

        return $s3Url;
    }

    /**
     * Deletes a file from Amazon S3.
     *
     * @param string $filePath The path of the file in the S3 bucket.
     *
     * @return bool True if the file was successfully deleted, false otherwise.
     */
    public function deleteFile(string $filePath): bool
    {
        return $this->s3FileAdapter->deleteFile($filePath);
    }

    /**
     * Checks if a file exists at the specified location in the Amazon S3.
     *
     * This method checks the existence of a file located at the given path within the S3 filesystem.
     *
     * @param string $s3Path The path to the file in the Amazon S3.
     *
     * @return bool Returns true if the file exists at the specified path in the Amazon S3., otherwise returns false.
     */
    public function fileExists(string $s3Path): bool
    {
        return $this->s3FileAdapter->fileExists($s3Path);
    }

    /**
     * Generates an accessible URL for a file using CloudFront domain.
     *
     * This method generates a URL for accessing a file via CloudFront distribution. It constructs
     * the URL by appending the CloudFront domain name, application name, environment, and the
     * specified file path.
     *
     * @param string $filePath The file path relative to the application.
     *
     * @return string The accessible URL for the file via CloudFront distribution.
     */
    public static function generateAccessibleFileUrl(string $filePath): string
    {
        $cloudFrontUrl = config("aws.cloudfront.url");

        if (empty($cloudFrontUrl)) {
            throw new \RuntimeException('CloudFront domain is not configured.');
        }

        return $cloudFrontUrl . '/' . Config::get('app.name') . '/' . $filePath;
    }
}
