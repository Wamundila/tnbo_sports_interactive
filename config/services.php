<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'interactive' => [
        'service_key' => env('INTERACTIVE_SERVICE_KEY'),
    ],

    'authbox' => [
        'base_url' => env('AUTHBOX_BASE_URL'),
        'api_key' => env('AUTHBOX_API_KEY'),
        'current_user_path' => env('AUTHBOX_CURRENT_USER_PATH', '/api/v1/me'),
        'timeout_seconds' => (int) env('AUTHBOX_TIMEOUT_SECONDS', 5),
        'profile_cache_ttl_seconds' => (int) env('AUTHBOX_PROFILE_CACHE_TTL_SECONDS', 60),
    ],

];
