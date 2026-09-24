<?php

declare(strict_types=1);

use App\Enums\PlanEnum;
use App\Enums\SubscriptionPaymentStatusEnum;
use App\Enums\SubscriptionStatusEnum;
use App\Models\Organization;
use App\Models\StripeEvent;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Notifications\AppNotification;
use Database\Seeders\Reference\SubscriptionPlanSeeder;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;

const WEBHOOK_SECRET = 'whsec_test';

/**
 * Envoie un événement signé comme le fait Stripe.
 *
 * @param  array<string, mixed>  $object
 */
function postStripeEvent(string $type, array $object, ?string $id = null): TestResponse
{
    config(['services.stripe.webhook_secret' => WEBHOOK_SECRET]);

    $payload = (string) json_encode([
        'id' => $id ?? 'evt_'.Str::random(12),
        'object' => 'event',
        'type' => $type,
        'data' => ['object' => $object],
    ]);
    $timestamp = time();
    $signature = hash_hmac('sha256', "{$timestamp}.{$payload}", WEBHOOK_SECRET);

    return test()->call('POST', '/api/webhooks/stripe', server: [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_STRIPE_SIGNATURE' => "t={$timestamp},v1={$signature}",
    ], content: $payload);
}

/**
 * Abonnement tel que Stripe le décrit depuis l'API « basil » : les dates de période sont sur les lignes.
 *
 * @return array<string, mixed>
 */
function stripeSubscription(Organization $organization, string $status, array $overrides = []): array
{
    return array_merge([
        'object' => 'subscription',
        'id' => 'sub_org',
        'status' => $status,
        'cancel_at_period_end' => false,
        'canceled_at' => null,
        'ended_at' => null,
        'trial_end' => null,
        'metadata' => ['organization_id' => $organization->id, 'plan_slug' => 'pro'],
        'items' => ['object' => 'list', 'data' => [[
            'object' => 'subscription_item',
            'current_period_start' => now()->subDay()->timestamp,
            'current_period_end' => now()->addMonth()->timestamp,
            'price' => ['object' => 'price', 'id' => 'price_pro'],
        ]]],
    ], $overrides);
}

describe('signature', function () {
    it('refuses an event without a valid signature', function () {
        config(['services.stripe.webhook_secret' => WEBHOOK_SECRET]);

        $this->call('POST', '/api/webhooks/stripe', server: [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_STRIPE_SIGNATURE' => 't=1,v1=forged',
        ], content: '{"id":"evt_1","object":"event","type":"account.updated","data":{"object":{}}}')
            ->assertBadRequest();

        expect(StripeEvent::count())->toBe(0);
    });
});

describe('Connect accounts', function () {
    it('updates the company and notifies the owner once onboarding is complete', function () {
        Notification::fake();
        $organization = Organization::factory()->create();
        $organization->forceFill(['stripe_account_id' => 'acct_org'])->save();

        postStripeEvent('account.updated', [
            'object' => 'account',
            'id' => 'acct_org',
            'charges_enabled' => true,
            'payouts_enabled' => true,
            'details_submitted' => true,
        ])->assertNoContent();

        $organization->refresh();

        expect($organization->stripe_charges_enabled)->toBeTrue()
            ->and($organization->stripe_payouts_enabled)->toBeTrue()
            ->and($organization->stripe_onboarding_completed)->toBeTrue();
        Notification::assertSentTo($organization->owner, AppNotification::class);
    });

    it('processes an event delivered twice only once', function () {
        Notification::fake();
        $organization = Organization::factory()->create();
        $organization->forceFill(['stripe_account_id' => 'acct_org'])->save();
        $account = ['object' => 'account', 'id' => 'acct_org', 'charges_enabled' => true, 'payouts_enabled' => true, 'details_submitted' => true];

        postStripeEvent('account.updated', $account, 'evt_same')->assertNoContent();
        $organization->forceFill(['stripe_onboarding_completed' => false])->save();
        postStripeEvent('account.updated', $account, 'evt_same')->assertNoContent();

        expect($organization->fresh()->stripe_onboarding_completed)->toBeFalse()
            ->and(StripeEvent::count())->toBe(1);
        Notification::assertSentToTimes($organization->owner, AppNotification::class, 1);
    });
});

describe('subscriptions', function () {
    beforeEach(function () {
        $this->seed(SubscriptionPlanSeeder::class);
    });

    it('records a new subscription from its metadata and activates the plan', function () {
        Notification::fake();
        $organization = Organization::factory()->create();

        postStripeEvent('customer.subscription.created', stripeSubscription($organization, 'active'))->assertNoContent();

        $subscription = $organization->subscription()->with('plan')->firstOrFail();

        expect($subscription->status)->toBe(SubscriptionStatusEnum::ACTIVE)
            ->and($subscription->plan->slug)->toBe('pro')
            ->and($subscription->current_period_end)->not->toBeNull()
            ->and($organization->fresh()->effectivePlan())->toBe(PlanEnum::PRO);
        Notification::assertSentTo($organization->owner, AppNotification::class);
    });

    it('falls back to the free plan when the subscription ends', function () {
        Notification::fake();
        $organization = Organization::factory()->create();
        Subscription::factory()->for($organization)->onPlan(PlanEnum::PRO)->create(['stripe_subscription_id' => 'sub_org']);

        postStripeEvent('customer.subscription.deleted', stripeSubscription($organization, 'canceled', [
            'ended_at' => now()->timestamp,
        ]))->assertNoContent();

        expect($organization->fresh()->effectivePlan())->toBe(PlanEnum::FREE)
            ->and($organization->subscription()->firstOrFail()->ends_at)->not->toBeNull();
        Notification::assertSentTo($organization->owner, AppNotification::class);
    });

    it('ignores a subscription whose company is unknown', function () {
        postStripeEvent('customer.subscription.created', [
            ...stripeSubscription(Organization::factory()->create(), 'active'),
            'metadata' => ['organization_id' => (string) Str::uuid(), 'plan_slug' => 'pro'],
        ])->assertNoContent();

        expect(Subscription::count())->toBe(0);
    });

    it('records a paid invoice of the subscription', function () {
        $subscription = Subscription::factory()->create(['stripe_subscription_id' => 'sub_org']);

        postStripeEvent('invoice.paid', [
            'object' => 'invoice',
            'id' => 'in_1',
            'amount_paid' => 5900,
            'amount_due' => 5900,
            'currency' => 'eur',
            'invoice_pdf' => 'https://stripe.test/in_1.pdf',
            'parent' => ['type' => 'subscription_details', 'subscription_details' => ['subscription' => 'sub_org']],
        ])->assertNoContent();

        $payment = SubscriptionPayment::sole();

        expect($payment->subscription_id)->toBe($subscription->id)
            ->and($payment->amount)->toBe('59.00')
            ->and($payment->status)->toBe(SubscriptionPaymentStatusEnum::PAID);
    });

    it('records a failed invoice and notifies the owner', function () {
        Notification::fake();
        $subscription = Subscription::factory()->create(['stripe_subscription_id' => 'sub_org']);

        postStripeEvent('invoice.payment_failed', [
            'object' => 'invoice',
            'id' => 'in_2',
            'amount_paid' => 0,
            'amount_due' => 5900,
            'currency' => 'eur',
            'parent' => ['type' => 'subscription_details', 'subscription_details' => ['subscription' => 'sub_org']],
        ])->assertNoContent();

        expect(SubscriptionPayment::sole()->status)->toBe(SubscriptionPaymentStatusEnum::FAILED);
        Notification::assertSentTo($subscription->organization->owner, AppNotification::class);
    });
});
