<?php

return [
    'slack' => [
        'app_name' => '[' . env('APP_ENV') . '] ' . strtoupper(env('APP_NAME')) . ' Notification Center',
        'channel' => [
            'default' => '#tranvelocity-channel',
            'testing' => '#tran-notification-test',
            'local' => '#tran-notification-test',
            'staging' => '#tran-notifications-staging',
            'production' => '#tran-notifications-production',
        ],
        'webhook_url' => '',
        'icons' => [
            'exclamation' => ':exclamation:'
        ]
    ]
];
