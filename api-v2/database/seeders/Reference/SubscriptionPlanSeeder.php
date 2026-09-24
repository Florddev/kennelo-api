<?php

declare(strict_types=1);

namespace Database\Seeders\Reference;

use App\Enums\PlanEnum;
use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

/**
 * Plans d'abonnement, créés à partir de config/plans.php (les identifiants Stripe dépendent de l'environnement).
 * Crée les plans manquants, ne modifie pas un plan existant : il se gère ensuite dans le back-office.
 */
class SubscriptionPlanSeeder extends Seeder
{
    public function run(): void
    {
        foreach (PlanEnum::cases() as $plan) {
            $config = config('plans.'.$plan->value);

            SubscriptionPlan::query()->firstOrCreate(
                ['slug' => $plan->value],
                [
                    'name' => $config['name'],
                    'price_monthly' => $config['price_monthly'] ?? '0.00',
                    'commission_rate' => $config['commission_rate'],
                    'stripe_price_id' => $config['stripe_price_id'] ?? null,
                    'limits' => $config['limits'] ?? [],
                    'is_active' => true,
                ],
            );
        }
    }
}
