<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\PlanEnum;
use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

class SubscriptionPlanSeeder extends Seeder
{
    public function run(): void
    {
        foreach (PlanEnum::cases() as $plan) {
            $config = config('plans.'.$plan->value, []);

            SubscriptionPlan::updateOrCreate(
                ['slug' => $plan->value],
                [
                    'name' => $config['name'] ?? ucfirst($plan->value),
                    'stripe_product_id' => $config['stripe_product_id'] ?? null,
                    'stripe_price_id' => $config['stripe_price_id'] ?? null,
                    'description' => $config['description'] ?? null,
                    'price_monthly' => $config['price_monthly'] ?? '0.00',
                    'price_yearly' => $config['price_yearly'] ?? null,
                    'currency' => strtoupper((string) config('services.stripe.currency', 'eur')),
                    'commission_rate' => $config['commission_rate'] ?? '0.0000',
                    'features' => $config['features'] ?? null,
                    'limits' => $config['limits'] ?? null,
                    'is_active' => true,
                ]
            );
        }
    }
}
