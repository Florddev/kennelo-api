<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SubscriptionStatusEnum;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property SubscriptionStatusEnum $status
 * @property-read User|null $user
 * @property-read SubscriptionPlan|null $plan
 */
class Subscription extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id',
        'subscription_plan_id',
        'stripe_subscription_id',
        'stripe_customer_id',
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SubscriptionPayment::class);
    }

    public function isActive(): bool
    {
        return $this->status === SubscriptionStatusEnum::ACTIVE;
    }

    public function isEffective(): bool
    {
        return $this->status->isEffective();
    }
}
