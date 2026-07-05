<?php

declare(strict_types=1);

use App\Enums\ActivityStatusEnum;
use App\Enums\ProspectStatusEnum;
use App\Models\Activity;
use App\Models\Prospect;
use App\Models\SearchLog;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    Cache::flush();
});

it('returns an overview of the platform', function () {
    $admin = adminUser();
    Activity::factory()->create(['status' => ActivityStatusEnum::PENDING->value]);
    Activity::factory()->count(2)->create(['status' => ActivityStatusEnum::APPROVED->value]);
    Prospect::factory()->count(3)->create(['status' => ProspectStatusEnum::NON_CONTACTE->value]);
    Prospect::factory()->create(['status' => ProspectStatusEnum::INSCRIT->value]);

    $this->withHeaders(asUser($admin))
        ->getJson('/api/admin/stats/overview')
        ->assertOk()
        ->assertJsonPath('data.activities.pending', 1)
        ->assertJsonPath('data.activities.approved', 2)
        ->assertJsonPath('data.prospects.total', 4)
        ->assertJsonPath('data.prospects.from_prospection', 1)
        ->assertJsonStructure([
            'data' => [
                'activities' => ['total', 'approved', 'pending', 'rejected', 'professionals'],
                'prospects' => ['total', 'registered', 'by_status', 'from_prospection'],
                'searches' => ['total', 'last_30_days'],
            ],
        ]);
});

it('returns search statistics by department and month', function () {
    $admin = adminUser();
    SearchLog::factory()->count(5)->create(['department' => '69']);
    SearchLog::factory()->count(2)->create(['department' => '75']);

    $this->withHeaders(asUser($admin))
        ->getJson('/api/admin/stats/searches')
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                'by_department' => [['department', 'total']],
                'monthly' => [['month', 'total']],
            ],
        ]);
});

it('returns business KPIs with funnel, validation, coverage and growth', function () {
    $admin = adminUser();

    Prospect::factory()->count(4)->create(['status' => ProspectStatusEnum::NON_CONTACTE->value]);
    Prospect::factory()->count(3)->create(['status' => ProspectStatusEnum::CONTACTE->value]);
    Prospect::factory()->count(2)->create(['status' => ProspectStatusEnum::INSCRIT->value]);
    Activity::factory()->count(3)->create([
        'status' => ActivityStatusEnum::APPROVED->value,
        'reviewed_at' => now(),
    ]);
    Activity::factory()->create([
        'status' => ActivityStatusEnum::REJECTED->value,
        'reviewed_at' => now(),
    ]);
    SearchLog::factory()->count(6)->create(['department' => '69']);

    $this->withHeaders(asUser($admin))
        ->getJson('/api/admin/stats/business')
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                'prospection_funnel' => ['identified', 'contacted', 'registered', 'conversion_rate', 'overall_conversion_rate'],
                'validation' => ['approved', 'rejected', 'pending', 'approval_rate', 'avg_processing_days'],
                'market_coverage' => [['department', 'searches', 'professionals', 'opportunity_score']],
                'growth' => ['searches' => ['series', 'current', 'previous', 'variation']],
            ],
        ])
        ->assertJsonPath('data.prospection_funnel.identified', 9)
        ->assertJsonPath('data.prospection_funnel.registered', 2)
        ->assertJsonPath('data.validation.approved', 3)
        ->assertJsonPath('data.validation.approval_rate', 75);
});

it('forbids non-admin from accessing stats', function () {
    $user = User::factory()->create();

    $this->withHeaders(asUser($user))
        ->getJson('/api/admin/stats/overview')
        ->assertForbidden();

    $this->withHeaders(asUser($user))
        ->getJson('/api/admin/stats/business')
        ->assertForbidden();
});
