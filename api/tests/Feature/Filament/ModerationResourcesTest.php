<?php

declare(strict_types=1);

use App\Enums\ReviewReportStatusEnum;
use App\Filament\Resources\AdminActions\Pages\ListAdminActions;
use App\Filament\Resources\ReviewReports\Pages\ListReviewReports;
use App\Filament\Resources\SearchLogs\Pages\ListSearchLogs;
use App\Models\Activity;
use App\Models\Booking;
use App\Models\Review;
use App\Models\ReviewReport;
use App\Models\SearchLog;
use App\Models\User;
use Livewire\Livewire;

it('renders the search logs list for an admin', function (): void {
    actingAsFilamentAdmin();
    $logs = SearchLog::factory()->count(3)->create();

    Livewire::test(ListSearchLogs::class)
        ->assertOk()
        ->assertCanSeeTableRecords($logs);
});

it('renders the admin actions journal for an admin', function (): void {
    actingAsFilamentAdmin();

    Livewire::test(ListAdminActions::class)->assertOk();
});

it('renders the review reports list and resolves a report', function (): void {
    actingAsFilamentAdmin();

    $user = User::factory()->create();
    $manager = User::factory()->create();
    $activity = Activity::factory()->create(['manager_id' => $manager->id]);
    $booking = Booking::factory()->completed()->create([
        'user_id' => $user->id,
        'activity_id' => $activity->id,
    ]);
    $review = Review::factory()->fromUser()->published()->create([
        'booking_id' => $booking->id,
        'reviewer_id' => $user->id,
    ]);
    $report = ReviewReport::create([
        'review_id' => $review->id,
        'reporter_id' => $manager->id,
        'reason' => 'spam',
        'status' => ReviewReportStatusEnum::PENDING->value,
    ]);

    Livewire::test(ListReviewReports::class)
        ->assertOk()
        ->assertCanSeeTableRecords([$report])
        ->callTableAction('resolve', $report, data: ['status' => ReviewReportStatusEnum::REVIEWED->value]);

    $report->refresh();

    expect($report->status->value)->toBe(ReviewReportStatusEnum::REVIEWED->value)
        ->and($report->reviewed_at)->not->toBeNull();
});
