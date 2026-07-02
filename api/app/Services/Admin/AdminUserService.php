<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Enums\NotificationTypeEnum;
use App\Enums\UserStatusEnum;
use App\Models\User;
use App\Services\Notification\NotificationService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Password;

class AdminUserService
{
    public function __construct(
        private NotificationService $notifications
    ) {}

    public function ban(User $user, User $admin, array $data): User
    {
        $user->update([
            'status' => UserStatusEnum::BANNED,
            'ban_reason' => $data['reason'],
            'banned_at' => Carbon::now(),
            'banned_until' => $data['banned_until'] ?? null,
            'banned_by' => $admin->id,
        ]);

        $this->notifications->notify(
            $user,
            NotificationTypeEnum::ACCOUNT_BANNED,
            [
                'reason' => $data['reason'],
                'banned_until' => $data['banned_until'] ?? null,
            ],
        );

        return $user->fresh(['roles']);
    }

    public function unban(User $user): User
    {
        $user->update([
            'status' => UserStatusEnum::ACTIVE,
            'ban_reason' => null,
            'banned_at' => null,
            'banned_until' => null,
            'banned_by' => null,
        ]);

        $this->notifications->notify($user, NotificationTypeEnum::ACCOUNT_UNBANNED);

        return $user->fresh(['roles']);
    }

    public function forcePasswordReset(User $user): void
    {
        Password::sendResetLink(['email' => $user->email]);
    }

    public function verifyEmail(User $user): User
    {
        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
        }

        return $user->fresh(['roles']);
    }

    public function resendVerification(User $user): void
    {
        if (! $user->hasVerifiedEmail()) {
            $user->sendEmailVerificationNotification();
        }
    }
}
