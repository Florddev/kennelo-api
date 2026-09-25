<?php

declare(strict_types=1);

use App\Enums\BookingItemStatusEnum;
use App\Enums\BookingStatusEnum;
use App\Models\Activity;
use App\Models\Address;
use App\Models\AnimalType;
use App\Models\Booking;
use App\Models\Pet;
use App\Models\User;
use App\Models\UserAddress;
use App\Notifications\AppNotification;
use Illuminate\Support\Facades\Notification;

describe('quote', function () {
    it('prices the nights of each unit and adds the Kennelo fee', function () {
        $unitType = dogBoarding();
        $client = User::factory()->create();

        $this->withHeaders(asUser($client))
            ->postJson('/api/bookings/quote', stayRequest($unitType, [dogOf($client, $unitType)]))
            ->assertOk()
            ->assertJsonPath('data.nights', 2)
            ->assertJsonPath('data.units.0.subtotal', '60.00')
            ->assertJsonPath('data.items_amount', '60.00')
            ->assertJsonPath('data.service_fee', '4.80')
            ->assertJsonPath('data.total_price', '64.80')
            ->assertJsonPath('data.cancellation_policy', 'moderate')
            ->assertJsonMissingPath('data.platform_fee');
    });

    it('charges the extra animals sharing a unit', function () {
        $unitType = dogBoarding(extraAnimalPrice: '10.00', animalsPerUnit: 2);
        $client = User::factory()->create();
        $pets = [dogOf($client, $unitType), dogOf($client, $unitType)];

        $this->withHeaders(asUser($client))
            ->postJson('/api/bookings/quote', stayRequest($unitType, $pets, [
                'units' => [['unit_type_id' => $unitType->id, 'pet_ids' => [$pets[0]->id, $pets[1]->id]]],
            ]))
            ->assertOk()
            ->assertJsonPath('data.units.0.subtotal', '80.00');
    });

    it('adds the stay options at the price of the activity for each pet, an included one being free', function () {
        $unitType = dogBoarding();
        $client = User::factory()->create();
        $dog = dogOf($client, $unitType);
        $bath = stayOption($unitType, '20.00', adjustment: '10');
        $walk = stayOption($unitType, '8.00', included: true);

        $this->withHeaders(asUser($client))
            ->postJson('/api/bookings/quote', stayRequest($unitType, [$dog], ['options' => [
                ['service_id' => $bath->id, 'pet_id' => $dog->id, 'quantity' => 2],
                ['service_id' => $walk->id, 'pet_id' => $dog->id],
            ]]))
            ->assertOk()
            ->assertJsonPath('data.options.0.unit_price', '22.00')
            ->assertJsonPath('data.options.0.subtotal', '44.00')
            ->assertJsonPath('data.options.1.subtotal', '0.00')
            ->assertJsonPath('data.items_amount', '104.00');
    });

    it('refuses a pet of another client and a pet placed twice', function () {
        $unitType = dogBoarding();
        $client = User::factory()->create();
        $dog = dogOf($client, $unitType);
        $stranger = dogOf(User::factory()->create(), $unitType);

        $this->withHeaders(asUser($client))
            ->postJson('/api/bookings/quote', stayRequest($unitType, [$dog, $stranger]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['units.1.pet_ids.0']);

        $this->postJson('/api/bookings/quote', stayRequest($unitType, [$dog, $dog]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['units.0.pet_ids.0']);
    });

    it('refuses a species the unit does not take in', function () {
        $unitType = dogBoarding();
        $client = User::factory()->create();
        $cat = Pet::factory()->for($client)->create(['animal_type_id' => AnimalType::factory()->cat()]);

        $this->withHeaders(asUser($client))
            ->postJson('/api/bookings/quote', stayRequest($unitType, [$cat]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['units.0.pet_ids']);
    });

    it('refuses more animals than a unit takes in', function () {
        $unitType = dogBoarding();
        $client = User::factory()->create();
        $pets = [dogOf($client, $unitType), dogOf($client, $unitType)];

        $this->withHeaders(asUser($client))
            ->postJson('/api/bookings/quote', stayRequest($unitType, $pets, [
                'units' => [['unit_type_id' => $unitType->id, 'pet_ids' => [$pets[0]->id, $pets[1]->id]]],
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['units.0.pet_ids' => __('booking.too_many_animals', ['max' => 1])]);
    });

    it('refuses a night when every unit is taken', function () {
        $unitType = dogBoarding(units: 1);
        Booking::factory()->occupying($unitType)->between(today()->addDays(11)->toDateString(), today()->addDays(13)->toDateString())->create();
        $client = User::factory()->create();

        $this->withHeaders(asUser($client))
            ->postJson('/api/bookings/quote', stayRequest($unitType, [dogOf($client, $unitType)]))
            ->assertUnprocessable()
            ->assertJson(['message' => __('booking.full_on', ['date' => today()->addDays(11)->toDateString()])]);
    });

    it('frees the units of a declined or cancelled stay', function () {
        $unitType = dogBoarding(units: 1);
        Booking::factory()->occupying($unitType)->status(BookingStatusEnum::REJECTED)->create();
        $client = User::factory()->create();

        $this->withHeaders(asUser($client))
            ->postJson('/api/bookings/quote', stayRequest($unitType, [dogOf($client, $unitType)]))
            ->assertOk();
    });

    it('refuses a stay shorter than the minimum of its first night', function () {
        $unitType = dogBoarding();
        $unitType->activity->periodSettings()->update(['min_stay' => 3]);
        $client = User::factory()->create();

        $this->withHeaders(asUser($client))
            ->postJson('/api/bookings/quote', stayRequest($unitType, [dogOf($client, $unitType)]))
            ->assertUnprocessable()
            ->assertJson(['message' => __('booking.min_stay', ['count' => 3])]);
    });

    it('refuses an activity that cannot be booked', function () {
        $unitType = dogBoarding();
        $unitType->activity->forceFill(['is_active' => false])->save();
        $client = User::factory()->create();

        $this->withHeaders(asUser($client))
            ->postJson('/api/bookings/quote', stayRequest($unitType, [dogOf($client, $unitType)]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['activity_id' => __('booking.activity_unavailable')]);
    });

    it('comes to the client within the radius of the activity', function () {
        $unitType = dogBoarding();
        $unitType->activity->forceFill(['serves_at_pro' => false, 'serves_at_client' => true, 'service_radius_km' => 10])->save();
        $unitType->activity->address->update(['latitude' => 45.7640, 'longitude' => 4.8357]);
        $client = User::factory()->create();
        $near = UserAddress::factory()->for($client)->for(Address::factory()->state(['latitude' => 45.7719, 'longitude' => 4.8902]))->create();
        $far = UserAddress::factory()->for($client)->for(Address::factory()->state(['latitude' => 45.1885, 'longitude' => 5.7245]))->create();
        $dog = dogOf($client, $unitType);

        $this->withHeaders(asUser($client))
            ->postJson('/api/bookings/quote', stayRequest($unitType, [$dog], ['location' => 'at_client', 'address_id' => $near->id]))
            ->assertOk()
            ->assertJsonPath('data.location', 'at_client');

        $this->postJson('/api/bookings/quote', stayRequest($unitType, [$dog], ['location' => 'at_client', 'address_id' => $far->id]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['address_id' => __('booking.out_of_radius')]);
    });
});

describe('store', function () {
    it('books the stay, authorizes the payment and asks the team to answer', function () {
        Notification::fake();
        $unitType = dogBoarding();
        $client = User::factory()->create();
        $dog = dogOf($client, $unitType);
        stripeAuthorizes($this->stripe());

        $id = $this->withHeaders(asUser($client))
            ->postJson('/api/bookings', stayRequest($unitType, [$dog], ['special_requests' => 'Il a peur des orages']))
            ->assertCreated()
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.payment_status', 'requires_capture')
            ->assertJsonPath('data.total_price', '64.80')
            ->assertJsonPath('data.units.0.pet_ids', [$dog->id])
            ->assertJsonPath('data.payments.0.status', 'requires_capture')
            ->assertJsonMissingPath('data.client_secret')
            ->json('data.id');

        $booking = Booking::findOrFail($id);

        expect($booking->organization_id)->toBe($unitType->activity->organization_id)
            ->and($booking->vat_rate)->toBe('20.00')
            ->and($booking->platform_fee)->toBe('4.80')
            ->and($booking->activity_amount)->toBe('55.20')
            ->and($booking->stripe_transfer_group)->toBe("booking_{$id}");
        $this->stripe()->assertSent('post', '/v1/payment_intents', fn (array $params): bool => $params['amount'] === 6480
            && $params['capture_method'] === 'manual'
            && $params['setup_future_usage'] === 'off_session'
            && $params['metadata']['booking_id'] === $id);
        Notification::assertSentTo($unitType->activity->organization->owner, AppNotification::class);
    });

    it('waits for the 3-D Secure confirmation before asking the team', function () {
        Notification::fake();
        $unitType = dogBoarding();
        $client = User::factory()->create();
        stripeAuthorizes($this->stripe(), 'requires_action');

        $this->withHeaders(asUser($client))
            ->postJson('/api/bookings', stayRequest($unitType, [dogOf($client, $unitType)]))
            ->assertCreated()
            ->assertJsonPath('data.payment_status', 'requires_action')
            ->assertJsonPath('data.client_secret', 'pi_initial_secret');

        Notification::assertNothingSent();
    });

    it('records nothing when the card is declined', function () {
        $unitType = dogBoarding();
        $client = User::factory()->create();
        $this->stripe()
            ->fake('post', '/v1/customers', ['object' => 'customer', 'id' => 'cus_client'])
            ->fake('post', '/v1/payment_intents', ['error' => ['type' => 'card_error', 'code' => 'card_declined', 'message' => 'Your card was declined.']], 402);

        $this->withHeaders(asUser($client))
            ->postJson('/api/bookings', stayRequest($unitType, [dogOf($client, $unitType)]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['payment_method_id' => __('booking.payment_declined')]);

        expect(Booking::count())->toBe(0);
    });

    it('releases the authorization when the last unit is taken during the payment', function () {
        $unitType = dogBoarding(units: 1);
        $client = User::factory()->create();
        $this->stripe()
            ->fake('post', '/v1/customers', ['object' => 'customer', 'id' => 'cus_client'])
            ->fake('post', '/v1/payment_intents', function () use ($unitType): array {
                Booking::factory()->occupying($unitType)->create();

                return ['object' => 'payment_intent', 'id' => 'pi_late', 'status' => 'requires_capture'];
            })
            ->fake('post', '/v1/payment_intents/pi_late/cancel', ['object' => 'payment_intent', 'id' => 'pi_late', 'status' => 'canceled']);

        $this->withHeaders(asUser($client))
            ->postJson('/api/bookings', stayRequest($unitType, [dogOf($client, $unitType)]))
            ->assertUnprocessable();

        $this->stripe()->assertSent('post', '/v1/payment_intents/pi_late/cancel');
        expect(Booking::query()->where('user_id', $client->id)->exists())->toBeFalse();
    });

    it('copies the address of the client and hides it from the team until the payment is captured', function () {
        $unitType = dogBoarding();
        $unitType->activity->forceFill(['serves_at_client' => true, 'service_radius_km' => 50])->save();
        $unitType->activity->address->update(['latitude' => 45.7640, 'longitude' => 4.8357]);
        $client = User::factory()->create();
        $home = UserAddress::factory()->for($client)->for(Address::factory()->state(['line1' => '12 rue des Lilas', 'latitude' => 45.7719, 'longitude' => 4.8902]))->create();
        stripeAuthorizes($this->stripe());

        $id = $this->withHeaders(asUser($client))
            ->postJson('/api/bookings', stayRequest($unitType, [dogOf($client, $unitType)], ['location' => 'at_client', 'address_id' => $home->id]))
            ->assertCreated()
            ->assertJsonPath('data.service_address.line1', '12 rue des Lilas')
            ->json('data.id');

        expect(Booking::findOrFail($id)->service_address_id)->not->toBe($home->address_id);

        $this->withHeaders(asUser($unitType->activity->organization->owner))
            ->getJson("/api/activities/{$unitType->activity_id}/bookings/{$id}")
            ->assertOk()
            ->assertJsonMissingPath('data.service_address.line1')
            ->assertJsonPath('data.service_address.city', $home->address->city);
    });

    it('sells the options to place in the agenda', function () {
        $unitType = dogBoarding();
        $client = User::factory()->create();
        $dog = dogOf($client, $unitType);
        $bath = stayOption($unitType, '20.00');
        stripeAuthorizes($this->stripe());

        $id = $this->withHeaders(asUser($client))
            ->postJson('/api/bookings', stayRequest($unitType, [$dog], ['options' => [['service_id' => $bath->id, 'pet_id' => $dog->id]]]))
            ->assertCreated()
            ->assertJsonPath('data.items.0.status', 'to_schedule')
            ->assertJsonPath('data.total_price', '86.40')
            ->json('data.id');

        $item = Booking::findOrFail($id)->items()->sole();

        expect($item->status)->toBe(BookingItemStatusEnum::TO_SCHEDULE)
            ->and($item->booking_payment_id)->toBe(Booking::findOrFail($id)->payments()->sole()->id);
    });
});

describe('reading', function () {
    it('lists the bookings of the client only', function () {
        $client = User::factory()->create();
        $mine = Booking::factory()->for($client)->create();
        Booking::factory()->create();

        $this->withHeaders(asUser($client))
            ->getJson('/api/bookings')
            ->assertOk()
            ->assertJsonPath('data.*.id', [$mine->id]);
    });

    it('shows a booking to its client and to the team, and to nobody else', function () {
        $booking = Booking::factory()->create();

        $this->withHeaders(asUser($booking->user))
            ->getJson("/api/bookings/{$booking->id}")
            ->assertOk()
            ->assertJsonMissingPath('data.activity_amount');

        $this->withHeaders(asUser($booking->organization->owner))
            ->getJson("/api/bookings/{$booking->id}")
            ->assertOk()
            ->assertJsonPath('data.activity_amount', '55.20')
            ->assertJsonPath('data.client.id', $booking->user_id);
    });

    it('hides a booking from a stranger', function () {
        $booking = Booking::factory()->create();

        $this->withHeaders(asUser(User::factory()->create()))
            ->getJson("/api/bookings/{$booking->id}")
            ->assertNotFound();
    });

    it('lists the bookings of an activity to its team', function () {
        $activity = Activity::factory()->bookable()->create();
        $booking = Booking::factory()->for($activity)->create(['organization_id' => $activity->organization_id]);
        Booking::factory()->create();

        $this->withHeaders(asUser($activity->organization->owner))
            ->getJson("/api/activities/{$activity->id}/bookings")
            ->assertOk()
            ->assertJsonPath('data.*.id', [$booking->id]);
    });
});
