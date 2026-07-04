<?php

declare(strict_types=1);

use App\Enums\BookingStatusEnum;
use App\Enums\WeekDayEnum;
use App\Models\Activity;
use App\Models\ActivityCycle;
use App\Models\ActivityCycleSetting;
use App\Models\AnimalType;
use App\Models\Booking;
use App\Models\Pet;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Stripe\StripeClient;
use Tests\Support\FailingPaymentStripeClient;
use Tests\Support\FakeStripeClient;

function bookingFixesFixtures(int $maxCapacity = 5): array
{
    $manager = User::factory()->create();
    $activity = Activity::factory()->create([
        'manager_id' => $manager->id,
        'is_active' => true,
        'stripe_account_id' => 'acct_test_'.uniqid(),
        'stripe_charges_enabled' => true,
        'stripe_payouts_enabled' => true,
        'stripe_onboarding_completed' => true,
    ]);
    $animalType = AnimalType::create(['code' => 'dog_'.uniqid(), 'name' => 'Chien', 'category' => 'mammals']);

    $cycle = ActivityCycle::create([
        'activity_id' => $activity->id,
        'is_active' => true,
        'priority' => 0,
        'start_date' => null,
        'end_date' => null,
    ]);

    $setting = ActivityCycleSetting::create([
        'activity_cycle_id' => $cycle->id,
        'animal_type_id' => $animalType->id,
        'max_capacity' => $maxCapacity,
    ]);

    foreach (WeekDayEnum::values() as $weekday) {
        $setting->prices()->create(['weekday' => $weekday, 'price' => 30.00]);
    }

    return [$manager, $activity, $animalType];
}

it('refunds the charge when a confirmed booking is cancelled', function () {
    $stripe = new FakeStripeClient;
    app()->instance(StripeClient::class, $stripe);

    $user = User::factory()->create();
    $activity = Activity::factory()->create();
    $booking = Booking::factory()->confirmed()->create([
        'user_id' => $user->id,
        'activity_id' => $activity->id,
        'stripe_charge_id' => 'ch_test_confirmed',
        'payment_status' => 'succeeded',
        'paid_at' => now(),
    ]);

    $this->withHeaders(asUser($user))
        ->putJson("/api/bookings/{$booking->id}/cancel")
        ->assertOk()
        ->assertJsonPath('data.status', BookingStatusEnum::CANCELLED->value);

    expect($stripe->called('refunds.create'))->toBeTrue();
    expect($booking->fresh()->payment_status->value)->toBe('refunded');
});

it('releases the authorization when a pending booking is cancelled', function () {
    $stripe = new FakeStripeClient;
    app()->instance(StripeClient::class, $stripe);

    $user = User::factory()->create();
    $activity = Activity::factory()->create();
    $booking = Booking::factory()->pending()->create([
        'user_id' => $user->id,
        'activity_id' => $activity->id,
        'stripe_payment_intent_id' => 'pi_test_pending',
        'payment_status' => 'requires_capture',
    ]);

    $this->withHeaders(asUser($user))
        ->putJson("/api/bookings/{$booking->id}/cancel")
        ->assertOk()
        ->assertJsonPath('data.status', BookingStatusEnum::CANCELLED->value);

    expect($stripe->called('paymentIntents.cancel'))->toBeTrue();
    expect($booking->fresh()->payment_status->value)->toBe('canceled');
});

it('rejects confirmation when capacity is no longer available', function () {
    app()->instance(StripeClient::class, new FakeStripeClient);

    $user = User::factory()->create();
    [$manager, $activity, $animalType] = bookingFixesFixtures();

    ActivityCycleSetting::whereHas('cycle', fn ($query) => $query->where('activity_id', $activity->id))
        ->where('animal_type_id', $animalType->id)
        ->update(['max_capacity' => 1]);

    $checkIn = now()->addDays(10)->format('Y-m-d');
    $checkOut = now()->addDays(13)->format('Y-m-d');

    $occupant = User::factory()->create();
    $occupantPet = Pet::create(['user_id' => $occupant->id, 'animal_type_id' => $animalType->id, 'name' => 'Filler']);
    $confirmedBooking = Booking::factory()->confirmed()->create([
        'user_id' => $occupant->id,
        'activity_id' => $activity->id,
        'check_in_date' => $checkIn,
        'check_out_date' => $checkOut,
    ]);
    $confirmedBooking->pets()->attach($occupantPet->id, [
        'price_per_night' => '30.00',
        'number_of_nights' => 3,
        'subtotal' => '90.00',
    ]);

    $pendingPet = Pet::create(['user_id' => $user->id, 'animal_type_id' => $animalType->id, 'name' => 'Rex']);
    $pendingBooking = Booking::factory()->pending()->create([
        'user_id' => $user->id,
        'activity_id' => $activity->id,
        'check_in_date' => $checkIn,
        'check_out_date' => $checkOut,
        'stripe_payment_intent_id' => 'pi_test_overbook',
    ]);
    $pendingBooking->pets()->attach($pendingPet->id, [
        'price_per_night' => '30.00',
        'number_of_nights' => 3,
        'subtotal' => '90.00',
    ]);

    $this->withHeaders(asUser($manager))
        ->putJson("/api/activities/{$activity->id}/bookings/{$pendingBooking->id}/confirm")
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['status']);

    expect($pendingBooking->fresh()->status)->toBe(BookingStatusEnum::PENDING);
});

it('does not leave an orphan pending booking when payment fails at creation', function () {
    Event::fake();
    app()->instance(StripeClient::class, new FailingPaymentStripeClient);

    $user = User::factory()->create();
    [$manager, $activity, $animalType] = bookingFixesFixtures();
    $pet = Pet::create(['user_id' => $user->id, 'animal_type_id' => $animalType->id, 'name' => 'Rex']);

    $this->withHeaders(asUser($user))
        ->postJson('/api/bookings', [
            'activity_id' => $activity->id,
            'check_in_date' => now()->addDays(10)->format('Y-m-d'),
            'check_out_date' => now()->addDays(13)->format('Y-m-d'),
            'pet_ids' => [$pet->id],
            'payment_method_id' => 'pm_card_declined',
        ])
        ->assertUnprocessable();

    expect(Booking::where('user_id', $user->id)->where('status', BookingStatusEnum::PENDING)->exists())->toBeFalse();
});
