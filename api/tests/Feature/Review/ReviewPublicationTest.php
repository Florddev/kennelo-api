<?php

declare(strict_types=1);

use App\Models\Review;
use App\Services\Review\ReviewPublicationService;

it('publishes reviews older than 14 days', function () {
    $matured = Review::factory()->create([
        'is_published' => false,
        'created_at' => now()->subDays(15),
    ]);
    $young = Review::factory()->create([
        'is_published' => false,
        'created_at' => now()->subDays(5),
    ]);

    $count = app(ReviewPublicationService::class)->publishMatured();

    expect($count)->toBe(1);
    expect($matured->fresh()->is_published)->toBeTrue();
    expect($matured->fresh()->published_at)->not->toBeNull();
    expect($young->fresh()->is_published)->toBeFalse();
});

it('does not republish already published reviews', function () {
    Review::factory()->published()->create([
        'created_at' => now()->subDays(20),
    ]);

    $count = app(ReviewPublicationService::class)->publishMatured();

    expect($count)->toBe(0);
});
