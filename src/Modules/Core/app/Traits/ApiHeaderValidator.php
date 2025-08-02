<?php

declare(strict_types=1);

namespace Modules\Core\app\Traits;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ApiHeaderValidator
{
    /**
     * Validate API headers.
     *
     * @param array $headers
     * @return \Illuminate\Contracts\Validation\Validator
     */
    public static function validateBasicAuthHeaders(array $headers): \Illuminate\Contracts\Validation\Validator
    {
        self::logHeaders('Headers for validation:', $headers);

        return Validator::make($headers, [
            config('api.auth.headers.api_key') => ['required'],
            config('api.auth.headers.signature') => ['required'],
            config('api.auth.headers.timestamp') => ['required'],
        ]);
    }

    /**
     * Validate Session ID header.
     *
     * @param array $headers
     * @return \Illuminate\Contracts\Validation\Validator
     */
    public static function validateSessionCheckHeaders(array $headers): \Illuminate\Contracts\Validation\Validator
    {
        self::logHeaders('Headers for Session ID validation:', $headers);

        return Validator::make($headers, [
            config('api.auth.headers.auth_id') => ['required'],
            config('api.auth.headers.user_ssid') => ['required'],
        ]);
    }

    /**
     * Log or print headers for debugging.
     *
     * @param string $message
     * @param array $headers
     */
    private static function logHeaders(string $message, array $headers): void
    {
        Log::info($message . ' ' . json_encode($headers));
    }
}
