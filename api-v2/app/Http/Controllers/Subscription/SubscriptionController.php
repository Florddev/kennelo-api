<?php

declare(strict_types=1);

namespace App\Http\Controllers\Subscription;

use App\Http\Controllers\Controller;
use App\Http\Requests\Subscription\StartSubscriptionCheckoutRequest;
use App\Http\Resources\SubscriptionInvoiceResource;
use App\Http\Resources\SubscriptionPlanResource;
use App\Http\Resources\SubscriptionResource;
use App\Models\Organization;
use App\Models\SubscriptionPlan;
use App\Services\Subscription\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @tags Subscriptions
 */
class SubscriptionController extends Controller
{
    public function __construct(
        private readonly SubscriptionService $subscriptions,
    ) {}

    public function plans(): AnonymousResourceCollection
    {
        return SubscriptionPlanResource::collection(
            SubscriptionPlan::where('is_active', true)->orderByDesc('commission_rate')->get()
        );
    }

    /**
     * Show the organization's subscription
     *
     * data vaut null tant que l'entreprise n'a jamais souscrit : elle est alors sur l'offre gratuite.
     */
    public function show(Organization $organization): SubscriptionResource|JsonResponse
    {
        $this->authorize('manageBilling', $organization);

        $subscription = $organization->subscription?->load('plan');

        return $subscription === null
            ? response()->json(['data' => null])
            : new SubscriptionResource($subscription);
    }

    /**
     * Start a checkout
     *
     * Retourne l'URL de paiement Stripe. L'abonnement est enregistré à la réception du webhook.
     */
    public function checkout(StartSubscriptionCheckoutRequest $request, Organization $organization): JsonResponse
    {
        $this->authorize('manageBilling', $organization);

        $plan = SubscriptionPlan::where('slug', $request->validated('plan_slug'))->firstOrFail();

        return response()->json(['data' => [
            'checkout_url' => $this->subscriptions->startCheckout($organization, $plan),
        ]]);
    }

    /**
     * Cancel the subscription
     *
     * L'offre reste active jusqu'à la fin de la période payée.
     */
    public function destroy(Organization $organization): SubscriptionResource
    {
        $this->authorize('manageBilling', $organization);

        return new SubscriptionResource($this->subscriptions->cancel($organization));
    }

    /**
     * List subscription invoices
     *
     * Factures émises par Stripe Billing.
     */
    public function invoices(Organization $organization): AnonymousResourceCollection
    {
        $this->authorize('manageBilling', $organization);

        return SubscriptionInvoiceResource::collection($this->subscriptions->invoices($organization));
    }
}
