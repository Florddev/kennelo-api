<?php

declare(strict_types=1);

use App\Enums\BookingStatusEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\ReviewerTypeEnum;
use App\Models\Booking;
use App\Models\Review;
use App\Models\SearchLog;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

beforeEach(fn () => Cache::flush());

it('returns finance KPIs from paid bookings', function () {
    $admin = adminUser();
    Booking::factory()->count(3)->create([
        'payment_status' => PaymentStatusEnum::SUCCEEDED->value,
        'total_price' => 100,
        'service_fee' => 10,
        'platform_fee' => 5,
        'activity_amount' => 85,
    ]);
    Booking::factory()->create(['payment_status' => PaymentStatusEnum::PENDING->value, 'total_price' => 999]);

    $this->withHeaders(asUser($admin))
        ->getJson('/api/admin/stats/finance')
        ->assertOk()
        ->assertJsonPath('data.gmv', 300)
        ->assertJsonPath('data.kennelo_revenue', 45)
        ->assertJsonPath('data.take_rate', 15)
        ->assertJsonPath('data.net_to_pros', 255)
        ->assertJsonPath('data.avg_basket', 100)
        ->assertJsonPath('data.paid_bookings', 3)
        ->assertJsonStructure(['data' => ['gmv', 'take_rate', 'refunds', 'refund_rate', 'gmv_growth' => ['series', 'variation']]]);
});

it('returns bookings KPIs by status', function () {
    $admin = adminUser();
    Booking::factory()->count(2)->create(['status' => BookingStatusEnum::COMPLETED->value]);
    Booking::factory()->create(['status' => BookingStatusEnum::CANCELLED->value]);
    Booking::factory()->create(['status' => BookingStatusEnum::PENDING->value]);

    $this->withHeaders(asUser($admin))
        ->getJson('/api/admin/stats/bookings')
        ->assertOk()
        ->assertJsonPath('data.total', 4)
        ->assertJsonPath('data.by_status.completed', 2)
        ->assertJsonPath('data.cancellation_rate', 25)
        ->assertJsonStructure(['data' => ['by_status', 'cancellation_rate', 'payment_conversion_rate', 'avg_stay_nights', 'monthly' => ['series']]]);
});

it('returns community KPIs', function () {
    $admin = adminUser();
    User::factory()->count(4)->create(['is_id_verified' => true]);
    User::factory()->count(2)->create(['last_seen_at' => now()]);
    $booking = Booking::factory()->create();
    Review::factory()->create([
        'booking_id' => $booking->id,
        'reviewer_type' => ReviewerTypeEnum::USER->value,
        'is_published' => true,
        'overall_rating' => 4.5,
    ]);

    $this->withHeaders(asUser($admin))
        ->getJson('/api/admin/stats/community')
        ->assertOk()
        ->assertJsonPath('data.avg_pro_rating', 4.5)
        ->assertJsonStructure([
            'data' => [
                'user_growth' => ['series'],
                'roles_split' => ['owners', 'pros', 'admins'],
                'kyc_rate',
                'active_users' => ['dau', 'wau', 'mau'],
                'avg_pro_rating',
                'rating_distribution',
                'review_response_rate',
                'messages_last_30_days',
                'active_conversations',
            ],
        ]);
});

it('extends searches with zero-result rate and top filters', function () {
    $admin = adminUser();
    SearchLog::factory()->count(3)->create(['results_count' => 5, 'filters' => ['sort' => 'rating']]);
    SearchLog::factory()->create(['results_count' => 0, 'filters' => ['min_rating' => 4]]);

    $this->withHeaders(asUser($admin))
        ->getJson('/api/admin/stats/searches')
        ->assertOk()
        ->assertJsonPath('data.zero_result_count', 1)
        ->assertJsonPath('data.zero_result_rate', 25)
        ->assertJsonStructure(['data' => ['zero_result_rate', 'top_filters']]);
});

it('extends business with team performance and contact mix', function () {
    $admin = adminUser();

    $this->withHeaders(asUser($admin))
        ->getJson('/api/admin/stats/business')
        ->assertOk()
        ->assertJsonStructure(['data' => ['team_performance', 'contact_type_mix']]);
});

it('forbids non-admin from the new stats endpoints', function () {
    $user = User::factory()->create();

    foreach (['finance', 'bookings', 'community'] as $endpoint) {
        $this->withHeaders(asUser($user))
            ->getJson("/api/admin/stats/{$endpoint}")
            ->assertForbidden();
    }
});
