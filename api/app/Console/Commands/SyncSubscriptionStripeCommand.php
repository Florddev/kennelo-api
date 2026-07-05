<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\PlanEnum;
use App\Models\SubscriptionPlan;
use Illuminate\Console\Command;
use Stripe\Price;
use Stripe\Product;
use Stripe\StripeClient;

class SyncSubscriptionStripeCommand extends Command
{
    protected $signature = 'subscriptions:sync-stripe';

    protected $description = 'Create or synchronize the Stripe products and recurring prices for the paid subscription plans';

    private StripeClient $stripe;

    public function handle(): int
    {
        $this->stripe = app(StripeClient::class);
        $currency = strtolower((string) config('services.stripe.currency', 'eur'));

        foreach ([PlanEnum::STARTER, PlanEnum::PRO] as $planEnum) {
            $plan = SubscriptionPlan::where('slug', $planEnum->value)->first();

            if ($plan === null) {
                $this->error("Plan {$planEnum->value} not found. Run the seeder first.");

                return self::FAILURE;
            }

            $product = $this->resolveProduct($planEnum, $plan->name);
            $price = $this->resolvePrice($planEnum, $product->id, $currency, (string) $plan->price_monthly);

            $plan->update([
                'stripe_product_id' => $product->id,
                'stripe_price_id' => $price->id,
            ]);

            $this->info("{$planEnum->value}: product {$product->id} / price {$price->id}");
        }

        $this->info('Stripe subscription plans synchronized.');

        return self::SUCCESS;
    }

    private function resolveProduct(PlanEnum $plan, string $name): Product
    {
        $existing = $this->stripe->products->search([
            'query' => "metadata['plan_slug']:'{$plan->value}'",
        ]);

        if (! empty($existing->data)) {
            return $existing->data[0];
        }

        return $this->stripe->products->create([
            'name' => 'Kennelo '.$name,
            'metadata' => ['plan_slug' => $plan->value],
        ]);
    }

    private function resolvePrice(PlanEnum $plan, string $productId, string $currency, string $priceMonthly): Price
    {
        $existing = $this->stripe->prices->search([
            'query' => "metadata['plan_slug']:'{$plan->value}' AND active:'true'",
        ]);

        if (! empty($existing->data)) {
            return $existing->data[0];
        }

        return $this->stripe->prices->create([
            'product' => $productId,
            'currency' => $currency,
            'unit_amount' => (int) bcmul($priceMonthly, '100', 0),
            'recurring' => ['interval' => 'month'],
            'metadata' => ['plan_slug' => $plan->value],
        ]);
    }
}
