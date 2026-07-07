<?php

declare(strict_types=1);

use App\Enums\PlanEnum;
use App\Enums\SubscriptionStatusEnum;
use App\Models\Activity;
use App\Models\ActivityCycle;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\Activity\ActivityCycleService;
use App\Services\Activity\ActivityService;
use App\Services\Subscription\PlanLimitService;
use Database\Seeders\SubscriptionPlanSeeder;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    (new SubscriptionPlanSeeder)->run();
});

function subscribeUser(User $user, PlanEnum $plan): Subscription
{
    return Subscription::create([
        'user_id' => $user->id,
        'subscription_plan_id' => SubscriptionPlan::where('slug', $plan->value)->firstOrFail()->id,
        'stripe_subscription_id' => 'sub_'.uniqid(),
        'stripe_customer_id' => 'cus_'.uniqid(),
        'status' => SubscriptionStatusEnum::ACTIVE,
    ]);
}

it('applies the free commission rate when the manager has no subscription', function () {
    $activity = Activity::factory()->create();

    expect($activity->effectivePlan()->commissionRate())->toBe('0.08');
});

it('applies the pro commission rate when the manager has an active pro subscription', function () {
    $manager = User::factory()->create();
    $activity = Activity::factory()->create(['manager_id' => $manager->id]);
    subscribeUser($manager, PlanEnum::PRO);

    expect($activity->fresh()->effectivePlan()->commissionRate())->toBe('0.00');
});

it('reverts to the free rate when the subscription is not effective', function () {
    $manager = User::factory()->create();
    $activity = Activity::factory()->create(['manager_id' => $manager->id]);
    subscribeUser($manager, PlanEnum::PRO);
    $manager->subscription()->update(['status' => SubscriptionStatusEnum::CANCELED]);

    expect($activity->fresh()->effectivePlan()->commissionRate())->toBe('0.08');
});

it('blocks creating a second activity on the free plan', function () {
    $manager = User::factory()->create();
    Activity::factory()->create(['manager_id' => $manager->id]);

    app(ActivityService::class)->create($manager, ['name' => 'Second']);
})->throws(ValidationException::class);

it('allows unlimited activities on the pro plan', function () {
    $manager = User::factory()->create();
    Activity::factory()->create(['manager_id' => $manager->id]);
    subscribeUser($manager, PlanEnum::PRO);

    $second = app(ActivityService::class)->create($manager, ['name' => 'Second']);

    expect($second)->toBeInstanceOf(Activity::class);
});

it('blocks creating more cycles than the free plan allows', function () {
    $activity = Activity::factory()->create();

    for ($i = 0; $i < 3; $i++) {
        ActivityCycle::create([
            'activity_id' => $activity->id,
            'is_active' => true,
            'priority' => $i,
        ]);
    }

    app(ActivityCycleService::class)->createCycle($activity, []);
})->throws(ValidationException::class);

it('exposes the max photos limit per plan', function () {
    $manager = User::factory()->create();
    $activity = Activity::factory()->create(['manager_id' => $manager->id]);

    expect(app(PlanLimitService::class)->maxPhotos($activity))->toBe(5);

    subscribeUser($manager, PlanEnum::PRO);

    expect(app(PlanLimitService::class)->maxPhotos($activity->fresh()))->toBe(-1);
});
