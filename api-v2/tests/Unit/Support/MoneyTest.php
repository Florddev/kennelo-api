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

it('takes the VAT out of an amount including it', function (string $amount, string $rate, string $expected) {
    expect(Money::excludingVat($amount, $rate))->toBe($expected);
})->with([
    'standard rate' => ['12.00', '20.00', '10.00'],
    'rounded down' => ['10.00', '20.00', '8.33'],
    'rounded up' => ['19.99', '5.50', '18.95'],
    'a few cents' => ['0.05', '20.00', '0.04'],
    'VAT exemption' => ['30.00', '0.00', '30.00'],
]);

it('writes an amount the French way', function (string $amount, string $expected) {
    expect(Money::format($amount))->toBe($expected);
})->with([
    'cents' => ['0.5', '0,50'],
    'thousands' => ['1234.5', "1\u{A0}234,50"],
    'millions' => ['1234567.891', "1\u{A0}234\u{A0}567,89"],
    'negative' => ['-1500', "-1\u{A0}500,00"],
]);
