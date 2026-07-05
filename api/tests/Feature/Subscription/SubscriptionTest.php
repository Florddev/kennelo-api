<?php

declare(strict_types=1);

use App\Enums\FinancialOperationTypeEnum;
use App\Enums\PlanEnum;
use App\Enums\SubscriptionStatusEnum;
use App\Models\Activity;
use App\Models\FinancialOperation;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\Stripe\StripeWebhookService;
use Database\Seeders\SubscriptionPlanSeeder;
use Stripe\Event;
use Stripe\StripeClient;
use Tests\Support\FakeStripeClient;

function seedPlans(): void
{
    (new SubscriptionPlanSeeder)->run();
}

function fakeSubscriptionStripe(): FakeStripeClient
{
    $fake = new FakeStripeClient;
    app()->instance(StripeClient::class, $fake);

    return $fake;
}

function makeHostActivity(): array
{
    $manager = User::factory()->create();
    $activity = Activity::factory()->create([
        'manager_id' => $manager->id,
        'is_active' => true,
    ]);

    return [$manager, $activity];
}

function proPlan(): SubscriptionPlan
{
    return SubscriptionPlan::where('slug', PlanEnum::PRO->value)->firstOrFail();
}

function dispatchWebhook(array $type, array $object): void
{
    $event = Event::constructFrom([
        'id' => 'evt_'.uniqid(),
        'type' => $type['type'],
        'data' => ['object' => $object],
    ]);

    app(StripeWebhookService::class)->handleEvent($event);
}

beforeEach(function () {
    seedPlans();
});

it('lists the available plans', function () {
    [$manager] = makeHostActivity();

    $this->withHeaders(asUser($manager))
        ->getJson('/api/plans')
        ->assertOk()
        ->assertJsonCount(3, 'data');
});

it('returns the free plan when an activity has no subscription', function () {
    [$manager, $activity] = makeHostActivity();

    $this->withHeaders(asUser($manager))
        ->getJson("/api/activities/{$activity->id}/subscription")
        ->assertOk()
        ->assertJsonPath('data.plan', 'free');
});

it('starts a checkout session and returns the url', function () {
    $fake = fakeSubscriptionStripe();
    [$manager, $activity] = makeHostActivity();
    proPlan()->update(['stripe_price_id' => 'price_pro_test']);

    $this->withHeaders(asUser($manager))
        ->postJson("/api/activities/{$activity->id}/subscription/checkout", ['plan_slug' => 'pro'])
        ->assertOk()
        ->assertJsonStructure(['data' => ['checkout_url']]);

    expect($fake->called('checkout.sessions.create'))->toBeTrue();
});

it('prevents double subscription when one is already active', function () {
    fakeSubscriptionStripe();
    [$manager, $activity] = makeHostActivity();
    proPlan()->update(['stripe_price_id' => 'price_pro_test']);

    Subscription::create([
        'activity_id' => $activity->id,
        'subscription_plan_id' => proPlan()->id,
        'stripe_subscription_id' => 'sub_existing',
        'stripe_customer_id' => 'cus_existing',
        'status' => SubscriptionStatusEnum::ACTIVE,
    ]);

    $this->withHeaders(asUser($manager))
        ->postJson("/api/activities/{$activity->id}/subscription/checkout", ['plan_slug' => 'pro'])
        ->assertStatus(422);
});

it('forbids a non-manager from starting a checkout', function () {
    fakeSubscriptionStripe();
    [, $activity] = makeHostActivity();
    proPlan()->update(['stripe_price_id' => 'price_pro_test']);
    $stranger = User::factory()->create();

    $this->withHeaders(asUser($stranger))
        ->postJson("/api/activities/{$activity->id}/subscription/checkout", ['plan_slug' => 'pro'])
        ->assertForbidden();
});

it('activates a subscription from the created webhook', function () {
    [, $activity] = makeHostActivity();

    dispatchWebhook(
        ['type' => 'customer.subscription.created'],
        subscriptionObject($activity, 'active'),
    );

    $subscription = Subscription::where('activity_id', $activity->id)->first();

    expect($subscription)->not->toBeNull()
        ->and($subscription->status)->toBe(SubscriptionStatusEnum::ACTIVE)
        ->and($activity->fresh()->effectivePlan())->toBe(PlanEnum::PRO);
});

it('is idempotent when the same subscription event is processed twice', function () {
    [, $activity] = makeHostActivity();

    dispatchWebhook(['type' => 'customer.subscription.created'], subscriptionObject($activity, 'active'));
    dispatchWebhook(['type' => 'customer.subscription.updated'], subscriptionObject($activity, 'active'));

    expect(Subscription::where('activity_id', $activity->id)->count())->toBe(1);
});

it('records a subscription payment on invoice.paid', function () {
    [, $activity] = makeHostActivity();
    dispatchWebhook(['type' => 'customer.subscription.created'], subscriptionObject($activity, 'active'));

    dispatchWebhook(['type' => 'invoice.paid'], [
        'object' => 'invoice',
        'id' => 'in_test_1',
        'subscription' => 'sub_test_'.$activity->id,
        'amount_paid' => 5900,
        'currency' => 'eur',
        'payment_intent' => 'pi_sub_1',
        'invoice_pdf' => 'https://invoice.test/pdf',
    ]);

    expect(SubscriptionPayment::where('stripe_invoice_id', 'in_test_1')->exists())->toBeTrue()
        ->and(FinancialOperation::where('type', FinancialOperationTypeEnum::SUBSCRIPTION_PAYMENT)->exists())->toBeTrue();
});

it('degrades to free and applies downgrade on subscription deletion', function () {
    [, $activity] = makeHostActivity();
    dispatchWebhook(['type' => 'customer.subscription.created'], subscriptionObject($activity, 'active'));

    expect($activity->fresh()->effectivePlan())->toBe(PlanEnum::PRO);

    dispatchWebhook(['type' => 'customer.subscription.deleted'], subscriptionObject($activity, 'canceled'));

    expect($activity->fresh()->effectivePlan())->toBe(PlanEnum::FREE);
});

it('cancels a subscription at period end', function () {
    $fake = fakeSubscriptionStripe();
    [$manager, $activity] = makeHostActivity();

    Subscription::create([
        'activity_id' => $activity->id,
        'subscription_plan_id' => proPlan()->id,
        'stripe_subscription_id' => 'sub_cancel',
        'stripe_customer_id' => 'cus_cancel',
        'status' => SubscriptionStatusEnum::ACTIVE,
        'current_period_end' => now()->addMonth(),
    ]);

    $this->withHeaders(asUser($manager))
        ->deleteJson("/api/activities/{$activity->id}/subscription")
        ->assertOk();

    expect($fake->called('subscriptions.update'))->toBeTrue()
        ->and(Subscription::where('activity_id', $activity->id)->first()->canceled_at)->not->toBeNull();
});

function subscriptionObject(Activity $activity, string $status): array
{
    return [
        'object' => 'subscription',
        'id' => 'sub_test_'.$activity->id,
        'status' => $status,
        'customer' => 'cus_test_'.$activity->id,
        'cancel_at_period_end' => false,
        'current_period_start' => now()->subDay()->timestamp,
        'current_period_end' => now()->addMonth()->timestamp,
        'trial_end' => null,
        'canceled_at' => null,
        'metadata' => [
            'activity_id' => $activity->id,
            'plan_slug' => 'pro',
        ],
        'items' => [
            'data' => [[
                'price' => ['id' => 'price_pro_test'],
            ]],
        ],
    ];
}
