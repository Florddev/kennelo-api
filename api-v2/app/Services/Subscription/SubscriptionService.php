<?php

declare(strict_types=1);

namespace App\Services\Subscription;

use App\Models\Organization;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Services\Stripe\StripeCustomerService;
use Illuminate\Validation\ValidationException;
use Stripe\Invoice;
use Stripe\StripeClient;

/**
 * Abonnement d'une entreprise. La souscription passe par Stripe Checkout ; l'abonnement lui-même
 * est enregistré par le webhook, qui reste la seule source de vérité sur son état.
 */
class SubscriptionService
{
    private const int INVOICES_LIMIT = 24;

    public function __construct(
        private readonly StripeClient $stripe,
        private readonly StripeCustomerService $customers,
    ) {}

    /**
     * Retourne l'URL de la page de paiement Stripe.
     */
    public function startCheckout(Organization $organization, SubscriptionPlan $plan): string
    {
        if ($organization->subscription?->isEffective()) {
            throw ValidationException::withMessages(['plan_slug' => __('subscription.already_active')]);
        }

        if (blank($plan->stripe_price_id) || ! $plan->is_active) {
            throw ValidationException::withMessages(['plan_slug' => __('subscription.plan_unavailable')]);
        }

        $returnUrl = rtrim((string) config('app.frontend_url'), '/').'/hosting/subscription?'.http_build_query([
            'organization' => $organization->id,
        ]);

        // Les métadonnées de l'abonnement permettent au webhook de retrouver l'entreprise et l'offre.
        $metadata = [
            'organization_id' => $organization->id,
            'plan_slug' => $plan->slug,
        ];

        $session = $this->stripe->checkout->sessions->create([
            'mode' => 'subscription',
            'customer' => $this->customers->getOrCreateOrganizationCustomer($organization),
            'line_items' => [[
                'price' => $plan->stripe_price_id,
                'quantity' => 1,
            ]],
            'subscription_data' => ['metadata' => $metadata],
            'metadata' => $metadata,
            'success_url' => $returnUrl.'&checkout=success',
            'cancel_url' => $returnUrl.'&checkout=cancel',
        ]);

        return (string) $session->url;
    }

    /**
     * Résilie à la fin de la période payée : l'offre reste active jusque-là.
     */
    public function cancel(Organization $organization): Subscription
    {
        $subscription = $organization->subscription;

        if ($subscription === null || ! $subscription->isEffective() || $subscription->isCanceling()) {
            throw ValidationException::withMessages(['subscription' => __('subscription.not_cancelable')]);
        }

        $this->stripe->subscriptions->update($subscription->stripe_subscription_id, [
            'cancel_at_period_end' => true,
        ]);

        $subscription->update([
            'canceled_at' => now(),
            'ends_at' => $subscription->current_period_end,
        ]);

        return $subscription->load('plan');
    }

    /**
     * @return list<Invoice>
     */
    public function invoices(Organization $organization): array
    {
        $subscription = $organization->subscription;

        if ($subscription === null || blank($organization->stripe_customer_id)) {
            return [];
        }

        return $this->stripe->invoices->all([
            'customer' => $organization->stripe_customer_id,
            'subscription' => $subscription->stripe_subscription_id,
            'limit' => self::INVOICES_LIMIT,
        ])->data;
    }
}
