<?php

declare(strict_types=1);

use App\Enums\BookingStatusEnum;
use App\Enums\PaymentStatusEnum;
use App\Models\Activity;
use App\Models\Booking;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Review;
use App\Models\User;

it('gives the admins an overview of the platform', function () {
    Activity::factory()->bookable()->create();
    Booking::factory()->create();

    $this->withHeaders(asUser(adminUser()))
        ->getJson('/api/admin/stats/overview')
        ->assertOk()
        ->assertJsonPath('activities.bookable', 2)
        ->assertJsonPath('activities.by_status.approved', 2)
        ->assertJsonPath('organizations.by_status.verified', 2)
        ->assertJsonPath('bookings', ['total' => 1, 'pending' => 1])
        ->assertJsonPath('pending_review_reports', 0);
});

it('sums up the money of the paid bookings', function () {
    Booking::factory()->confirmed()->count(2)->create();
    Booking::factory()->create();
    $refunded = Booking::factory()->confirmed()->create(['payment_status' => PaymentStatusEnum::PARTIALLY_REFUNDED, 'total_price' => '34.80']);
    $refunded->payments()->sole()->refunds()->create(['amount' => '30.00', 'service_fee_amount' => '0.00', 'reason' => 'client_cancellation', 'stripe_refund_id' => 're_1']);

    $this->withHeaders(asUser(adminUser()))
        ->getJson('/api/admin/stats/finance')
        ->assertOk()
        ->assertJsonPath('paid_bookings', 3)
        ->assertJsonPath('gmv', '164.40')
        ->assertJsonPath('kennelo_revenue', '28.80')
        ->assertJsonPath('net_to_pros', '165.60')
        ->assertJsonPath('refunds', '30.00')
        ->assertJsonPath('average_basket', '54.80')
        ->assertJsonPath('gmv_by_month.current', 164.4);
});

it('counts the bookings by status', function () {
    Booking::factory()->create();
    Booking::factory()->status(BookingStatusEnum::REJECTED)->create();
    Booking::factory()->confirmed()->occupying(dogBoarding())->create();

    $this->withHeaders(asUser(adminUser()))
        ->getJson('/api/admin/stats/bookings')
        ->assertOk()
        ->assertJsonPath('total', 3)
        ->assertJsonPath('by_status.rejected', 1)
        ->assertJsonPath('cancellation_rate', 33.3)
        ->assertJsonPath('average_stay_nights', 2)
        ->assertJsonPath('by_month.current', 3);
});

it('measures the community', function () {
    Review::factory()->rating('5.0')->published()->create();
    Message::factory()->for(Conversation::factory())->create();

    $this->withHeaders(asUser(adminUser()))
        ->getJson('/api/admin/stats/community')
        ->assertOk()
        ->assertJsonPath('reviews.average', 5)
        ->assertJsonPath('reviews.response_rate', 0)
        ->assertJsonPath('messages_last_30_days', 1)
        ->assertJsonPath('active_conversations', 1)
        ->assertJsonPath('professionals', 2);
});

it('keeps the stats to the admins', function () {
    $this->withHeaders(asUser(User::factory()->create()))->getJson('/api/admin/stats/overview')->assertForbidden();
});
