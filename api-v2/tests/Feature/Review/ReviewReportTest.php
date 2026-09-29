<?php

declare(strict_types=1);

use App\Enums\AdminActionTypeEnum;
use App\Enums\ReviewReportStatusEnum;
use App\Models\AdminAction;
use App\Models\Review;
use App\Models\ReviewReport;
use App\Models\User;
use App\Notifications\AppNotification;
use App\Services\Review\ReviewPublicationService;
use Illuminate\Support\Facades\Notification;

describe('reporting', function () {
    it('lets anyone report a published review once, and warns the admins', function () {
        Notification::fake();
        $admin = adminUser();
        $review = Review::factory()->published()->create();

        $this->withHeaders(asUser(User::factory()->create()))
            ->postJson("/api/reviews/{$review->id}/reports", ['reason' => 'offensive', 'description' => 'Insultes envers le personnel.'])
            ->assertCreated()
            ->assertJsonPath('status', 'pending')
            ->assertJsonPath('reason', 'offensive');
        $this->postJson("/api/reviews/{$review->id}/reports", ['reason' => 'spam'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['review' => __('reviews.errors.already_reported')]);

        Notification::assertSentTo($admin, AppNotification::class);
    });

    it('cannot report its own review, or one not published', function () {
        $review = Review::factory()->published()->create();
        $hidden = Review::factory()->create();

        $this->withHeaders(asUser($review->reviewer))
            ->postJson("/api/reviews/{$review->id}/reports", ['reason' => 'spam'])
            ->assertForbidden();
        $this->withHeaders(asUser(User::factory()->create()))
            ->postJson("/api/reviews/{$hidden->id}/reports", ['reason' => 'spam'])
            ->assertNotFound();
        $this->postJson("/api/reviews/{$review->id}/reports", ['reason' => 'boring'])->assertJsonValidationErrors('reason');
    });
});

describe('moderation', function () {
    it('lists the reports for the admins only', function () {
        $pending = ReviewReport::query()->create(['review_id' => Review::factory()->published()->create()->id, 'reporter_id' => User::factory()->create()->id, 'reason' => 'fake']);
        $closed = ReviewReport::query()->create(['review_id' => Review::factory()->published()->create()->id, 'reporter_id' => User::factory()->create()->id, 'reason' => 'spam']);
        $closed->forceFill(['status' => ReviewReportStatusEnum::REJECTED])->save();

        $this->withHeaders(asUser(adminUser()))
            ->getJson('/api/admin/review-reports?status=pending')
            ->assertOk()
            ->assertJsonPath('data.*.id', [$pending->id])
            ->assertJsonPath('data.0.review.reviewer.id', $pending->review?->reviewer_id);
        $this->withHeaders(asUser(User::factory()->create()))->getJson('/api/admin/review-reports')->assertForbidden();
    });

    it('removes a review for good, closing its other reports', function () {
        Notification::fake();
        $admin = adminUser();
        $review = Review::factory()->published()->create();
        $reporters = User::factory()->count(2)->create();
        [$first, $second] = $reporters->map(fn (User $reporter): ReviewReport => ReviewReport::query()->create(['review_id' => $review->id, 'reporter_id' => $reporter->id, 'reason' => 'offensive']))->all();

        $this->withHeaders(asUser($admin))
            ->putJson("/api/admin/review-reports/{$first->id}", ['status' => 'removed'])
            ->assertOk()
            ->assertJsonPath('status', 'removed')
            ->assertJsonPath('review.is_published', false);
        $this->putJson("/api/admin/review-reports/{$second->id}", ['status' => 'rejected'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status' => __('reviews.errors.already_decided')]);

        expect($second->refresh()->status)->toBe(ReviewReportStatusEnum::REMOVED)
            ->and(AdminAction::query()->sole()->action)->toBe(AdminActionTypeEnum::DECIDE_REVIEW_REPORT);
        Notification::assertSentTo($reporters, AppNotification::class);
        $this->getJson("/api/activities/{$review->activity_id}/reviews")->assertJsonCount(0, 'data');

        // Même à la fin du délai, un avis retiré ne revient pas.
        $this->travel(30)->days();
        app(ReviewPublicationService::class)->publishDue();
        expect($review->refresh()->is_published)->toBeFalse();
    });

    it('keeps a review that is examined without follow-up', function () {
        $review = Review::factory()->published()->create();
        $report = ReviewReport::query()->create(['review_id' => $review->id, 'reporter_id' => User::factory()->create()->id, 'reason' => 'other']);

        $this->withHeaders(asUser(adminUser()))
            ->putJson("/api/admin/review-reports/{$report->id}", ['status' => 'reviewed'])
            ->assertOk();
        $this->putJson("/api/admin/review-reports/{$report->id}", ['status' => 'pending'])->assertJsonValidationErrors('status');

        expect($review->refresh()->is_published)->toBeTrue();
    });
});
