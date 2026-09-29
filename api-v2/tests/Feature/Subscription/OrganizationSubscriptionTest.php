<?php

declare(strict_types=1);

use App\Enums\OrganizationRoleEnum;
use App\Enums\PlanEnum;
use App\Models\Organization;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use Database\Seeders\Reference\SubscriptionPlanSeeder;

beforeEach(function () {
    $this->seed(SubscriptionPlanSeeder::class);
});

function payablePlan(PlanEnum $plan = PlanEnum::PRO): SubscriptionPlan
{
    $subscriptionPlan = SubscriptionPlan::where('slug', $plan->value)->firstOrFail();
    $subscriptionPlan->update(['stripe_price_id' => 'price_'.$plan->value]);

    return $subscriptionPlan;
}

describe('show', function () {
    it('returns null while the company has never subscribed', function () {
        $organization = Organization::factory()->create();

        $this->withHeaders(asUser($organization->owner))
            ->getJson("/api/organizations/{$organization->id}/subscription")
            ->assertOk()
            ->assertContent('null');
    });

    it('returns the current subscription with its plan', function () {
        $organization = Organization::factory()->create();
        Subscription::factory()->for($organization)->onPlan(PlanEnum::PRO)->create();

        $this->withHeaders(asUser($organization->owner))
            ->getJson("/api/organizations/{$organization->id}/subscription")
            ->assertOk()
            ->assertJsonPath('plan.slug', 'pro')
            ->assertJsonPath('is_effective', true);
    });

    it('forbids a member who cannot manage billing', function () {
        $organization = Organization::factory()->create();

        $this->withHeaders(asUser(memberOf($organization, OrganizationRoleEnum::ACCOUNTANT)))
            ->getJson("/api/organizations/{$organization->id}/subscription")
            ->assertForbidden();
    });
});

describe('checkout', function () {
    it('creates the Stripe customer of the company and returns the checkout URL', function () {
        $organization = Organization::factory()->create();
        payablePlan();
        $this->stripe()
            ->fake('post', '/v1/customers', ['object' => 'customer', 'id' => 'cus_org'])
            ->fake('post', '/v1/checkout/sessions', ['object' => 'checkout.session', 'id' => 'cs_1', 'url' => 'https://checkout.stripe.test/cs_1']);

        $this->withHeaders(asUser($organization->owner))
            ->postJson("/api/organizations/{$organization->id}/subscription/checkout", ['plan_slug' => 'pro'])
            ->assertOk()
            ->assertJsonPath('checkout_url', 'https://checkout.stripe.test/cs_1');

        expect($organization->fresh()->stripe_customer_id)->toBe('cus_org');
        $this->stripe()->assertSent('post', '/v1/checkout/sessions', fn (array $params): bool => $params['customer'] === 'cus_org'
            && $params['line_items'][0]['price'] === 'price_pro'
            && $params['subscription_data']['metadata']['organization_id'] === $organization->id);
    });

    it('refuses a second subscription while one is active', function () {
        $organization = Organization::factory()->create();
        Subscription::factory()->for($organization)->create();
        payablePlan();

        $this->withHeaders(asUser($organization->owner))
            ->postJson("/api/organizations/{$organization->id}/subscription/checkout", ['plan_slug' => 'pro'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['plan_slug' => __('subscription.already_active')]);
    });

    it('refuses a plan without a Stripe price', function () {
        $organization = Organization::factory()->create();

        $this->withHeaders(asUser($organization->owner))
            ->postJson("/api/organizations/{$organization->id}/subscription/checkout", ['plan_slug' => 'free'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['plan_slug' => __('subscription.plan_unavailable')]);
    });
});

describe('cancel', function () {
    it('cancels at the end of the paid period', function () {
        $organization = Organization::factory()->create();
        $subscription = Subscription::factory()->for($organization)->create();
        $this->stripe()->fake('post', '/v1/subscriptions/*', ['object' => 'subscription', 'id' => $subscription->stripe_subscription_id]);

        $this->withHeaders(asUser($organization->owner))
            ->deleteJson("/api/organizations/{$organization->id}/subscription")
            ->assertOk()
            ->assertJsonPath('is_effective', true)
            ->assertJsonPath('ends_at', $subscription->current_period_end->toISOString());

        $this->stripe()->assertSent('post', "/v1/subscriptions/{$subscription->stripe_subscription_id}", fn (array $params): bool => $params['cancel_at_period_end'] === 'true');
    });

    it('refuses to cancel a subscription already ending', function () {
        $organization = Organization::factory()->create();
        Subscription::factory()->for($organization)->canceling()->create();

        $this->withHeaders(asUser($organization->owner))
            ->deleteJson("/api/organizations/{$organization->id}/subscription")
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['subscription' => __('subscription.not_cancelable')]);

        $this->stripe()->assertNotSent('post', '/v1/subscriptions/*');
    });
});

describe('invoices', function () {
    it('lists the Stripe invoices of the subscription', function () {
        $organization = Organization::factory()->create();
        $organization->forceFill(['stripe_customer_id' => 'cus_org'])->save();
        Subscription::factory()->for($organization)->create();
        $this->stripe()->fake('get', '/v1/invoices', ['object' => 'list', 'data' => [[
            'object' => 'invoice',
            'id' => 'in_1',
            'number' => 'KEN-0001',
            'status' => 'paid',
            'amount_paid' => 5900,
            'amount_due' => 5900,
            'currency' => 'eur',
            'created' => 1_790_000_000,
            'invoice_pdf' => 'https://stripe.test/in_1.pdf',
            'hosted_invoice_url' => 'https://stripe.test/in_1',
        ]]]);

        $this->withHeaders(asUser($organization->owner))
            ->getJson("/api/organizations/{$organization->id}/subscription/invoices")
            ->assertOk()
            ->assertJsonPath('0.amount_paid', '59.00')
            ->assertJsonPath('0.currency', 'EUR');
    });
});
