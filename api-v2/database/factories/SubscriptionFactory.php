<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PlanEnum;
use App\Enums\SubscriptionStatusEnum;
use App\Models\Organization;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Les offres viennent du seeder de référence SubscriptionPlanSeeder, à lancer avant.
 *
 * @extends Factory<Subscription>
 */
class SubscriptionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'subscription_plan_id' => fn (): string => SubscriptionPlan::where('slug', PlanEnum::STARTER->value)->valueOrFail('id'),
            'stripe_subscription_id' => 'sub_'.Str::random(14),
            'status' => SubscriptionStatusEnum::ACTIVE,
            'current_period_start' => now()->subDays(10),
            'current_period_end' => now()->addDays(20),
        ];
    }

    public function onPlan(PlanEnum $plan): static
    {
        return $this->state(fn (): array => [
            'subscription_plan_id' => SubscriptionPlan::where('slug', $plan->value)->valueOrFail('id'),
        ]);
    }

    public function canceling(): static
    {
        return $this->state(fn (array $attributes): array => [
            'canceled_at' => now(),
            'ends_at' => $attributes['current_period_end'],
        ]);
    }

    public function status(SubscriptionStatusEnum $status): static
    {
        return $this->state(fn (): array => ['status' => $status]);
    }
}
