<?php

declare(strict_types=1);

namespace App\Services\Subscription;

use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\Stripe\StripeCustomerService;
use Illuminate\Validation\ValidationException;
use Stripe\Invoice;
use Stripe\StripeClient;

class SubscriptionService
{
    public function __construct(
        private readonly StripeClient $stripe,
        private readonly StripeCustomerService $customers,
    ) {}

    public function startCheckout(User $manager, SubscriptionPlan $plan): string
    {
        $this->assertNoActiveSubscription($manager);

        if (empty($plan->stripe_price_id)) {
            throw ValidationException::withMessages([
                'plan' => ['This plan is not available for subscription.'],
            ]);
        }

        $customerId = $this->customers->getOrCreateCustomer($manager);
        $frontendUrl = rtrim((string) config('app.frontend_url'), '/');
        $returnUrl = $frontendUrl.'/hosting/subscription';

        $metadata = [
            'user_id' => $manager->id,
            'plan_slug' => $plan->slug,
        ];

        $session = $this->stripe->checkout->sessions->create([
            'mode' => 'subscription',
            'customer' => $customerId,
            'line_items' => [[
                'price' => $plan->stripe_price_id,
                'quantity' => 1,
            ]],
            'subscription_data' => [
                'metadata' => $metadata,
            ],
            'metadata' => $metadata,
            'success_url' => $returnUrl.'?checkout=success&session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => $returnUrl.'?checkout=cancel',
        ]);

        return (string) $session->url;
    }

    public function cancel(Subscription $subscription): Subscription
    {
        if (! $subscription->isEffective()) {
            throw ValidationException::withMessages([
                'subscription' => ['This subscription cannot be canceled.'],
            ]);
        }

        $this->stripe->subscriptions->update($subscription->stripe_subscription_id, [
            'cancel_at_period_end' => true,
        ]);

        $subscription->update([
            'canceled_at' => now(),
            'ends_at' => $subscription->current_period_end,
        ]);

        return $subscription->fresh();
    }

    /**
     * @return array<int, Invoice>
     */
    public function listInvoices(User $manager): array
    {
        $subscription = $manager->subscription()->first();

        if ($subscription === null || empty($subscription->stripe_customer_id)) {
            return [];
        }

        $invoices = $this->stripe->invoices->all([
            'customer' => $subscription->stripe_customer_id,
            'subscription' => $subscription->stripe_subscription_id,
            'limit' => 24,
        ]);

        return $invoices->data;
    }

    private function assertNoActiveSubscription(User $manager): void
    {
        $existing = $manager->subscription()->first();

        if ($existing !== null && $existing->isEffective()) {
            throw ValidationException::withMessages([
                'subscription' => ['You already have an active subscription.'],
            ]);
        }
    }
}
