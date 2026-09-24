<?php

declare(strict_types=1);

use App\Models\Address;

it('fills the department from the postal code when an address is created', function (): void {
    $address = Address::factory()->create(['postal_code' => '69003', 'department' => null]);

    expect($address->refresh()->department)->toBe('69');
});

it('handles corsica and overseas postal codes', function (): void {
    expect(Address::factory()->create(['postal_code' => '20090', 'department' => null])->refresh()->department)->toBe('2A')
        ->and(Address::factory()->create(['postal_code' => '97400', 'department' => null])->refresh()->department)->toBe('974');
});

it('recomputes the department when the postal code changes', function (): void {
    $address = Address::factory()->create(['postal_code' => '75001', 'department' => null]);

    expect($address->refresh()->department)->toBe('75');

    $address->update(['postal_code' => '69001']);

    expect($address->refresh()->department)->toBe('69');
});
