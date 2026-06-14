<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ActivityPermissionEnum;
use App\Enums\ActivityTypeEnum;
use App\Enums\CollaboratorStatusEnum;
use App\Enums\ReviewerTypeEnum;
use App\Services\MediaService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
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

/**
 * @property-read User|null $manager
 * @property-read Address|null $address
 * @property-read Collection<int, ActivityCycle> $cycles
 */
class Activity extends Model implements HasMedia
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
        'stripe_account_id',
        'stripe_onboarding_completed',
        'stripe_charges_enabled',
        'stripe_payouts_enabled',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'type' => ActivityTypeEnum::class,
            'stripe_onboarding_completed' => 'boolean',
            'stripe_charges_enabled' => 'boolean',
            'stripe_payouts_enabled' => 'boolean',
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
        return $this->belongsToMany(User::class, 'activity_collaborators', 'activity_id', 'user_id')
            ->wherePivot('status', CollaboratorStatusEnum::ACCEPTED->value);
    }

    public function roles(): HasMany
    {
        return $this->hasMany(ActivityRole::class);
    }

    public function collaboratorLinks(): HasMany
    {
        return $this->hasMany(ActivityCollaborator::class);
    }

    public function favoritedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'favorites', 'activity_id', 'user_id');
    }

    public function scopeWithIsFavorited(Builder $query, ?User $user): Builder
    {
        if ($user === null) {
            return $query;
        }

        return $query->withExists(['favoritedBy as is_favorited' => fn (Builder $q) => $q->where('users.id', $user->id)]);
    }

    public function cycles(): HasMany
    {
        return $this->hasMany(ActivityCycle::class);
    }

    public function availabilities(): HasMany
    {
        return $this->hasMany(ActivityAvailability::class);
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
            ->where('reviews.reviewer_type', ReviewerTypeEnum::USER->value);
    }

    public function collaboratorHasPermission(User $user, ActivityPermissionEnum $permission): bool
    {
        return ActivityCollaborator::query()
            ->where('activity_id', $this->id)
            ->where('user_id', $user->id)
            ->accepted()
            ->withPermission($permission)
            ->exists();
    }

    public function resolveStripeAccountId(): ?string
    {
        return $this->stripe_account_id ?? $this->manager?->stripe_account_id;
    }

    public function resolveChargesEnabled(): bool
    {
        return (bool) ($this->stripe_charges_enabled || $this->manager?->stripe_charges_enabled);
    }

    public function resolvePayoutsEnabled(): bool
    {
        return (bool) ($this->stripe_payouts_enabled || $this->manager?->stripe_payouts_enabled);
    }
}
