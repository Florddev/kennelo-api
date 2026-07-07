<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\PlanEnum;
use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

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

        if ($this->stripeConfigured()) {
            Artisan::call('subscriptions:sync-stripe');
        }
    }

    private function stripeConfigured(): bool
    {
        $secret = (string) config('services.stripe.secret');

        return $secret !== '' && ! str_starts_with($secret, 'sk_test_dummy');
    }
}
