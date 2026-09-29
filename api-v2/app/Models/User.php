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
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\HasApiTokens;
use Laravel\Sanctum\PersonalAccessToken;
use Laravel\Sanctum\TransientToken;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property UserStatusEnum $status
 * @property Carbon|null $email_verified_at
 * @property Carbon|null $last_seen_at
 * @property Carbon|null $password_changed_at
 * @property string|null $two_factor_secret
 * @property array<int, string>|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property Carbon|null $banned_at
 * @property Carbon|null $banned_until
 */
class User extends Authenticatable implements HasLocalePreference, HasMedia, MustVerifyEmail
{
    use HasApiTokens, HasFactory, HasRoles, HasUuids, InteractsWithMedia, Notifiable, SoftDeletes;

    private const PASSWORD_CAST = 'hashed';

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'google_id',
        'phone',
        'last_seen_at',
        'password',
        'password_changed_at',
        'locale',
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
            'last_seen_at' => 'datetime',
            'status' => UserStatusEnum::class,
            'password' => self::PASSWORD_CAST,
            'password_changed_at' => 'datetime',
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

    public function currentAccessToken(): PersonalAccessToken|TransientToken|null
    {
        return $this->accessToken;
    }

    /**
     * Accès à l'espace de gestion : réservé aux membres actifs d'au moins une entreprise ouverte.
     * Le propriétaire d'une entreprise en est toujours membre.
     */
    public function canAccessManagement(): bool
    {
        return $this->organizationMemberships()->active()->whereHas('organization')->exists();
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

    /**
     * @return HasMany<OrganizationMember, $this>
     */
    public function organizationMemberships(): HasMany
    {
        return $this->hasMany(OrganizationMember::class);
    }

    /**
     * @return HasMany<Organization, $this>
     */
    public function ownedOrganizations(): HasMany
    {
        return $this->hasMany(Organization::class, 'owner_id');
    }

    /**
     * @return BelongsToMany<Organization, $this>
     */
    public function organizations(): BelongsToMany
    {
        return $this->belongsToMany(Organization::class, 'organization_members')
            ->withPivot('status')
            ->withTimestamps();
    }

    public function rememberedDevices(): HasMany
    {
        return $this->hasMany(TwoFactorRememberedDevice::class);
    }

    /**
     * Carnet d'adresses du client.
     *
     * @return HasMany<UserAddress, $this>
     */
    public function addresses(): HasMany
    {
        return $this->hasMany(UserAddress::class);
    }

    /**
     * @return HasMany<Pet, $this>
     */
    public function pets(): HasMany
    {
        return $this->hasMany(Pet::class);
    }

    /**
     * Réservations passées par le client.
     *
     * @return HasMany<Booking, $this>
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * Factures et avoirs reçus en tant que client : réservations et frais de service.
     *
     * @return HasMany<Invoice, $this>
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'recipient_user_id');
    }

    /**
     * Conversations en tant que client, une par activité contactée.
     *
     * @return HasMany<Conversation, $this>
     */
    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    /**
     * @return BelongsToMany<Activity, $this>
     */
    public function favoriteActivities(): BelongsToMany
    {
        return $this->belongsToMany(Activity::class, 'favorites')->withPivot('created_at');
    }
}
