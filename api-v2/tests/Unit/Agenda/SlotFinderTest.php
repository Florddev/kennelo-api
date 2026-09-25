<?php

declare(strict_types=1);

use App\Enums\AvailabilityStatusEnum;
use App\Enums\WeekDayEnum;
use App\Services\Agenda\SlotFinder;
use Carbon\CarbonImmutable;

/**
 * Mêmes plages chaque jour de la semaine.
 *
 * @param  list<array{0: string, 1: string}>  $ranges
 * @return array<int, list<array{0: string, 1: string}>>
 */
function everyDay(array $ranges): array
{
    return array_fill_keys(WeekDayEnum::values(), $ranges);
}

/**
 * Salon parisien : Léa (puis les autres ressources données) planifiée de 8 h à 19 h tous les jours.
 *
 * @param  array<string, mixed>  $overrides  arguments nommés de SlotFinder
 */
function slotFinder(array $overrides = []): SlotFinder
{
    return new SlotFinder(...[
        'timezone' => 'Europe/Paris',
        'openingHours' => everyDay([['09:00', '18:00']]),
        'exceptions' => [],
        'schedules' => ['lea' => everyDay([['08:00', '19:00']])],
        'busy' => [],
        'stepMinutes' => 30,
        'earliestStart' => CarbonImmutable::parse('2000-01-01', 'UTC'),
        ...$overrides,
    ]);
}

/**
 * Débuts des créneaux d'un jour, en heure de Paris.
 *
 * @return list<string>
 */
function startsOn(SlotFinder $finder, string $day, int $duration = 60): array
{
    return array_map(
        fn (array $slot): string => $slot['starts_at']->setTimezone('Europe/Paris')->format('H:i'),
        $finder->find(CarbonImmutable::parse($day), CarbonImmutable::parse($day), $duration),
    );
}

function paris(string $time): CarbonImmutable
{
    return CarbonImmutable::parse($time, 'Europe/Paris');
}

it('offers slots on the step grid within each opening range of the day', function () {
    $finder = slotFinder(['openingHours' => everyDay([['09:00', '12:00'], ['14:00', '17:00']])]);

    expect(startsOn($finder, '2026-10-06'))->toBe(['09:00', '09:30', '10:00', '10:30', '11:00', '14:00', '14:30', '15:00', '15:30', '16:00']);
});

it('keeps a slot out of the lunch break', function () {
    $finder = slotFinder(['openingHours' => everyDay([['09:00', '12:00'], ['14:00', '18:00']])]);

    expect(startsOn($finder, '2026-10-06', duration: 90))->not->toContain('11:00')
        ->and(startsOn($finder, '2026-10-06', duration: 90))->toContain('10:30');
});

it('limits the opening hours to the schedule of the resource', function () {
    $finder = slotFinder(['schedules' => ['lea' => everyDay([['10:00', '12:00']])]]);

    expect(startsOn($finder, '2026-10-06'))->toBe(['10:00', '10:30', '11:00']);
});

it('starts on the grid when a range does not', function () {
    $finder = slotFinder(['schedules' => ['lea' => everyDay([['09:10', '11:00']])], 'stepMinutes' => 15]);

    expect(startsOn($finder, '2026-10-06')[0])->toBe('09:15');
});

it('joins two touching ranges of the schedule', function () {
    $finder = slotFinder(['schedules' => ['lea' => everyDay([['09:00', '12:00'], ['12:00', '14:00']])]]);

    expect(startsOn($finder, '2026-10-06', duration: 120))->toContain('11:00');
});

it('keeps the local hours across a daylight saving change', function (string $day, string $utc, string $dayBefore, string $utcBefore) {
    $finder = slotFinder();

    expect($finder->find(CarbonImmutable::parse($day), CarbonImmutable::parse($day), 60)[0]['starts_at']->toISOString())->toBe($utc)
        ->and($finder->find(CarbonImmutable::parse($dayBefore), CarbonImmutable::parse($dayBefore), 60)[0]['starts_at']->toISOString())->toBe($utcBefore);
})->with([
    'spring forward' => ['2026-03-29', '2026-03-29T07:00:00.000000Z', '2026-03-28', '2026-03-28T08:00:00.000000Z'],
    'fall back' => ['2026-10-25', '2026-10-25T08:00:00.000000Z', '2026-10-24', '2026-10-24T07:00:00.000000Z'],
]);

it('skips what keeps the resource busy, a slot may end when it begins', function () {
    $finder = slotFinder([
        'stepMinutes' => 60,
        'busy' => ['lea' => [[paris('2026-10-06 10:00'), paris('2026-10-06 11:00')]]],
    ]);

    expect(startsOn($finder, '2026-10-06'))->toBe(['09:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00', '17:00']);
});

it('closes on an exceptional closure, and opens on the schedule alone on an exceptional opening', function () {
    $finder = slotFinder([
        'openingHours' => [WeekDayEnum::TUESDAY->value => [['09:00', '12:00']]],
        'exceptions' => [
            '2026-10-06' => AvailabilityStatusEnum::CLOSED,
            '2026-10-11' => AvailabilityStatusEnum::OPEN,
        ],
        'stepMinutes' => 60,
    ]);

    expect(startsOn($finder, '2026-10-06'))->toBe([])
        ->and(startsOn($finder, '2026-10-13'))->toBe(['09:00', '10:00', '11:00'])
        ->and(startsOn($finder, '2026-10-18'))->toBe([])
        ->and(startsOn($finder, '2026-10-11'))->toHaveCount(11)->toContain('08:00', '18:00');
});

it('offers nothing before the notice', function () {
    $finder = slotFinder(['earliestStart' => paris('2026-10-06 16:10')->utc()]);

    expect(startsOn($finder, '2026-10-06'))->toBe(['16:30', '17:00']);
});

it('lists the free resources of a slot in their order', function () {
    $finder = slotFinder([
        'schedules' => ['lea' => everyDay([['09:00', '18:00']]), 'max' => everyDay([['09:00', '18:00']])],
        'busy' => ['lea' => [[paris('2026-10-06 09:00'), paris('2026-10-06 10:00')]]],
    ]);

    expect($finder->freeResourcesAt(paris('2026-10-06 09:00')->utc(), 60))->toBe(['max'])
        ->and($finder->freeResourcesAt(paris('2026-10-06 10:00')->utc(), 60))->toBe(['lea', 'max'])
        ->and($finder->freeResourcesAt(paris('2026-10-06 10:10')->utc(), 60))->toBe([]);
});
