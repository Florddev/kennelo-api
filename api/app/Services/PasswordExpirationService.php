<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;

class PasswordExpirationService
{
    public function __construct(private readonly JWTService $jwtService) {}

    public function isExpired(User $user): bool
    {
        if ($user->password === null) {
            return false;
        }

        $reference = $user->password_changed_at ?? $user->created_at;

        return $reference->addDays((int) config('auth.password_expiry_days'))->isPast();
    }

    public function challengeTokenIfExpired(User $user): ?string
    {
        return $this->isExpired($user) ? $this->jwtService->generatePasswordResetChallengeToken($user) : null;
    }
}
