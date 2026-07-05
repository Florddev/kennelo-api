<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ActivityPermissionEnum;
use App\Enums\ActivityStatusEnum;
use App\Enums\ActivityTypeEnum;
use App\Enums\CollaboratorStatusEnum;
use App\Enums\PlanEnum;
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
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * @property ActivityStatusEnum $status
 * @property ActivityTypeEnum|null $type
 * @property array<string, mixed>|null $company_verification_data
 * @property-read User|null $manager
 * @property-read User|null $reviewedBy
 * @property-read Address|null $address
 * @property-read Collection<int, ActivityCycle> $cycles
 * @property-read Subscription|null $subscription
 */
class Activity extends Model implements HasMedia
{
    use HasFactory, HasUuids, InteractsWithMedia, SoftDeletes;

    protected $fillable = [
        'name',
        'siret',
        'siren',
        'ape_code',
        'type',
        'description',
        'phone',
        'email',
        'website',
        'google_place_id',
        'google_rating',
        'google_reviews_count',
        'google_maps_url',
        'google_synced_at',
        'address_id',
        'timezone',
        'is_active',
        'status',
        'company_verified_at',
        'company_verification_data',
        'rejection_reason',
        'reviewed_by',
        'reviewed_at',
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
            'status' => ActivityStatusEnum::class,
            'company_verified_at' => 'datetime',
            'company_verification_data' => 'array',
            'reviewed_at' => 'datetime',
            'google_rating' => 'float',
            'google_reviews_count' => 'integer',
            'google_synced_at' => 'datetime',
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

    public function scopeWhereManagerVerified(Builder $query): Builder
    {
        return $query->whereHas('manager', fn (Builder $q) => $q->whereNotNull('email_verified_at'));
    }

    public function address(): BelongsTo
    {
        return $this->belongsTo(Address::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
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

    /**
     * @return HasOne<Subscription, $this>
     */
    public function subscription(): HasOne
    {
        return $this->hasOne(Subscription::class)->latestOfMany();
    }

    public function effectivePlan(): PlanEnum
    {
        /** @var Subscription|null $subscription */
        $subscription = $this->relationLoaded('subscription')
            ? $this->getRelation('subscription')
            : $this->subscription()->with('plan')->first();

        if ($subscription === null || ! $subscription->isEffective()) {
            return PlanEnum::FREE;
        }

        $slug = $subscription->plan instanceof SubscriptionPlan ? $subscription->plan->slug : '';

        return PlanEnum::tryFrom($slug) ?? PlanEnum::FREE;
    }

    public function planLimit(string $key): ?int
    {
        return $this->effectivePlan()->limit($key);
    }

    public function planLimitIsUnlimited(string $key): bool
    {
        return $this->effectivePlan()->isUnlimited($key);
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

    public function resolveOnboardingCompleted(): bool
    {
        return (bool) ($this->stripe_onboarding_completed || $this->manager?->stripe_onboarding_completed);
    }
}
