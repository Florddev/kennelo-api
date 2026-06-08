<?php

declare(strict_types=1);

use App\Enums\WeekDayEnum;

it('exposes the linux-style bitmask values', function () {
    expect(WeekDayEnum::MONDAY->value)->toBe(1)
        ->and(WeekDayEnum::TUESDAY->value)->toBe(2)
        ->and(WeekDayEnum::WEDNESDAY->value)->toBe(4)
        ->and(WeekDayEnum::THURSDAY->value)->toBe(8)
        ->and(WeekDayEnum::FRIDAY->value)->toBe(16)
        ->and(WeekDayEnum::SATURDAY->value)->toBe(32)
        ->and(WeekDayEnum::SUNDAY->value)->toBe(64)
        ->and(WeekDayEnum::ALL)->toBe(127);
});

it('builds a mask from days', function () {
    $mask = WeekDayEnum::toMask([WeekDayEnum::MONDAY, WeekDayEnum::WEDNESDAY, WeekDayEnum::FRIDAY]);

    expect($mask)->toBe(1 + 4 + 16);
});

it('decomposes a mask into days', function () {
    $days = WeekDayEnum::fromMask(WeekDayEnum::SATURDAY->value | WeekDayEnum::SUNDAY->value);

    expect($days)->toBe([WeekDayEnum::SATURDAY, WeekDayEnum::SUNDAY]);
});

it('returns all days for the ALL mask', function () {
    expect(WeekDayEnum::fromMask(WeekDayEnum::ALL))->toBe(WeekDayEnum::cases());
});

it('returns no day for an empty mask', function () {
    expect(WeekDayEnum::fromMask(WeekDayEnum::NONE))->toBe([]);
});

it('checks day membership in a mask', function () {
    $mask = WeekDayEnum::MONDAY->value | WeekDayEnum::FRIDAY->value;

    expect(WeekDayEnum::contains($mask, WeekDayEnum::MONDAY))->toBeTrue()
        ->and(WeekDayEnum::contains($mask, WeekDayEnum::FRIDAY))->toBeTrue()
        ->and(WeekDayEnum::contains($mask, WeekDayEnum::TUESDAY))->toBeFalse();
});

it('round-trips mask and days', function () {
    $days = [WeekDayEnum::TUESDAY, WeekDayEnum::THURSDAY, WeekDayEnum::SUNDAY];
    $mask = WeekDayEnum::toMask($days);

    expect(WeekDayEnum::fromMask($mask))->toBe($days);
});
