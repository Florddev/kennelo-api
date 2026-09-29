<?php

declare(strict_types=1);

use Illuminate\Support\Collection;

/**
 * @return Collection<string, array<mixed>>
 */
function documentedOperations(): Collection
{
    $document = json_decode((string) file_get_contents(base_path('openapi.json')), true, flags: JSON_THROW_ON_ERROR);

    return collect($document['paths'])->flatMap(fn (array $operations, string $path): array => collect($operations)
        ->mapWithKeys(fn (array $operation, string $method): array => [mb_strtoupper($method).' '.$path => $operation])
        ->all());
}

it('names every operation once, under a single tag', function () {
    $operations = documentedOperations();

    expect($operations->pluck('operationId')->duplicates())->toBeEmpty()
        ->and($operations->reject(fn (array $operation): bool => count($operation['tags']) === 1))->toBeEmpty();
});

it('derives stable operation ids from the route name or the controller', function () {
    $operations = documentedOperations();

    expect($operations['POST /login']['operationId'])->toBe('login')
        ->and($operations['POST /admin/bookings/{booking}/refunds']['operationId'])->toBe('admin.booking.refund')
        ->and($operations['GET /bookings/{booking}']['operationId'])->toBe('booking.show')
        ->and($operations['POST /activities/{activity}/unit-types']['operationId'])->toBe('stay.unitType.store');
});

it('marks the operations open without a session', function () {
    $operations = documentedOperations();

    expect($operations['GET /explore/search']['security'])->toBe([])
        ->and($operations['POST /login']['security'])->toBe([])
        ->and($operations['POST /bookings'])->not->toHaveKey('security');
});

it('leaves out the routes the fronts do not call', function () {
    $operations = documentedOperations()->keys();

    expect($operations)->not->toContain('POST /webhooks/stripe')
        ->and($operations)->not->toContain('GET /broadcasting/auth');
});
