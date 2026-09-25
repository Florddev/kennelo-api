<?php

declare(strict_types=1);

use App\Support\Money;

it('rounds to the nearest cent, half away from zero', function (string $amount, string $expected) {
    expect(Money::round($amount))->toBe($expected);
})->with([
    ['12.344', '12.34'],
    ['12.345', '12.35'],
    ['12.3449999', '12.34'],
    ['-12.345', '-12.35'],
    ['-0.004', '0.00'],
    ['7', '7.00'],
]);

it('adds amounts without float errors', function () {
    expect(Money::sum('0.10', '0.20'))->toBe('0.30')
        ->and(Money::sum())->toBe('0.00');
});

it('applies a percentage and rounds once', function (string $amount, string $percent, string $expected) {
    expect(Money::adjust($amount, $percent))->toBe($expected);
})->with([
    'discount' => ['40.00', '-20', '32.00'],
    'increase' => ['33.33', '15', '38.33'],
    'half cent' => ['10.05', '-50', '5.03'],
    'unchanged' => ['19.99', '0', '19.99'],
    'free' => ['19.99', '-100', '0.00'],
]);

it('multiplies an amount by a quantity', function () {
    expect(Money::multiply('12.50', '3'))->toBe('37.50')
        ->and(Money::multiply('0.333', '3'))->toBe('1.00');
});

it('converts to and from Stripe cents', function () {
    expect(Money::toCents('59.00'))->toBe(5900)
        ->and(Money::toCents('0.015'))->toBe(2)
        ->and(Money::fromCents(1999))->toBe('19.99');
});

it('prorates an amount', function () {
    expect(Money::prorate('10.00', '25.00', '100.00'))->toBe('2.50')
        ->and(Money::prorate('10.00', '1.00', '3.00'))->toBe('3.33')
        ->and(Money::prorate('10.00', '5.00', '0.00'))->toBe('0.00');
});

it('keeps the smaller amount', function () {
    expect(Money::min('12.50', '8.00'))->toBe('8.00')
        ->and(Money::min('3.00', '3.00'))->toBe('3.00');
});
