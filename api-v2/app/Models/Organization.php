<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\OrganizationLegalFormEnum;
use App\Enums\OrganizationStatusEnum;
use App\Enums\PlanEnum;
use App\Enums\VatRegimeEnum;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Entreprise : l'entité juridique qui porte l'équipe, l'abonnement et le compte Stripe Connect.
 *
 * Le statut, la vérification et les champs Stripe ne sont jamais remplis depuis une requête :
 * les services les écrivent avec forceFill().
 *
 * @property string $owner_id
 * @property OrganizationLegalFormEnum $legal_form
 * @property VatRegimeEnum $vat_regime
 * @property OrganizationStatusEnum $status
 * @property string|null $stripe_account_id
 * @property string|null $stripe_customer_id
 * @property bool $stripe_charges_enabled
 * @property bool $stripe_payouts_enabled
 * @property bool $stripe_onboarding_completed
 * @property array<string, mixed>|null $verification_data
 * @property Carbon|null $verified_at
 * @property Carbon|null $reviewed_at
 * @property Carbon|null $billing_mandate_accepted_at
 */
class Organization extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'owner_id',
        'legal_name',
        'legal_form',
        'siren',
        'siret',
        'ape_code',
        'vat_number',
        'vat_regime',
        'address_id',
    ];

    protected function casts(): array
    {
        return [
            'legal_form' => OrganizationLegalFormEnum::class,
            'vat_regime' => VatRegimeEnum::class,
            'status' => OrganizationStatusEnum::class,
            'stripe_charges_enabled' => 'boolean',
            'stripe_payouts_enabled' => 'boolean',
            'stripe_onboarding_completed' => 'boolean',
            'bank_account_verified_at' => 'datetime',
            'verification_data' => 'array',
            'verified_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'billing_mandate_accepted_at' => 'datetime',
        ];
    }

    public function isOwnedBy(User $user): bool
    {
        return $this->owner_id === $user->id;
    }

    /**
     * Offre qui s'applique aux quotas et à la commission : celle de l'abonnement s'il est payé ou en essai.
     */
    public function effectivePlan(): PlanEnum
    {
        $subscription = $this->subscription;

        if ($subscription === null || ! $subscription->isEffective()) {
            return PlanEnum::FREE;
        }

        return $subscription->plan?->plan() ?? PlanEnum::FREE;
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
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
     * @return HasMany<OrganizationMember, $this>
     */
    public function members(): HasMany
    {
        return $this->hasMany(OrganizationMember::class)->chaperone();
    }

    /**
     * @return HasMany<Activity, $this>
     */
    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class);
    }

    /**
     * Catalogue de l'entreprise, partagé par ses activités.
     *
     * @return HasMany<Service, $this>
     */
    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    /**
     * @return HasMany<Subscription, $this>
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * Abonnement en cours : le plus récent, qu'il soit actif, impayé ou résilié.
     *
     * @return HasOne<Subscription, $this>
     */
    public function subscription(): HasOne
    {
        return $this->hasOne(Subscription::class)->latestOfMany('created_at');
    }
}
