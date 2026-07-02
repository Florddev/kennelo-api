<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\UserStatusEnum;
use App\Services\MediaService;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property UserStatusEnum $status
 * @property string|null $two_factor_secret
 * @property array<int, string>|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 */
class User extends Authenticatable implements HasLocalePreference, HasMedia, JWTSubject, MustVerifyEmail
{
    use HasFactory, HasRoles, HasUuids, InteractsWithMedia, Notifiable, SoftDeletes;

    private const PASSWORD_CAST = 'hashed';

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'google_id',
        'phone',
        'is_id_verified',
        'status',
        'password',
        'locale',
        'address_id',
        'stripe_account_id',
        'stripe_charges_enabled',
        'stripe_payouts_enabled',
        'stripe_onboarding_completed',
        'stripe_customer_id',
        'ban_reason',
        'banned_at',
        'banned_until',
        'banned_by',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'is_id_verified' => 'boolean',
            'status' => UserStatusEnum::class,
            'password' => self::PASSWORD_CAST,
            'stripe_charges_enabled' => 'boolean',
            'stripe_payouts_enabled' => 'boolean',
            'stripe_onboarding_completed' => 'boolean',
            'two_factor_secret' => 'encrypted',
            'two_factor_recovery_codes' => 'encrypted:array',
            'two_factor_confirmed_at' => 'datetime',
            'banned_at' => 'datetime',
            'banned_until' => 'datetime',
        ];
    }

    public function isBanned(): bool
    {
        return $this->status === UserStatusEnum::BANNED;
    }

    public function preferredLocale(): string
    {
        return $this->locale ?? (string) config('app.locale');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(MediaService::COLLECTION_AVATAR)
            ->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        MediaService::registerAvatarConversion($this);
    }

    protected static function booted(): void
    {
        static::addGlobalScope('active', function (Builder $query): void {
            $query->where('users.status', UserStatusEnum::ACTIVE);
        });
    }

    public function scopeWithInactive(Builder $query): Builder
    {
        return $query->withoutGlobalScope('active');
    }

    public function address(): BelongsTo
    {
        return $this->belongsTo(Address::class);
    }

    public function bannedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'banned_by');
    }

    public function identityVerifications(): HasMany
    {
        return $this->hasMany(IdentityVerification::class);
    }

    public function managedActivities(): HasMany
    {
        return $this->hasMany(Activity::class, 'manager_id');
    }

    public function collaboratedActivities(): BelongsToMany
    {
        return $this->belongsToMany(Activity::class, 'activity_collaborators', 'user_id', 'activity_id');
    }

    public function collaboratorLinks(): HasMany
    {
        return $this->hasMany(ActivityCollaborator::class, 'user_id');
    }

    public function favoriteActivities(): BelongsToMany
    {
        return $this->belongsToMany(Activity::class, 'favorites', 'user_id', 'activity_id')
            ->withPivot('created_at');
    }

    public function scanners(): HasMany
    {
        return $this->hasMany(Scanner::class);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    public function reviewsGiven(): HasMany
    {
        return $this->hasMany(Review::class, 'reviewer_id');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function rememberedDevices(): HasMany
    {
        return $this->hasMany(TwoFactorRememberedDevice::class);
    }

    public function getJWTIdentifier(): mixed
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims(): array
    {
        return [];
    }
}
