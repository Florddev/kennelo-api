<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Notifications\MagicLinkNotification;
use Illuminate\Support\Facades\Cache;

class MagicLinkService
{
    private const CACHE_PREFIX = 'magic-link-used:';

    public function send(string $email): void
    {
        $user = User::where('email', $email)->first();

        if ($user === null) {
            return;
        }

        $user->notify(new MagicLinkNotification);
    }

    public function markAsUsed(User $user, string $expires, string $signature): bool
    {
        $key = self::CACHE_PREFIX.hash('sha256', $user->getKey().$expires.$signature);

        return Cache::add($key, true, now()->addMinutes(20));
    }
}
