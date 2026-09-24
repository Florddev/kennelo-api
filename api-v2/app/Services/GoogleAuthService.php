<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\User as SocialiteUser;

class GoogleAuthService
{
    public function userFromToken(string $token): ?SocialiteUser
    {
        $driver = Socialite::driver('google');

        if (! $driver instanceof AbstractProvider) {
            return null;
        }

        return rescue(
            fn (): SocialiteUser => $driver->stateless()->userFromToken($token),
            null,
            fn (\Throwable $e) => Log::warning('Google token verification failed: '.$e->getMessage()),
        );
    }
}
