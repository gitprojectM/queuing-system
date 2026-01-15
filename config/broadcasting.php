<?php

return [
    // If PUSHER_APP_KEY is configured, default to pusher to avoid silent "null" broadcasting
    // which breaks realtime updates (Now Serving auto queue) without an obvious error.
    'default' => env('BROADCAST_DRIVER', env('PUSHER_APP_KEY') ? 'pusher' : 'null'),

    'connections' => [
        'pusher' => [
            'driver' => 'pusher',
            'key' => env('PUSHER_APP_KEY'),
            'secret' => env('PUSHER_APP_SECRET'),
            'app_id' => env('PUSHER_APP_ID'),
            'options' => [
                'cluster' => env('PUSHER_APP_CLUSTER'),
                'useTLS' => true,
            ],
        ],

        'redis' => [
            'driver' => 'redis',
            'connection' => 'default',
        ],

        'log' => [
            'driver' => 'log',
        ],

        'null' => [
            'driver' => 'null',
        ],
    ],
];
