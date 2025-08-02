<?php

declare(strict_types=1);

namespace Modules\AWS\S3\Adapters;

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Modules\Core\app\Constants\StatusCodeConstant;
use Modules\Core\app\Exceptions\FatalErrorException;
use Modules\Notification\Traits\NotificationTrait;

class S3FileAdapter
{
    use NotificationTrait;
    private FilesystemAdapter $filesystem;

    /**
     * S3FileAdapter constructor.
     *
     * @param string|null $disk
     */
    public function __construct(?string $disk = null)
    {
        if (!$disk) {
            $disk = Config::get('aws.s3');
        }

        $this->filesystem = Storage::build($disk);
    }

    /**
     * Upload a file to S3.
     *
     * @param UploadedFile $file
     * @param string $s3Path
     * @return string
     */
    public function uploadFile(UploadedFile $file, string $s3Path): string
    {
        try {
            $fileName = pathinfo($s3Path, PATHINFO_FILENAME);
            $extension = $file->getClientOriginalExtension();

            $response = $this->filesystem->putFileAs(
                dirname($s3Path),
                $file,
                $fileName . '.' . $extension,
                [
                    'CacheControl' => 'max-age=864000',
                    'ContentType' => $file->getMimeType()
                ]
            );

            if (!$response) {
                // Log an error if the upload response is not as expected
                $errorMessage = "Failed to upload file. The S3 file path is $s3Path";
                Log::error($errorMessage);
                $this->sendSlackNotification($errorMessage);
                throw new FatalErrorException(StatusCodeConstant::EXTERNAL_API_FAILED_CODE, $errorMessage);
            }

            return $this->filesystem->url($s3Path);
        } catch (\Exception|FatalErrorException $e) {
            // Log any exception that might occur during the upload
            $errorMessage = "Exception during S3 upload for file: {$s3Path} Error: " . $e->getMessage();
            Log::error($errorMessage);
            $this->sendSlackNotification($errorMessage);
            throw new FatalErrorException(StatusCodeConstant::EXTERNAL_API_FAILED_CODE, $errorMessage);
        }
    }

    /**
     * Get the URL of a file in S3.
     *
     * @param string $s3Path
     * @return string
     */
    public function getFileUrl(string $s3Path): string
    {
        return $this->filesystem->url($s3Path);
    }

    /**
     * Delete a file from S3.
     *
     * @param string $s3Path
     * @return bool
     */
    public function deleteFile(string $s3Path): bool
    {
        try {
            $result = $this->filesystem->delete($s3Path);

            if ($result) {
                // Log success message
                Log::info("S3 file deleted successfully: $s3Path");
            } else {
                // Log a message indicating that the file wasn't deleted
                Log::warning("S3 file deletion failed or file not found: $s3Path");
            }

            return $result;
        } catch (\Exception $e) {
            // Log any exception that might occur during the deletion
            Log::error("Exception during S3 file deletion for file: $s3Path - " . $e->getMessage());
            return false;
        }
    }

    /**
     * Checks if a file exists at the specified location in the S3 filesystem.
     *
     * This method checks the existence of a file located at the given path within the S3 filesystem.
     *
     * @param string $s3Path The path to the file in the S3 filesystem.
     *
     * @return bool Returns true if the file exists at the specified path in the S3 filesystem, otherwise returns false.
     */
    public function fileExists(string $s3Path): bool
    {
        return $this->filesystem->exists($s3Path);
    }
}
