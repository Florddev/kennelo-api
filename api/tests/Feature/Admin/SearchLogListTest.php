<?php

declare(strict_types=1);

use App\Models\SearchLog;
use App\Models\User;

it('admin can list search logs', function () {
    $admin = adminUser();
    SearchLog::factory()->count(3)->create();

    $this->withHeaders(asUser($admin))
        ->getJson('/api/admin/search-logs')
        ->assertOk()
        ->assertJsonStructure(['data' => [['id', 'location', 'department', 'results_count', 'created_at']], 'meta']);
});

it('admin can filter search logs by department', function () {
    $admin = adminUser();
    SearchLog::factory()->create(['department' => '69']);
    SearchLog::factory()->create(['department' => '75']);

    $this->withHeaders(asUser($admin))
        ->getJson('/api/admin/search-logs?department=69')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.department', '69');
});

it('forbids non-admin from listing search logs', function () {
    $user = User::factory()->create();

    $this->withHeaders(asUser($user))
        ->getJson('/api/admin/search-logs')
        ->assertForbidden();
});
