<?php

declare(strict_types=1);

namespace Modules\Core\app\Traits;

use GuzzleHttp\Exception\InvalidArgumentException;
use Illuminate\Support\Carbon;

trait ApiSignatureTrait
{
    /**
     * Generate signature headers based on the provided API key, secret, and algorithm.
     *
     * @param string $apiKey     API key.
     * @param string $apiSecret  API secret.
     * @param string $algorithm  Algorithm for signature generation (e.g., 'sha1' or 'sha2').
     *
     * @return array An array containing apiKey, timestamp, and signature.
     */
    public function generateSignatureHeaders(string $apiKey, string $apiSecret, string $algorithm = 'sha2'): array
    {
        return match ($algorithm) {
            'sha1' => $this->generateSha1Signature($apiKey, $apiSecret),
            'sha2' => $this->generateSha2Signature($apiKey, $apiSecret),
            default => throw new InvalidArgumentException("Unsupported algorithm: $algorithm"),
        };
    }

    /**
     * Generate a signature and timestamp using SHA-512 based on the provided API key and secret.
     *
     * @param string $apiKey
     * @param string $apiSecret
     * @return array
     */
    public function generateSha2Signature(string $apiKey, string $apiSecret, string $algorithm = 'sha512'): array
    {
        // Generate a timestamp
        $timestamp = Carbon::now()->timestamp;

        $signature = hash_hmac($algorithm, $timestamp . $apiKey, $apiSecret);

        return [
            'api_key' => $apiKey,
            'timestamp' => $timestamp,
            'signature' => $signature,
        ];
    }

    /**
     * Verify apiKey, timestamp, and signature parameters from the headers using SHA-512.
     *
     * @param string $apiKey
     * @param int $timestamp
     * @param string $clientSignature
     * @param string $apiSecret
     * @param string $algorithm
     * @return bool
     */
    public function verifySha2Signature(string $apiKey, int $timestamp, string $clientSignature, string $apiSecret, string $algorithm = 'sha512'): bool
    {
        // Generate the server-side signature for comparison
        $serverSignature = hash_hmac($algorithm, $timestamp . $apiKey, $apiSecret);

        // Verify the signatures match
        return hash_equals($serverSignature, $clientSignature);
    }

    /**
     * Generate SHA1 signature for API authentication.
     *
     * @param string      $apiKey     API key.
     * @param string      $apiSecret  API secret.
     * @param string|null $timestamp  Timestamp (if null, the current timestamp is used).
     *
     * @return array An array containing apiKey, timestamp, and signature.
     */
    public function generateSha1Signature(string $apiKey, string $apiSecret, ?string $timestamp = null): array
    {
        $timestamp = $timestamp ?? Carbon::now()->timestamp;

        return [
            'api_key'   => $apiKey,
            'timestamp' => $timestamp,
            'signature' => hash_hmac('sha1', $timestamp . $apiKey, $apiSecret),
        ];
    }

    /**
     * Verify SHA1 signature for API authentication.
     *
     * @param string $apiKey          API key.
     * @param int    $timestamp       Timestamp.
     * @param string $clientSignature Client-provided signature for verification.
     * @param string $apiSecret       API secret.
     *
     * @return bool True if the signatures match, false otherwise.
     */
    public function verifySha1Signature(string $apiKey, int $timestamp, string $clientSignature, string $apiSecret): bool
    {
        // Generate the server-side signature for comparison
        $serverSignature = hash_hmac('sha1', $timestamp . $apiKey, $apiSecret);

        // Verify the signatures match
        return hash_equals($serverSignature, $clientSignature);
    }
}
