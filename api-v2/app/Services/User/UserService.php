<?php

declare(strict_types=1);

namespace App\Services\User;

use App\Enums\BookingStatusEnum;
use App\Enums\NotificationTypeEnum;
use App\Models\User;
use App\Services\MediaService;
use App\Services\Notification\NotificationService;
use App\Services\User\Exceptions\UserHasActiveBookingsException;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class UserService
{
    public function __construct(
        private NotificationService $notifications,
    ) {}

    public function getAllPaginated(array $filters = []): LengthAwarePaginator
    {
        return User::withInactive()->with(['media', 'roles'])
            ->when(isset($filters['search']), function ($q) use ($filters) {
                $q->where(function ($q) use ($filters) {
                    $q->where('first_name', 'like', "%{$filters['search']}%")
                        ->orWhere('last_name', 'like', "%{$filters['search']}%")
                        ->orWhere('email', 'like', "%{$filters['search']}%");
                });
            })
            ->when(isset($filters['role']), fn ($q) => $q->role($filters['role']))
            ->when(
                isset($filters['sort_by']),
                fn ($q) => $q->orderBy($filters['sort_by'], $filters['sort_dir'] ?? 'asc'),
                fn ($q) => $q->latest(),
            )
            ->orderByDesc('id')
            ->paginate($filters['per_page'] ?? null);
    }

    public function updateProfile(User $user, array $data): User
    {
        $updateData = collect($data)->reject(fn ($value) => $value === null)->all();

        $user->update($updateData);

        return $user->fresh(['roles']);
    }

    public function uploadAvatar(User $user, UploadedFile $avatar): User
    {
        $user->addMedia($avatar)
            ->toMediaCollection(MediaService::COLLECTION_AVATAR);

        return $user->fresh(['roles', 'media']);
    }

    public function deleteAccount(User $user): void
    {
        // Requête directe : le modèle Booking arrive avec le lot « Réservation commune et séjours ».
        $hasActiveBookings = DB::table('bookings')
            ->where('user_id', $user->id)
            ->whereIn('status', [
                BookingStatusEnum::PENDING->value,
                BookingStatusEnum::CONFIRMED->value,
                BookingStatusEnum::IN_PROGRESS->value,
            ])
            ->exists();

        if ($hasActiveBookings) {
            throw UserHasActiveBookingsException::cannotDeleteAccount();
        }

        $user->delete();
    }

    /**
     * Les autres sessions de l'utilisateur sont déconnectées automatiquement : Sanctum compare
     * l'empreinte du mot de passe enregistrée dans chaque session à celle du compte.
     */
    public function changePassword(User $user, array $data): void
    {
        $this->ensurePasswordMatches($user, $data['current_password'], 'current_password');

        $user->forceFill([
            'password' => $data['password'],
            'password_changed_at' => now(),
        ])->save();
    }

    public function renewExpiredPassword(User $user, string $password): void
    {
        $user->forceFill([
            'password' => $password,
            'password_changed_at' => now(),
        ])->save();
    }

    public function changeEmail(User $user, array $data): User
    {
        $this->ensurePasswordMatches($user, $data['password'], 'password');

        $user->forceFill([
            'email' => $data['email'],
            'email_verified_at' => null,
        ])->save();

        $user->sendEmailVerificationNotification();

        return $user->fresh(['roles']);
    }

    /**
     * @throws ValidationException
     */
    private function ensurePasswordMatches(User $user, string $password, string $field): void
    {
        if (! Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([$field => __('account.password_incorrect')]);
        }
    }

    public function updateStatus(User $user, array $data): User
    {
        $user->forceFill(['status' => $data['status']])->save();

        $this->notifications->notify(
            $user,
            NotificationTypeEnum::ACCOUNT_STATUS_CHANGED,
            ['status' => $data['status']],
        );

        return $user->fresh(['roles']);
    }

    public function assignRoles(User $user, array $data): User
    {
        $user->syncRoles($data['roles']);

        return $user->fresh(['roles']);
    }

    public function removeRole(User $user, string $role): User
    {
        $user->removeRole($role);

        return $user->fresh(['roles']);
    }
}
