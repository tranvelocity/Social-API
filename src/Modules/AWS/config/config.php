<?php

return [
    'aws_access_key_id' => env('AWS_ACCESS_KEY_ID'),
    'aws_secret_access_key' => env('AWS_SECRET_ACCESS_KEY'),
    'aws_default_region' => 'ap-northeast-1',
    's3' => [
        'driver' => 's3',
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'ap-northeast-1'),
        'bucket' => match (env('APP_ENV')) {
            'local', 'testing' => 'tranvelocity-local',
            'staging' => 'tranvelocity-staging',
            'production' => 'tranvelocity-production',
            default => 'tranvelocity-local',
        },
        'min_multipart_upload_size' => 100000000, // ~1GB
    ],
    'cloudfront' => [
        'distribution_id' => match (env('APP_ENV')) {
            'staging' => '', // Replace it with actual staging distribution ID
            'production' => '', // replace it with actual production distribution ID
            default => 'E25CX5XTLB3WFH', // covers 'testing', 'local', and any other
        },
        'url' => match (env('APP_ENV')) {
            'staging' => 'https://123456789.cloudfront.net',
            'production' => 'https://987654321.cloudfront.net',
            default => 'https://1234567890.cloudfront.net', // covers 'testing', 'local', and any other
        },
    ],
    'dynamodb' => [
        'version' => 'latest',
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DYNAMODB_REGION', 'ap-northeast-1'),
        'tables' => [
            'authentication' => 'authentication',
        ],
    ],
];
