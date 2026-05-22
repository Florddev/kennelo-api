<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\EstablishmentPermission;
use App\Enums\EstablishmentType;
use App\Enums\ReviewerType;
use App\Services\MediaService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Establishment extends Model implements HasMedia
{
    use HasFactory, HasUuids, InteractsWithMedia, SoftDeletes;

    protected $fillable = [
        'name',
        'siret',
        'type',
        'description',
        'phone',
        'email',
        'website',
        'address_id',
        'timezone',
        'is_active',
        'manager_id',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'type' => EstablishmentType::class,
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(MediaService::COLLECTION_AVATAR)
            ->singleFile();

        $this->addMediaCollection(MediaService::COLLECTION_IMAGES)
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        MediaService::registerAvatarConversion($this);
        MediaService::registerImagesConversion($this);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function address(): BelongsTo
    {
        return $this->belongsTo(Address::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function collaborators(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'establishment_collaborators', 'establishment_id', 'user_id');
    }

    public function collaboratorPermissions(): HasMany
    {
        return $this->hasMany(EstablishmentCollaboratorPermission::class);
    }

    public function capacities(): HasMany
    {
        return $this->hasMany(EstablishmentCapacity::class);
    }

    public function availabilities(): HasMany
    {
        return $this->hasMany(EstablishmentAvailability::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    public function reviews(): HasManyThrough
    {
        return $this->hasManyThrough(Review::class, Booking::class)
            ->where('reviews.reviewer_type', ReviewerType::USER->value);
    }

    public function collaboratorHasPermission(User $user, EstablishmentPermission $permission): bool
    {
        if (! $this->collaborators()->where('users.id', $user->id)->exists()) {
            return false;
        }

        return $this->collaboratorPermissions()
            ->where('user_id', $user->id)
            ->where('permission', $permission->value)
            ->exists();
    }
}
