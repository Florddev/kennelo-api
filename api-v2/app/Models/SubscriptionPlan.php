<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PlanEnum;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * @property array<string, mixed>|null $features
 * @property array<string, int|null>|null $limits
 */
class SubscriptionPlan extends Model
{
    use HasUuids;

    protected $fillable = [
        'stripe_product_id',
        'stripe_price_id',
        'name',
        'slug',
        'description',
        'price_monthly',
        'price_yearly',
        'currency',
        'commission_rate',
        'features',
        'limits',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price_monthly' => 'decimal:2',
            'price_yearly' => 'decimal:2',
            'commission_rate' => 'decimal:4',
            'features' => 'array',
            'limits' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function plan(): ?PlanEnum
    {
        return PlanEnum::tryFrom($this->slug);
    }

    public function limit(string $key): ?int
    {
        $value = $this->limits[$key] ?? null;

        return $value === null ? null : (int) $value;
    }
}
