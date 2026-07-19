<?php

declare(strict_types=1);

use App\Models\User;

it('escapes formula injection in the users csv export', function () {
    $admin = adminUser();
    User::factory()->create([
        'first_name' => '=HYPERLINK("https://evil.tld")',
        'last_name' => '+attack',
        'email' => 'victim@example.com',
    ]);

    $response = $this->withHeaders(asUser($admin))->get('/api/admin/users/export');

    $response->assertOk();

    $content = $response->streamedContent();

    expect($content)->toContain('\'=HYPERLINK')
        ->and($content)->toContain('\'+attack')
        ->and($content)->not->toContain(',=HYPERLINK')
        ->and($content)->not->toContain(',+attack')
        ->and($content)->not->toContain('"=HYPERLINK');
});
