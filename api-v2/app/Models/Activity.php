<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ActivityStatusEnum;
use App\Enums\CancellationPolicyEnum;
use App\Enums\LocationModeEnum;
use App\Enums\OrganizationStatusEnum;
use App\Services\MediaService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Un métier exercé par une entreprise, dans un lieu. Le légal et le financier sont portés par l'entreprise.
 *
 * Le statut de revue n'est jamais rempli depuis une requête : les services l'écrivent avec forceFill().
 *
 * @property string $id
 * @property string $organization_id
 * @property string $profession_id
 * @property string|null $address_id
 * @property string $timezone
 * @property bool $serves_at_pro
 * @property bool $serves_at_client
 * @property bool $serves_remote
 * @property int|null $service_radius_km
 * @property CancellationPolicyEnum $cancellation_policy
 * @property bool $is_active
 * @property ActivityStatusEnum $status
 * @property Carbon|null $reviewed_at
 * @property-read Organization|null $organization
 * @property-read Profession|null $profession
 * @property-read Address|null $address
 */
class Activity extends Model implements HasMedia
{
    use HasFactory, HasUuids, InteractsWithMedia, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'profession_id',
        'name',
        'description',
        'phone',
        'email',
        'website',
        'address_id',
        'establishment_siret',
        'timezone',
        'serves_at_pro',
        'serves_at_client',
        'serves_remote',
        'service_radius_km',
        'cancellation_policy',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'serves_at_pro' => 'boolean',
            'serves_at_client' => 'boolean',
            'serves_remote' => 'boolean',
            'service_radius_km' => 'integer',
            'cancellation_policy' => CancellationPolicyEnum::class,
            'is_active' => 'boolean',
            'status' => ActivityStatusEnum::class,
            'reviewed_at' => 'datetime',
        ];
    }

    public function servesAt(LocationModeEnum $location): bool
    {
        return match ($location) {
            LocationModeEnum::AT_PRO => $this->serves_at_pro,
            LocationModeEnum::AT_CLIENT => $this->serves_at_client,
            LocationModeEnum::REMOTE => $this->serves_remote,
        };
    }

    /**
     * @return list<LocationModeEnum>
     */
    public function locations(): array
    {
        return array_values(array_filter(LocationModeEnum::cases(), $this->servesAt(...)));
    }

    /**
     * Réservable par un client : activité approuvée et ouverte, entreprise vérifiée qui peut encaisser.
     * La recherche et la réservation s'appuient toutes deux sur cette seule règle.
     */
    public function scopeBookable(Builder $query): Builder
    {
        return $query
            ->where('activities.status', ActivityStatusEnum::APPROVED)
            ->where('activities.is_active', true)
            ->whereHas('organization', fn (Builder $query) => $query
                ->where('status', OrganizationStatusEnum::VERIFIED)
                ->where('stripe_charges_enabled', true));
    }

    /**
     * Activités auxquelles manque un justificatif obligatoire de leur métier : jamais déposé, en attente,
     * refusé ou expiré.
     */
    public function scopeMissingRequiredDocuments(Builder $query): Builder
    {
        return $query->whereHas('profession.documentRequirements', fn (Builder $query) => $query
            ->where('is_required', true)
            ->whereNotExists(ActivityDocument::query()
                ->valid()
                ->whereColumn('activity_documents.activity_id', 'activities.id')
                ->whereColumn('activity_documents.document_type', 'profession_document_requirements.document_type')));
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(MediaService::COLLECTION_IMAGES)
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        MediaService::registerImagesConversion($this);
    }

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @return BelongsTo<Profession, $this>
     */
    public function profession(): BelongsTo
    {
        return $this->belongsTo(Profession::class);
    }

    /**
     * @return BelongsTo<Address, $this>
     */
    public function address(): BelongsTo
    {
        return $this->belongsTo(Address::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * @return BelongsToMany<AnimalType, $this>
     */
    public function animalTypes(): BelongsToMany
    {
        return $this->belongsToMany(AnimalType::class, 'activity_animal_types');
    }

    /**
     * @return HasMany<ActivityOpeningHour, $this>
     */
    public function openingHours(): HasMany
    {
        return $this->hasMany(ActivityOpeningHour::class)->orderBy('weekday')->orderBy('opens_at');
    }

    /**
     * @return HasMany<ActivityAvailability, $this>
     */
    public function availabilities(): HasMany
    {
        return $this->hasMany(ActivityAvailability::class);
    }

    /**
     * @return HasMany<ActivityDocument, $this>
     */
    public function documents(): HasMany
    {
        return $this->hasMany(ActivityDocument::class);
    }

    /**
     * Prestations du catalogue de l'entreprise que l'activité vend, avec leurs conditions de vente.
     *
     * @return BelongsToMany<Service, $this, ActivityOffer, 'offer'>
     */
    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'activity_services')
            ->using(ActivityOffer::class)
            ->as('offer')
            ->withPivot(['organization_id', 'offered_as', 'adjustment_percent', 'is_included', 'is_active'])
            ->withTimestamps();
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function favoritedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'favorites')->withPivot('created_at');
    }
}
