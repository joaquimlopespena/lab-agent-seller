<?php

return [
    'magic_link_ttl_minutes' => env('CUSTOMER_MAGIC_LINK_TTL_MINUTES', 15),
    'session_ttl_hours' => env('CUSTOMER_SESSION_TTL_HOURS', 24),
    'cookie' => [
        'name' => env('CUSTOMER_SESSION_COOKIE', 'customer_session'),
        'secure' => env('CUSTOMER_SESSION_COOKIE_SECURE', true),
        'same_site' => 'lax',
    ],
];
