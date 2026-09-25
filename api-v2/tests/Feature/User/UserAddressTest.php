<?php

declare(strict_types=1);

use App\Models\Address;
use App\Models\User;
use App\Models\UserAddress;

/**
 * @return array<string, mixed>
 */
function addressPayload(string $label, array $overrides = []): array
{
    return array_merge([
        'label' => $label,
        'address' => ['line1' => '3 rue des Lilas', 'postal_code' => '69003', 'city' => 'Lyon', 'country' => 'FR'],
    ], $overrides);
}

it('makes the first address the default one', function () {
    $client = User::factory()->create();

    $this->withHeaders(asUser($client))
        ->postJson('/api/user/addresses', addressPayload('Domicile'))
        ->assertCreated()
        ->assertJsonPath('data.label', 'Domicile')
        ->assertJsonPath('data.is_default', true)
        ->assertJsonPath('data.address.department', '69');
});

it('moves the default to a new address', function () {
    $client = User::factory()->create();
    $home = UserAddress::factory()->for($client)->default()->create();

    $this->withHeaders(asUser($client))
        ->postJson('/api/user/addresses', addressPayload('Chez mes parents', ['is_default' => true]))
        ->assertCreated()
        ->assertJsonPath('data.is_default', true);

    expect($home->fresh()->is_default)->toBeFalse();
});

it('lists the addresses, the default one first', function () {
    $client = User::factory()->create();
    UserAddress::factory()->for($client)->create(['label' => 'A']);
    $default = UserAddress::factory()->for($client)->default()->create(['label' => 'B']);
    UserAddress::factory()->create();

    $this->withHeaders(asUser($client))
        ->getJson('/api/user/addresses')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.id', $default->id);
});

it('updates an address and makes it the default one', function () {
    $client = User::factory()->create();
    $home = UserAddress::factory()->for($client)->default()->create();
    $work = UserAddress::factory()->for($client)->create();

    $this->withHeaders(asUser($client))
        ->patchJson("/api/user/addresses/{$work->id}", ['label' => 'Bureau', 'is_default' => true, 'address' => ['city' => 'Villeurbanne', 'line1' => '1 cours Émile Zola', 'postal_code' => '69100', 'country' => 'FR']])
        ->assertOk()
        ->assertJsonPath('data.label', 'Bureau')
        ->assertJsonPath('data.is_default', true)
        ->assertJsonPath('data.address.city', 'Villeurbanne');

    expect($home->fresh()->is_default)->toBeFalse();
});

it('deletes an address and hands the default to another one', function () {
    $client = User::factory()->create();
    $home = UserAddress::factory()->for($client)->default()->create();
    $work = UserAddress::factory()->for($client)->create();

    $this->withHeaders(asUser($client))->deleteJson("/api/user/addresses/{$home->id}")->assertNoContent();

    expect(UserAddress::query()->find($home->id))->toBeNull()
        ->and(Address::query()->find($home->address_id))->toBeNull()
        ->and($work->fresh()->is_default)->toBeTrue();
});

it('returns 404 for the address of someone else', function () {
    $address = UserAddress::factory()->create();

    $this->withHeaders(asUser(User::factory()->create()))
        ->deleteJson("/api/user/addresses/{$address->id}")
        ->assertNotFound();

    expect($address->fresh())->not->toBeNull();
});
