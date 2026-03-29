<?php

return [
    'algorithm' => env('JWT_ALGORITHM', 'RS256'),
    'public_key' => env('JWT_PUBLIC_KEY'),
    'public_key_path' => env('JWT_PUBLIC_KEY_PATH', config_path('auth_public.pem')),
    'issuer' => env('JWT_ISSUER'),
    'audience' => env('JWT_AUDIENCE'),
    'subject_pattern' => env('JWT_SUBJECT_PATTERN', '/^ts_\d+$/'),
    'clock_skew_seconds' => (int) env('JWT_CLOCK_SKEW_SECONDS', 30),
];
