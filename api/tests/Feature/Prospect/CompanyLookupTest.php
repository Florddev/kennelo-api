<?php

declare(strict_types=1);

use App\Services\Prospect\CompanyLookupService;
use Carbon\CarbonInterval;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;

beforeEach(function () {
    config(['services.recherche_entreprises.url' => 'https://recherche-entreprises.test']);
    Sleep::fake();
});

it('retries on a 429 then returns the first result', function () {
    Http::fakeSequence('recherche-entreprises.test/*')
        ->push(['message' => 'rate limited'], 429, ['Retry-After' => '1'])
        ->push(['results' => [['siren' => '123456789', 'nom_complet' => 'Acme']]], 200);

    $result = app(CompanyLookupService::class)->searchByText('acme');

    expect($result)->not->toBeNull()
        ->and($result['siren'])->toBe('123456789')
        ->and($result['legal_name'])->toBe('Acme');

    Http::assertSentCount(2);
});

it('honors the Retry-After header via a faked sleep', function () {
    Http::fakeSequence('recherche-entreprises.test/*')
        ->push(['message' => 'rate limited'], 429, ['Retry-After' => '2'])
        ->push(['results' => [['siren' => '999888777']]], 200);

    app(CompanyLookupService::class)->searchByText('acme');

    Sleep::assertSlept(fn (CarbonInterval $duration): bool => $duration->totalSeconds === 2.0);
});

it('returns null after exhausting retries on repeated 429s', function () {
    Http::fake([
        'recherche-entreprises.test/*' => Http::response(['message' => 'rate limited'], 429, ['Retry-After' => '1']),
    ]);

    $result = app(CompanyLookupService::class)->findBySiret('12345678901234');

    expect($result)->toBeNull();
    Http::assertSentCount(3);
});

it('returns null on a non-retryable server error without retrying', function () {
    Http::fake([
        'recherche-entreprises.test/*' => Http::response(['message' => 'boom'], 500),
    ]);

    $result = app(CompanyLookupService::class)->searchByText('acme');

    expect($result)->toBeNull();
    Http::assertSentCount(1);
});
