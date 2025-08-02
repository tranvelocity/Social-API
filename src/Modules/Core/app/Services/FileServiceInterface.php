<?php

declare(strict_types=1);

namespace Modules\Core\app\Services;

use Illuminate\Http\UploadedFile;

interface FileServiceInterface
{
    /**
     * Uploads a file to Amazon S3. Uses multipart upload for large files.
     *
     * @param UploadedFile $file The file to be uploaded.
     * @param string $s3Path The desired path in the S3 bucket.
     *
     * @return string|null The S3 URL of the uploaded file on success, or null on failure.
     */
    public function uploadFile(UploadedFile $file, string $s3Path): ?string;

    /**
     * Deletes a file from Amazon S3.
     *
     * @param string $filePath The path of the file in the S3 bucket.
     *
     * @return bool True if the file was successfully deleted, false otherwise.
     */
    public function deleteFile(string $filePath): bool;

    /**
     * Checks if a file exists at the specified location in the Amazon S3.
     *
     * This method checks the existence of a file located at the given path within the S3 filesystem.
     *
     * @param string $s3Path The path to the file in the Amazon S3.
     *
     * @return bool Returns true if the file exists at the specified path in the Amazon S3., otherwise returns false.
     */
    public function fileExists(string $s3Path): bool;
}
