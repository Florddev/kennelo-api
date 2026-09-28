<?php

declare(strict_types=1);

use App\Enums\BookingStatusEnum;
use App\Models\Booking;
use App\Models\Review;

it('publishes a lone review once the window to review has closed', function () {
    $closed = Booking::factory()->confirmed()->status(BookingStatusEnum::COMPLETED)
        ->between(today()->subDays(20)->toDateString(), today()->subDays(15)->toDateString())
        ->create();
    $open = Booking::factory()->confirmed()->status(BookingStatusEnum::COMPLETED)
        ->between(today()->subDays(10)->toDateString(), today()->subDays(8)->toDateString())
        ->create();
    $due = Review::factory()->for($closed)->create();
    $waiting = Review::factory()->for($open)->create();

    $this->artisan('reviews:publish-due')->expectsOutputToContain('Reviews published: 1.')->assertSuccessful();

    expect($due->refresh()->is_published)->toBeTrue()
        ->and($due->published_at)->not->toBeNull()
        ->and($waiting->refresh()->is_published)->toBeFalse();

    $this->artisan('reviews:publish-due')->expectsOutputToContain('Reviews published: 0.');
});
