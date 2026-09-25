<?php

declare(strict_types=1);

use App\Enums\ActivityStatusEnum;
use App\Enums\DocumentStatusEnum;
use App\Enums\DocumentTypeEnum;
use App\Enums\OrganizationRoleEnum;
use App\Models\Activity;
use App\Models\ActivityDocument;
use App\Models\Profession;
use App\Models\User;
use App\Notifications\AppNotification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
});

/**
 * Activité d'un métier qui exige une assurance valable un an et accepte une déclaration facultative sans échéance.
 */
function activityRequiringInsurance(): Activity
{
    $profession = Profession::factory()
        ->requiring(DocumentTypeEnum::RC_PRO_INSURANCE, 12)
        ->requiring(DocumentTypeEnum::PREFECTURE_DECLARATION, required: false)
        ->create();

    return Activity::factory()->for($profession)->create();
}

describe('submission', function () {
    it('stores the document on the private disk, pending review', function () {
        $activity = activityRequiringInsurance();

        $this->withHeaders(asUser($activity->organization->owner))
            ->post("/api/activities/{$activity->id}/documents", [
                'document_type' => 'rc_pro_insurance',
                'expires_at' => now()->addYear()->toDateString(),
                'file' => UploadedFile::fake()->image('attestation.jpg'),
            ], ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.file.name', 'attestation.jpg')
            ->assertJsonMissingPath('data.file.url');

        $media = ActivityDocument::query()->sole()->getFirstMedia(ActivityDocument::COLLECTION_FILE);

        expect($media->disk)->toBe('local');
        Storage::disk('local')->assertExists($media->getPathRelativeToRoot());
    });

    it('requires the expiry date of a document that expires', function () {
        $activity = activityRequiringInsurance();

        $this->withHeaders(asUser($activity->organization->owner))
            ->post("/api/activities/{$activity->id}/documents", [
                'document_type' => 'rc_pro_insurance',
                'file' => UploadedFile::fake()->image('attestation.jpg'),
            ], ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['expires_at']);

        $this->withHeaders(asUser($activity->organization->owner))
            ->post("/api/activities/{$activity->id}/documents", [
                'document_type' => 'prefecture_declaration',
                'file' => UploadedFile::fake()->image('declaration.jpg'),
            ], ['Accept' => 'application/json'])
            ->assertCreated();
    });

    it('refuses a document the profession does not expect', function () {
        $activity = activityRequiringInsurance();

        $this->withHeaders(asUser($activity->organization->owner))
            ->post("/api/activities/{$activity->id}/documents", [
                'document_type' => 'acaced',
                'file' => UploadedFile::fake()->image('acaced.jpg'),
            ], ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['document_type' => __('activity.document_not_required')]);
    });

    it('lets the team download the file, and nobody else', function () {
        $activity = activityRequiringInsurance();
        $document = ActivityDocument::factory()->for($activity)->create();
        $document->addMedia(UploadedFile::fake()->image('attestation.jpg'))->toMediaCollection(ActivityDocument::COLLECTION_FILE);

        $this->withHeaders(asUser($activity->organization->owner))
            ->get("/api/activities/{$activity->id}/documents/{$document->id}/file")
            ->assertOk()
            ->assertHeader('content-type', 'image/jpeg');

        $this->withHeaders(asUser(User::factory()->create()))
            ->get("/api/activities/{$activity->id}/documents/{$document->id}/file")
            ->assertNotFound();

        $this->withHeaders(asUser(memberOf($activity->organization, OrganizationRoleEnum::ACCOUNTANT)))
            ->get("/api/activities/{$activity->id}/documents/{$document->id}/file")
            ->assertForbidden();
    });
});

describe('review by Kennelo', function () {
    it('refuses to approve an activity without its required documents', function () {
        $activity = activityRequiringInsurance();
        ActivityDocument::factory()->for($activity)->create();

        $this->withHeaders(asUser(adminUser()))
            ->postJson("/api/admin/activities/{$activity->id}/approve")
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['activity' => __('activity.missing_documents')]);
    });

    it('approves an activity whose required documents are valid', function () {
        Notification::fake();
        $activity = activityRequiringInsurance();
        ActivityDocument::factory()->for($activity)->approved()->create();

        $this->withHeaders(asUser(adminUser()))
            ->postJson("/api/admin/activities/{$activity->id}/approve")
            ->assertOk()
            ->assertJsonPath('data.status', 'approved');

        Notification::assertSentTo($activity->organization->owner, AppNotification::class);
    });

    it('approves a document and notifies the team of the activity', function () {
        Notification::fake();
        $activity = activityRequiringInsurance();
        $manager = memberOf($activity->organization, OrganizationRoleEnum::ACTIVITY_MANAGER, $activity->id);
        $accountant = memberOf($activity->organization, OrganizationRoleEnum::ACCOUNTANT);
        $document = ActivityDocument::factory()->for($activity)->create();

        $this->withHeaders(asUser(adminUser()))
            ->postJson("/api/admin/activity-documents/{$document->id}/approve")
            ->assertOk()
            ->assertJsonPath('data.status', 'approved');

        Notification::assertSentTo([$activity->organization->owner, $manager], AppNotification::class);
        Notification::assertNotSentTo($accountant, AppNotification::class);
    });

    it('rejects a document with a reason', function () {
        $document = ActivityDocument::factory()->for(activityRequiringInsurance())->create();

        $this->withHeaders(asUser(adminUser()))
            ->postJson("/api/admin/activity-documents/{$document->id}/reject", ['reason' => 'Attestation illisible'])
            ->assertOk()
            ->assertJsonPath('data.status', 'rejected')
            ->assertJsonPath('data.rejection_reason', 'Attestation illisible');
    });

    it('lists the documents waiting for review', function () {
        $activity = activityRequiringInsurance();
        $pending = ActivityDocument::factory()->for($activity)->create();
        ActivityDocument::factory()->for($activity)->approved()->create();

        $this->withHeaders(asUser(adminUser()))
            ->getJson('/api/admin/activity-documents?status=pending')
            ->assertOk()
            ->assertJsonPath('data.*.id', [$pending->id])
            ->assertJsonPath('data.0.activity.id', $activity->id);
    });
});

describe('daily expiry task', function () {
    it('expires overdue documents and suspends the activities that miss a required one', function () {
        Notification::fake();
        $activity = activityRequiringInsurance();
        $activity->forceFill(['status' => ActivityStatusEnum::APPROVED])->save();
        $document = ActivityDocument::factory()->for($activity)->approved()->expiringOn(now()->subDay())->create();

        $this->artisan('activities:expire-documents')->assertSuccessful();

        $activity->refresh();

        expect($document->fresh()->status)->toBe(DocumentStatusEnum::EXPIRED)
            ->and($activity->status)->toBe(ActivityStatusEnum::SUSPENDED)
            ->and($activity->rejection_reason)->toBe(__('activity.suspended_missing_documents'));
        Notification::assertSentToTimes($activity->organization->owner, AppNotification::class, 2);
    });

    it('keeps an activity whose document is still valid today', function () {
        $activity = activityRequiringInsurance();
        $activity->forceFill(['status' => ActivityStatusEnum::APPROVED])->save();
        ActivityDocument::factory()->for($activity)->approved()->expiringOn(today())->create();

        $this->artisan('activities:expire-documents')->assertSuccessful();

        expect($activity->fresh()->status)->toBe(ActivityStatusEnum::APPROVED);
    });

    it('warns the team once, when the expiry date comes near', function () {
        Notification::fake();
        $activity = activityRequiringInsurance();
        ActivityDocument::factory()->for($activity)->approved()->expiringOn(today()->addDays(30))->create();
        ActivityDocument::factory()->for($activity)->approved()->expiringOn(today()->addDays(31))->create();

        $this->artisan('activities:expire-documents')->assertSuccessful();

        Notification::assertSentToTimes($activity->organization->owner, AppNotification::class, 1);
    });
});
