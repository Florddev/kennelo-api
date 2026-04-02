<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\UserStatus;
use App\Services\MediaService;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property UserStatus $status
 */
class User extends Authenticatable implements HasMedia, JWTSubject, MustVerifyEmail
{
    use HasFactory, HasRoles, HasUuids, InteractsWithMedia, Notifiable, SoftDeletes;

    private const PASSWORD_CAST = 'hashed';

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'is_id_verified',
        'status',
        'password',
        'locale',
        'address_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'is_id_verified' => 'boolean',
            'status' => UserStatus::class,
            'password' => self::PASSWORD_CAST,
        ];
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
            $query->where('status', UserStatus::ACTIVE);
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

    public function identityVerifications(): HasMany
    {
        return $this->hasMany(IdentityVerification::class);
    }

    public function managedEstablishments(): HasMany
    {
        return $this->hasMany(Establishment::class, 'manager_id');
    }

    public function collaboratedEstablishments(): BelongsToMany
    {
        return $this->belongsToMany(Establishment::class, 'establishment_collaborators', 'user_id', 'establishment_id');
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
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
