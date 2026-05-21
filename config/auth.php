<?php

return [

    'defaults' => [
        'guard'     => 'app',
        'passwords' => 'app_users',
    ],

    'guards' => [
        'web' => [
            'driver'   => 'session',
            'provider' => 'users',
        ],

        // Guard utama untuk RadiusManager
        'app' => [
            'driver'   => 'session',
            'provider' => 'app_users',
        ],
    ],

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model'  => App\Models\User::class,
        ],

        // Provider untuk AppUser
        'app_users' => [
            'driver' => 'eloquent',
            'model'  => App\Models\AppUser::class,
        ],
    ],

    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table'    => env('AUTH_PASSWORD_RESET_TOKEN_TABLE', 'password_reset_tokens'),
            'expire'   => 60,
            'throttle' => 2,
        ],

        'app_users' => [
            'provider' => 'app_users',
            'table'    => 'password_reset_tokens',
            'expire'   => 60,
            'throttle' => 2,
        ],
    ],

    'password_timeout' => env('AUTH_PASSWORD_TIMEOUT', 10800),

];
