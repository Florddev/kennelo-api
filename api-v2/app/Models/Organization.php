<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Entreprise. Modèle minimal pour l'instant : la logique métier arrive avec le lot « Entreprise et équipe ».
 */
class Organization extends Model
{
    use HasUuids, SoftDeletes;

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

    /**
     * @return BelongsTo<User, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * @return HasMany<OrganizationMember, $this>
     */
    public function members(): HasMany
    {
        return $this->hasMany(OrganizationMember::class);
    }
}
