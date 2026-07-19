<?php

declare(strict_types=1);

use App\Models\Address;
use App\Models\User;
use App\Services\User\UserService;

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

it('fills the department when a user updates an existing address', function (): void {
    $user = User::factory()->create();
    app(UserService::class)->upsertAddress($user, [
        'line1' => '1 rue du Test',
        'city' => 'Paris',
        'postal_code' => '75001',
        'country' => 'FR',
    ]);

    app(UserService::class)->upsertAddress($user->refresh(), [
        'line1' => '2 rue du Test',
        'city' => 'Lyon',
        'postal_code' => '69001',
        'country' => 'FR',
    ]);

    expect(Address::whereKey($user->refresh()->address_id)->value('department'))->toBe('69');
});

it('backfills missing departments with the artisan command', function (): void {
    $address = Address::factory()->create(['postal_code' => '33000', 'department' => null]);
    Address::withoutEvents(fn () => $address->update(['department' => null]));

    expect($address->refresh()->department)->toBeNull();

    $this->artisan('kennelo:backfill-address-departments')
        ->expectsOutputToContain('1 adresses mises à jour.')
        ->assertExitCode(0);

    expect($address->refresh()->department)->toBe('33');
});
