<?php

declare(strict_types=1);

use App\Models\SubscriptionPlan;
use App\Models\User;
use Database\Seeders\Reference\SubscriptionPlanSeeder;

beforeEach(function () {
    (new SubscriptionPlanSeeder)->run();
});

it('forbids non-admin from listing subscription plans', function () {
    $user = User::factory()->create();

    $this->withHeaders(asUser($user))
        ->getJson('/api/admin/subscription-plans')
        ->assertForbidden();
});

it('lists all subscription plans for an admin', function () {
    $admin = adminUser();

    $this->withHeaders(asUser($admin))
        ->getJson('/api/admin/subscription-plans')
        ->assertOk()
        ->assertJsonCount(3);
});

it('updates the commission rate and limits of a plan', function () {
    $admin = adminUser();
    $plan = SubscriptionPlan::where('slug', 'pro')->firstOrFail();

    $this->withHeaders(asUser($admin))
        ->putJson("/api/admin/subscription-plans/{$plan->id}", [
            'commission_rate' => '0.02',
            'limits' => ['max_activities' => 10, 'max_cycles_per_activity' => -1, 'max_photos' => 50],
            'is_active' => true,
        ])
        ->assertOk()
        ->assertJsonPath('commission_rate', '0.0200');

    $plan->refresh();

    expect($plan->commission_rate)->toBe('0.0200');
    expect($plan->limit('max_activities'))->toBe(10);
});

it('does not allow changing the slug or stripe ids', function () {
    $admin = adminUser();
    $plan = SubscriptionPlan::where('slug', 'starter')->firstOrFail();
    $originalSlug = $plan->slug;
    $originalPriceId = $plan->stripe_price_id;

    $this->withHeaders(asUser($admin))
        ->putJson("/api/admin/subscription-plans/{$plan->id}", [
            'slug' => 'hacked',
            'stripe_price_id' => 'price_hacked',
            'name' => 'Starter renamed',
        ])
        ->assertOk();

    $plan->refresh();

    expect($plan->slug)->toBe($originalSlug);
    expect($plan->stripe_price_id)->toBe($originalPriceId);
    expect($plan->name)->toBe('Starter renamed');
});

it('rejects a commission rate above 1', function () {
    $admin = adminUser();
    $plan = SubscriptionPlan::where('slug', 'pro')->firstOrFail();

    $this->withHeaders(asUser($admin))
        ->putJson("/api/admin/subscription-plans/{$plan->id}", [
            'commission_rate' => '1.5',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['commission_rate']);
});
