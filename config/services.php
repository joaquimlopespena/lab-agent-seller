<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
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

    'pipedrive' => [
        'client_id' => env('PIPEDRIVE_CLIENT_ID'),
        'client_secret' => env('PIPEDRIVE_CLIENT_SECRET'),
        'redirect_uri' => env('PIPEDRIVE_REDIRECT_URI'),
        'scopes' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('PIPEDRIVE_SCOPES', 'deals:read,persons:read,organizations:read,activities:read,users:read,products:read')),
        ))),
        'authorization_endpoint' => env('PIPEDRIVE_AUTHORIZATION_ENDPOINT', 'https://oauth.pipedrive.com/oauth/authorize'),
        'token_endpoint' => env('PIPEDRIVE_TOKEN_ENDPOINT', 'https://oauth.pipedrive.com/oauth/token'),
        'api_endpoint' => env('PIPEDRIVE_API_ENDPOINT', 'https://api.pipedrive.com/v1'),
        'state_ttl_minutes' => (int) env('PIPEDRIVE_STATE_TTL_MINUTES', 10),
        'timeout_seconds' => (int) env('PIPEDRIVE_TIMEOUT_SECONDS', 10),
        'connect_timeout_seconds' => (int) env('PIPEDRIVE_CONNECT_TIMEOUT_SECONDS', 3),
        'page_size' => (int) env('PIPEDRIVE_PAGE_SIZE', 100),
        'sync_attempts' => (int) env('PIPEDRIVE_SYNC_ATTEMPTS', 3),
        'sync_retry_delay_milliseconds' => (int) env('PIPEDRIVE_SYNC_RETRY_DELAY_MILLISECONDS', 100),
    ],

];
