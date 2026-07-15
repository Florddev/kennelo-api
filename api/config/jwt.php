<?php

declare(strict_types=1);
use PHPOpenSourceSaver\JWTAuth\Providers\Auth\Illuminate;
use PHPOpenSourceSaver\JWTAuth\Providers\JWT\Lcobucci;

return [

    'secret' => env('JWT_SECRET'),

    'keys' => [
        'public' => env('JWT_PUBLIC_KEY'),
        'private' => env('JWT_PRIVATE_KEY'),
        'passphrase' => env('JWT_PASSPHRASE'),
    ],

    'ttl' => (int) env('JWT_TTL', 60),

    'two_factor_challenge_ttl' => (int) env('JWT_TWO_FACTOR_CHALLENGE_TTL', 5),

    'password_reset_challenge_ttl' => (int) env('JWT_PASSWORD_RESET_CHALLENGE_TTL', 15),

    'impersonation_ttl' => (int) env('JWT_IMPERSONATION_TTL', 15),

    'refresh_token_ttl' => (int) env('JWT_REFRESH_TOKEN_TTL', 43200),

    'refresh_ttl' => (int) env('JWT_REFRESH_TTL', 43200),

    'algo' => env('JWT_ALGO', 'HS256'),

    'required_claims' => ['iss', 'iat', 'exp', 'nbf', 'sub', 'jti'],

    'persistent_claims' => [],

    'lock_subject' => true,

    'leeway' => (int) env('JWT_LEEWAY', 60),

    'blacklist_enabled' => (bool) env('JWT_BLACKLIST_ENABLED', true),

    'blacklist_grace_period' => (int) env('JWT_BLACKLIST_GRACE_PERIOD', 0),

    'show_black_list_exception' => (bool) env('JWT_SHOW_BLACKLIST_EXCEPTION', true),

    'decrypt_cookies' => false,

    'providers' => [
        'jwt' => Lcobucci::class,
        'auth' => Illuminate::class,
        'storage' => PHPOpenSourceSaver\JWTAuth\Providers\Storage\Illuminate::class,
    ],

];
