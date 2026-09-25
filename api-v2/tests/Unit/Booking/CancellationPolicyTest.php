<?php

declare(strict_types=1);

use App\Enums\CancellationPolicyEnum;
use Carbon\CarbonImmutable;

it('refunds according to the time left before the stay', function (CancellationPolicyEnum $policy, int $hoursBefore, string $rate) {
    $start = CarbonImmutable::parse('2026-07-14 00:00', 'Europe/Paris');

    expect($policy->refundRate($start->subHours($hoursBefore), $start))->toBe($rate);
})->with([
    'flexible, 24 hours before' => [CancellationPolicyEnum::FLEXIBLE, 24, '1.00'],
    'flexible, 23 hours before' => [CancellationPolicyEnum::FLEXIBLE, 23, '0.00'],
    'moderate, 5 days before' => [CancellationPolicyEnum::MODERATE, 120, '1.00'],
    'moderate, 119 hours before' => [CancellationPolicyEnum::MODERATE, 119, '0.50'],
    'moderate, after the start' => [CancellationPolicyEnum::MODERATE, -2, '0.50'],
    'strict, 7 days before' => [CancellationPolicyEnum::STRICT, 168, '0.50'],
    'strict, 167 hours before' => [CancellationPolicyEnum::STRICT, 167, '0.00'],
]);
