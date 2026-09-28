<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ActivityStatusEnum;
use App\Enums\CancellationPolicyEnum;
use App\Enums\LocationModeEnum;
use App\Enums\OrganizationPermissionEnum;
use App\Enums\OrganizationRoleEnum;
use App\Enums\OrganizationStatusEnum;
use App\Enums\PlanEnum;
use App\Enums\ReviewerTypeEnum;
use App\Services\MediaService;
use Closure;
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
use Spatie\MediaLibrary\MediaCollections\Models\Collections\MediaCollection;
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
     * Réservable par un client : activité approuvée et ouverte, justificatifs obligatoires valables, entreprise
     * vérifiée qui peut encaisser et qui a donné son mandat de facturation (chaque paiement doit produire sa
     * facture). La recherche et la réservation s'appuient toutes deux sur cette seule règle.
     *
     * Les justificatifs sont vérifiés ici plutôt que par un changement de statut : une activité dont un
     * justificatif expire disparaît d'elle-même, et revient dès qu'un nouveau est approuvé. Le statut
     * « suspendue » reste une décision de Kennelo.
     */
    public function scopeBookable(Builder $query): Builder
    {
        return $query
            ->where('activities.status', ActivityStatusEnum::APPROVED)
            ->where('activities.is_active', true)
            ->whereDoesntHave('profession.documentRequirements', self::unmetRequirement(...))
            ->whereHas('organization', fn (Builder $query) => $query
                ->where('status', OrganizationStatusEnum::VERIFIED)
                ->where('stripe_charges_enabled', true)
                ->whereNotNull('billing_mandate_accepted_at'));
    }

    /**
     * Activités sur lesquelles l'utilisateur a ce droit, comme OrganizationPermissions::allows() mais en une
     * requête : celles des entreprises qu'il possède, celles des entreprises où l'un de ses rôles d'entreprise le
     * porte, et celles auxquelles est lié l'un de ses rôles qui le porte.
     */
    public function scopeAllowing(Builder $query, User $user, OrganizationPermissionEnum $permission): Builder
    {
        $grants = fn () => OrganizationMemberRole::query()
            ->whereIn('role', OrganizationRoleEnum::granting($permission))
            ->whereHas('member', fn (Builder $member) => $member->active()->where('user_id', $user->id));

        return $query->where(fn (Builder $query) => $query
            ->whereIn('activities.organization_id', Organization::query()->select('id')->where('owner_id', $user->id))
            ->orWhereIn('activities.organization_id', $grants()->whereNull('activity_id')->select('organization_id'))
            ->orWhereIn('activities.id', $grants()->whereNotNull('activity_id')->select('activity_id')));
    }

    /**
     * Ajoute rating_average et rating_count : la note moyenne et le nombre des avis publiés des clients, calculés
     * par la requête (index activity_id, is_published, published_at).
     */
    public function scopeWithRating(Builder $query): Builder
    {
        return $query
            ->withAvg(['reviews as rating_average' => fn (Builder $reviews) => $reviews->where('is_published', true)], 'overall_rating')
            ->withCount(['reviews as rating_count' => fn (Builder $reviews) => $reviews->where('is_published', true)]);
    }

    /**
     * rating_average et rating_count d'une activité déjà chargée.
     */
    public function loadRating(): static
    {
        return $this
            ->loadAvg(['reviews as rating_average' => fn (Builder $reviews) => $reviews->where('is_published', true)], 'overall_rating')
            ->loadCount(['reviews as rating_count' => fn (Builder $reviews) => $reviews->where('is_published', true)]);
    }

    /**
     * Activités auxquelles manque un justificatif obligatoire de leur métier : jamais déposé, en attente,
     * refusé ou expiré.
     */
    public function scopeMissingRequiredDocuments(Builder $query): Builder
    {
        return $query->whereHas('profession.documentRequirements', self::unmetRequirement(...));
    }

    /**
     * À passer à withExists() ou loadExists() : ajoute has_missing_documents, pour que l'équipe sache pourquoi
     * une activité approuvée n'apparaît pas dans la recherche.
     *
     * @return array<string, Closure(Builder): Builder>
     */
    public static function missingDocumentsCheck(): array
    {
        return [
            'profession as has_missing_documents' => fn (Builder $query): Builder => $query
                ->whereHas('documentRequirements', self::unmetRequirement(...)),
        ];
    }

    /**
     * Photos présentées aux clients, dans l'ordre. Après un retour à une offre inférieure, si le réglage
     * soft_disable_photos est actif, celles au-delà du quota de l'offre sont masquées sans être supprimées :
     * l'équipe les voit toujours, et elles réapparaissent dès que l'offre le permet.
     *
     * @return MediaCollection<int, Media>
     */
    public function publicImages(): MediaCollection
    {
        $images = $this->getMedia(MediaService::COLLECTION_IMAGES);
        $plan = $this->organization?->effectivePlan() ?? PlanEnum::FREE;

        if (! setting('soft_disable_photos') || $plan->isUnlimited('max_photos')) {
            return $images;
        }

        return $images->take((int) $plan->limit('max_photos'));
    }

    /**
     * Exigence du métier que l'activité ne remplit pas : aucun justificatif de ce type n'est valable aujourd'hui.
     */
    private static function unmetRequirement(Builder $query): Builder
    {
        return $query
            ->where('is_required', true)
            ->whereNotExists(ActivityDocument::query()
                ->valid()
                ->whereColumn('activity_documents.activity_id', 'activities.id')
                ->whereColumn('activity_documents.document_type', 'profession_document_requirements.document_type'));
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
     * @return HasMany<ActivityUnitType, $this>
     */
    public function unitTypes(): HasMany
    {
        return $this->hasMany(ActivityUnitType::class)->orderBy('sort_order')->orderBy('name')->orderBy('id');
    }

    /**
     * @return HasMany<ActivityPeriodSetting, $this>
     */
    public function periodSettings(): HasMany
    {
        return $this->hasMany(ActivityPeriodSetting::class);
    }

    /**
     * @return HasMany<Booking, $this>
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * Avis des clients sur l'activité, publiés ou non.
     *
     * @return HasMany<Review, $this>
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class)->where('reviewer_type', ReviewerTypeEnum::USER);
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function favoritedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'favorites')->withPivot('created_at');
    }
}
