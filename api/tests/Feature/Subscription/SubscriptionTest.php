<?php

declare(strict_types=1);

use App\Enums\FinancialOperationTypeEnum;
use App\Enums\PlanEnum;
use App\Enums\SubscriptionStatusEnum;
use App\Models\Activity;
use App\Models\ActivityCycle;
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

function proPlan(): SubscriptionPlan
{
    return SubscriptionPlan::where('slug', PlanEnum::PRO->value)->firstOrFail();
}

function dispatchWebhook(string $type, array $object): void
{
    $event = Event::constructFrom([
        'id' => 'evt_'.uniqid(),
        'type' => $type,
        'data' => ['object' => $object],
    ]);

    app(StripeWebhookService::class)->handleEvent($event);
}

function subscriptionObject(User $user, string $status): array
{
    return [
        'object' => 'subscription',
        'id' => 'sub_test_'.$user->id,
        'status' => $status,
        'customer' => 'cus_test_'.$user->id,
        'cancel_at_period_end' => false,
        'current_period_start' => now()->subDay()->timestamp,
        'current_period_end' => now()->addMonth()->timestamp,
        'trial_end' => null,
        'canceled_at' => null,
        'metadata' => [
            'user_id' => $user->id,
            'plan_slug' => 'pro',
        ],
        'items' => [
            'data' => [[
                'price' => ['id' => 'price_pro_test'],
            ]],
        ],
    ];
}

beforeEach(function () {
    seedPlans();
});

it('lists the available plans', function () {
    $manager = User::factory()->create();

    $this->withHeaders(asUser($manager))
        ->getJson('/api/plans')
        ->assertOk()
        ->assertJsonCount(3, 'data');
});

it('returns the free plan when the user has no subscription', function () {
    $manager = User::factory()->create();

    $this->withHeaders(asUser($manager))
        ->getJson('/api/me/subscription')
        ->assertOk()
        ->assertJsonPath('data.plan', 'free');
});

it('starts a checkout session and returns the url', function () {
    $fake = fakeSubscriptionStripe();
    $manager = User::factory()->create();
    proPlan()->update(['stripe_price_id' => 'price_pro_test']);

    $this->withHeaders(asUser($manager))
        ->postJson('/api/me/subscription/checkout', ['plan_slug' => 'pro'])
        ->assertOk()
        ->assertJsonStructure(['data' => ['checkout_url']]);

    expect($fake->called('checkout.sessions.create'))->toBeTrue();
});

it('prevents double subscription when one is already active', function () {
    fakeSubscriptionStripe();
    $manager = User::factory()->create();
    proPlan()->update(['stripe_price_id' => 'price_pro_test']);

    Subscription::create([
        'user_id' => $manager->id,
        'subscription_plan_id' => proPlan()->id,
        'stripe_subscription_id' => 'sub_existing',
        'stripe_customer_id' => 'cus_existing',
        'status' => SubscriptionStatusEnum::ACTIVE,
    ]);

    $this->withHeaders(asUser($manager))
        ->postJson('/api/me/subscription/checkout', ['plan_slug' => 'pro'])
        ->assertStatus(422);
});

it('only exposes the current user subscription', function () {
    $manager = User::factory()->create();
    $other = User::factory()->create();

    Subscription::create([
        'user_id' => $other->id,
        'subscription_plan_id' => proPlan()->id,
        'stripe_subscription_id' => 'sub_other',
        'stripe_customer_id' => 'cus_other',
        'status' => SubscriptionStatusEnum::ACTIVE,
    ]);

    $this->withHeaders(asUser($manager))
        ->getJson('/api/me/subscription')
        ->assertOk()
        ->assertJsonPath('data.plan', 'free');
});

it('activates a subscription from the created webhook', function () {
    $manager = User::factory()->create();
    $activity = Activity::factory()->create(['manager_id' => $manager->id]);

    dispatchWebhook('customer.subscription.created', subscriptionObject($manager, 'active'));

    $subscription = Subscription::where('user_id', $manager->id)->first();

    expect($subscription)->not->toBeNull()
        ->and($subscription->status)->toBe(SubscriptionStatusEnum::ACTIVE)
        ->and($manager->fresh()->effectivePlan())->toBe(PlanEnum::PRO)
        ->and($activity->fresh()->effectivePlan())->toBe(PlanEnum::PRO);
});

it('is idempotent when the same subscription event is processed twice', function () {
    $manager = User::factory()->create();

    dispatchWebhook('customer.subscription.created', subscriptionObject($manager, 'active'));
    dispatchWebhook('customer.subscription.updated', subscriptionObject($manager, 'active'));

    expect(Subscription::where('user_id', $manager->id)->count())->toBe(1);
});

it('records a subscription payment on invoice.paid', function () {
    $manager = User::factory()->create();
    dispatchWebhook('customer.subscription.created', subscriptionObject($manager, 'active'));

    dispatchWebhook('invoice.paid', [
        'object' => 'invoice',
        'id' => 'in_test_1',
        'subscription' => 'sub_test_'.$manager->id,
        'amount_paid' => 5900,
        'currency' => 'eur',
        'payment_intent' => 'pi_sub_1',
        'invoice_pdf' => 'https://invoice.test/pdf',
    ]);

    expect(SubscriptionPayment::where('stripe_invoice_id', 'in_test_1')->exists())->toBeTrue()
        ->and(FinancialOperation::where('type', FinancialOperationTypeEnum::SUBSCRIPTION_PAYMENT)->exists())->toBeTrue();
});

it('degrades to free on subscription deletion', function () {
    $manager = User::factory()->create();
    dispatchWebhook('customer.subscription.created', subscriptionObject($manager, 'active'));

    expect($manager->fresh()->effectivePlan())->toBe(PlanEnum::PRO);

    dispatchWebhook('customer.subscription.deleted', subscriptionObject($manager, 'canceled'));

    expect($manager->fresh()->effectivePlan())->toBe(PlanEnum::FREE);
});

it('soft-disables surplus cycles across all activities on downgrade', function () {
    config(['plans.downgrade.soft_disable.cycles' => true]);

    $manager = User::factory()->create();
    dispatchWebhook('customer.subscription.created', subscriptionObject($manager, 'active'));

    $activities = Activity::factory()->count(2)->create(['manager_id' => $manager->id]);

    foreach ($activities as $activity) {
        for ($i = 0; $i < 5; $i++) {
            ActivityCycle::create([
                'activity_id' => $activity->id,
                'is_active' => true,
                'priority' => $i,
            ]);
        }
    }

    dispatchWebhook('customer.subscription.deleted', subscriptionObject($manager, 'canceled'));

    foreach ($activities as $activity) {
        expect(ActivityCycle::where('activity_id', $activity->id)->where('is_active', true)->count())->toBe(3);
    }
});

it('cancels a subscription at period end', function () {
    $fake = fakeSubscriptionStripe();
    $manager = User::factory()->create();

    Subscription::create([
        'user_id' => $manager->id,
        'subscription_plan_id' => proPlan()->id,
        'stripe_subscription_id' => 'sub_cancel',
        'stripe_customer_id' => 'cus_cancel',
        'status' => SubscriptionStatusEnum::ACTIVE,
        'current_period_end' => now()->addMonth(),
    ]);

    $this->withHeaders(asUser($manager))
        ->deleteJson('/api/me/subscription')
        ->assertOk();

    expect($fake->called('subscriptions.update'))->toBeTrue()
        ->and(Subscription::where('user_id', $manager->id)->first()->canceled_at)->not->toBeNull();
});
