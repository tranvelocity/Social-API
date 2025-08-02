<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Authentication Configurations
    |--------------------------------------------------------------------------
    */

    'sentry_laravel' => env('SENTRY_LARAVEL'),

    'should_report_exception' => [
        'Modules\Core\app\Exceptions\ConflictException',
        'Modules\Core\app\Exceptions\FatalErrorException',
        'Modules\Core\app\Exceptions\ForeignApiErrorException',
        'Symfony\Component\ErrorHandler\Error\FatalError',
        'Aws\Sqs\Exception\SqsException',
        'Aws\S3\Exception\S3Exception',
        'ErrorException',
        'ParseError',
        'TypeError',
        'Error',
    ],

    'auth' => [
        'headers' => [
            'api_key' => 'x-social-api-key',
            'timestamp' => 'x-social-timestamp',
            'signature' => 'x-social-signature',
            'session_id' => 'x-social-session-id',
            'auth_id' => 'x-social-auth-id',
            'user_ssid' => 'x-social-user-ssid',
        ],
        'authorization_expiration' => 0, //Unit: second, if 0 means it should not be verified (unlimited)
    ],

    /*
    |--------------------------------------------------------------------------
    | Response Configurations
    |--------------------------------------------------------------------------
    */
    'pagination' => [
        'offset' => 0,
        'limit' => 30,
        'max_limit' => 100,
    ],

    /*
    |--------------------------------------------------------------------------
    | Endpoint Configurations
    |--------------------------------------------------------------------------
    */
    'version' => env('API_VERSION', 1),
    'endpoints' => [
        // For admin dashboard
        'posters' => 'posters',
        'poster_item' => 'posters/{id}',

        'admin_posts' => 'admin/posts',
        'admin_post_item' => 'admin/posts/{id}',

        'admin_medias' => 'admin/medias',
        'admin_media_item' => 'admin/medias/{id}',

        'admin_post_comments' => 'admin/posts/{postId}/comments',
        'admin_post_comment' => 'admin/posts/{postId}/comments/{commentId}',
        'admin_post_comment_publish' => 'admin/posts/{postId}/comments/{id}/publish',
        'admin_post_comment_unpublish' => 'admin/posts/{postId}/comments/{id}/unpublish',

        'admin_post_likes' => 'admin/posts/{postId}/likes',

        'throttle_config' => 'throttle-config',
        'throttle_config_deletion' => 'throttle-config/{id}',

        'restricted_users' => 'restricted-users',
        'restricted_user_item' => 'restricted-users/{id}',

        // For user's pages
        'posts' => 'posts',
        'post_item' => 'posts/{id}',
        'posts_social' => 'posts-social',

        'post_comments' => 'posts/{postId}/comments',
        'post_comment' => 'posts/{postId}/comments/{commentId}',
        'post_comment_publish' => 'posts/{postId}/comments/{id}/publish',
        'post_comment_unpublish' => 'posts/{postId}/comments/{id}/unpublish',

        'post_likes' => 'posts/{postId}/likes',
        'post_like' => 'posts/{postId}/like',
        'post_unlike' => 'posts/{postId}/unlike',

        'medias' => 'medias',
        'media_item' => 'medias/{id}',

        'clear_member_caches' => 'clear-caches/member/{userId}',
        'clear_ng_word_caches' => 'clear-caches/ng-words',

        'session_check_is_poster' => 'session/is-poster',
        'session_retrieval' => 'session',
    ],
];
