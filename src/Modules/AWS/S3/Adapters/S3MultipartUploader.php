<?php

declare(strict_types=1);

namespace Modules\AWS\S3\Adapters;

use Aws\Credentials\CredentialProvider;
use Aws\Exception\AwsException;
use Aws\Exception\MultipartUploadException;
use Aws\S3\S3Client;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Modules\Core\app\Constants\StatusCodeConstant;
use Modules\Core\app\Exceptions\FatalErrorException;
use Modules\Notification\Traits\NotificationTrait;

class S3MultipartUploader
{
    use NotificationTrait;
    private S3Client $s3Client;

    // Separate the original file into multiple chunks. Each chunk size is 50 MB.
    private const CHUNK_SIZE = 50 * 1024 * 1024;

    /**
     * S3MultipartUploader constructor.
     *
     * @param string $region The AWS region of your S3 bucket.
     */
    public function __construct(string $region = 'ap-northeast-1', string $version = 'latest')
    {
        $this->s3Client = new S3Client([
            'region' => $region,
            'version' => $version,
            'credentials' => CredentialProvider::defaultProvider(),
        ]);
    }

    /**
     * Upload a large video file to an S3 bucket using multipart upload.
     *
     * @param UploadedFile $file The video file to be uploaded.
     * @param string $filePath The desired file path in the S3 bucket.
     * @param string|null $bucketName The name of the S3 bucket.
     *
     * @return string The S3 URL of the uploaded file on success, or throw an exception on failure.
     */
    public function multipartUpload(UploadedFile $file, string $filePath, ?string $bucketName = null): string
    {
        $bucketName = $bucketName ?? Config::get('aws.s3.bucket');

        $uploadId = null;

        try {
            // Initiate the multipart upload
            $multipartUpload = $this->s3Client->createMultipartUpload([
                'Bucket' => $bucketName,
                'Key' => $filePath,
            ]);

            $uploadId = $multipartUpload['UploadId'];
            $source = fopen($file->path(), 'rb');
            $uploadedParts = [];
            $partNumber = 1;

            while (!feof($source)) {
                // Upload each part of the file
                $result = $this->s3Client->uploadPart([
                    'Bucket' => $bucketName,
                    'Key' => $filePath,
                    'UploadId' => $uploadId,
                    'PartNumber' => $partNumber,
                    'Body' => fread($source, self::CHUNK_SIZE),
                ]);

                $uploadedParts[] = [
                    'PartNumber' => $partNumber++,
                    'ETag' => $result['ETag'],
                ];
            }

            // Complete the multipart upload
            $this->s3Client->completeMultipartUpload([
                'Bucket' => $bucketName,
                'Key' => $filePath,
                'UploadId' => $uploadId,
                'MultipartUpload' => [
                    'Parts' => $uploadedParts,
                ],
            ]);

            fclose($source);

            // Get the URL of the uploaded file
            return $this->s3Client->getObjectUrl($bucketName, $filePath);
        } catch (MultipartUploadException $e) {
            // Use $uploadId which was initialized to null
            $this->abortMultipartUpload($bucketName, $filePath, $uploadId);
            $errorMessage = "Failed to upload file: {$filePath} to the {$bucketName} bucket. Error content : " . $e->getMessage();
            Log::error($errorMessage);
            $this->sendSlackNotification($errorMessage);
            throw new FatalErrorException(StatusCodeConstant::EXTERNAL_API_FAILED_CODE, $errorMessage);
        } catch (AwsException $e) {
            $errorMessage = "AWS Exception during upload file: {$filePath} to the {$bucketName} bucket. Error content : " . $e->getMessage();
            Log::error($errorMessage);
            $this->sendSlackNotification($errorMessage);
            throw new FatalErrorException(StatusCodeConstant::EXTERNAL_API_FAILED_CODE, $errorMessage);
        }
    }

    /**
     * Abort a multipart upload in case of failure.
     *
     * @param string $bucketName The name of the S3 bucket.
     * @param string $filePath The file path of the file being uploaded.
     * @param string $uploadId The upload ID of the multipart upload.
     */
    private function abortMultipartUpload(string $bucketName, string $filePath, string $uploadId): void
    {
        try {
            $this->s3Client->abortMultipartUpload([
                'Bucket' => $bucketName,
                'Key' => $filePath,
                'UploadId' => $uploadId,
            ]);
        } catch (AwsException $e) {
            Log::error('Error aborting multipart upload: ' . $e->getMessage());
        }
    }
}
