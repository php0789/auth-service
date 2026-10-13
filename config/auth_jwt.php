<?php

return [
    'private_key_path' => env('JWT_PRIVATE_KEY_PATH', storage_path('keys/private.pem')),
    'public_key_path' => env('JWT_PUBLIC_KEY_PATH', storage_path('keys/public.pem')),
    'issuer' => env('JWT_ISSUER', 'ecommerce-auth-service'),
    'audience' => env('JWT_AUDIENCE', 'ecommerce-user-service'),
    'ttl' => (int) env('JWT_TTL', 900),
];
