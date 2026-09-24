<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SubscriptionStatusEnum;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Abonnement d'une entreprise, tenu à jour par les webhooks Stripe.
 *
 * @property SubscriptionStatusEnum $status
 * @property Carbon|null $current_period_start
 * @property Carbon|null $current_period_end
 * @property Carbon|null $trial_ends_at
 * @property Carbon|null $canceled_at
 * @property Carbon|null $ends_at
 */
class Subscription extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'organization_id',
        'subscription_plan_id',
        'stripe_subscription_id',
        'status',
        'current_period_start',
        'current_period_end',
        'trial_ends_at',
        'canceled_at',
        'ends_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => SubscriptionStatusEnum::class,
            'current_period_start' => 'datetime',
            'current_period_end' => 'datetime',
            'trial_ends_at' => 'datetime',
            'canceled_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function isEffective(): bool
    {
        return $this->status->isEffective();
    }

    /**
     * La résiliation est demandée : l'offre reste active jusqu'à la fin de la période payée.
     */
    public function isCanceling(): bool
    {
        return $this->canceled_at !== null;
    }

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @return BelongsTo<SubscriptionPlan, $this>
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan_id');
    }

    /**
     * @return HasMany<SubscriptionPayment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(SubscriptionPayment::class);
    }
}
