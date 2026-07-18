<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    Carbon::setTestNow(Carbon::parse('2026-03-10 12:00:00', 'UTC'));
});

it('returns an empty string for null, empty and invalid input', function () {
    expect(human_date(null))->toBe('')
        ->and(human_date(''))->toBe('')
        ->and(human_date('not-a-date'))->toBe('');
});

it('renders today with the time converted to the display timezone', function () {
    app()->setLocale('fr');

    expect(human_date('2026-03-10 14:30:00'))->toBe("Aujourd'hui à 15:30");
});

it('renders yesterday', function () {
    app()->setLocale('fr');

    expect(human_date('2026-03-09 08:00:00'))->toBe('Hier à 09:00');
});

it('follows the request locale', function () {
    app()->setLocale('en');

    expect(human_date('2026-03-10 14:30:00'))->toBe('Today at 15:30');

    app()->setLocale('ar');

    expect(human_date('2026-03-10 14:30:00'))->toBe('اليوم الساعة 15:30');
});

it('renders date-only values without a phantom time', function () {
    app()->setLocale('fr');

    expect(human_date('2026-03-05'))->toBe('5 mars')
        ->and(human_date(Carbon::parse('2026-03-05 00:00:00')))->toBe('5 mars');
});

it('localizes the month and its position', function () {
    app()->setLocale('en');

    expect(human_date('2026-03-05'))->toBe('March 5');
});

it('shows the year only for other years', function () {
    app()->setLocale('fr');

    expect(human_date('2026-01-05'))->toBe('5 janvier')
        ->and(human_date('2025-06-15'))->toBe('15 juin 2025');

    app()->setLocale('en');

    expect(human_date('2025-06-15'))->toBe('June 15, 2025');
});

it('shifts the day bucket when the timezone conversion crosses midnight', function () {
    app()->setLocale('fr');

    expect(human_date('2026-03-09 23:30:00'))->toBe("Aujourd'hui à 00:30");
});

it('accepts DateTimeInterface instances', function () {
    app()->setLocale('en');

    $date = new DateTimeImmutable('2026-03-10 14:30:00', new DateTimeZone('UTC'));

    expect(human_date($date))->toBe('Today at 15:30');
});
