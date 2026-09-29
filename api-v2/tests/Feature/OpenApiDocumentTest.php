<?php

declare(strict_types=1);

use Illuminate\Support\Collection;

/**
 * @return array<string, mixed>
 */
function openApiDocument(): array
{
    return json_decode((string) file_get_contents(base_path('openapi.json')), true, flags: JSON_THROW_ON_ERROR);
}

/**
 * @return Collection<string, array<mixed>>
 */
function documentedOperations(): Collection
{
    return collect(openApiDocument()['paths'])->flatMap(fn (array $operations, string $path): array => collect($operations)
        ->mapWithKeys(fn (array $operation, string $method): array => [mb_strtoupper($method).' '.$path => $operation])
        ->all());
}

/**
 * @return array<string, mixed>
 */
function successSchema(string $operation): array
{
    return collect(documentedOperations()[$operation]['responses'])
        ->first(fn (array $response, string $status): bool => str_starts_with($status, '2'))['content']['application/json']['schema'] ?? [];
}

it('names every operation once, under one of the declared domain tags', function () {
    $operations = documentedOperations();
    $tags = collect(openApiDocument()['tags'])->pluck('name');

    expect($operations->pluck('operationId')->duplicates())->toBeEmpty()
        ->and($operations->reject(fn (array $operation): bool => count($operation['tags']) === 1 && $tags->contains($operation['tags'][0])))->toBeEmpty()
        ->and($operations['GET /admin/bookings']['tags'])->toBe(['Bookings'])
        ->and($operations['PUT /activities/{activity}/opening-hours']['tags'])->toBe(['Activities']);
});

it('takes the operation ids from the route names', function () {
    $operations = documentedOperations();

    expect($operations->pluck('operationId')->reject(fn (string $id): bool => preg_match('/^[a-z0-9-]+(\.[a-z0-9-]+)*$/', $id) === 1))->toBeEmpty()
        ->and($operations['POST /login']['operationId'])->toBe('login')
        ->and($operations['GET /admin/users']['operationId'])->toBe('admin.users.index')
        ->and($operations['POST /admin/bookings/{booking}/refunds']['operationId'])->toBe('admin.bookings.refunds.store')
        ->and($operations['GET /bookings/{booking}']['operationId'])->toBe('bookings.show')
        ->and($operations['POST /activities/{activity}/unit-types']['operationId'])->toBe('activities.unit-types.store');
});

it('returns single resources bare and keeps data, links and meta on paginated lists', function () {
    expect(successSchema('GET /bookings/{booking}'))->toBe(['$ref' => '#/components/schemas/BookingResourceWithActivityOrganizationServiceAddressUnitsPetsItemsPaymentsRefunds'])
        ->and(successSchema('GET /bookings')['properties'])->toHaveKeys(['data', 'links', 'meta'])
        ->and(successSchema('GET /animal-types')['type'])->toBe('array');
});

it('declares the relations an endpoint loads in concrete schemas', function () {
    $schemas = openApiDocument()['components']['schemas'];

    expect(file_get_contents(base_path('openapi.json')))->not->toContain('"allOf"')
        ->and($schemas['UserResourceWithRoles']['required'])->toContain('roles')
        ->and($schemas['UserResource']['required'])->not->toContain('roles')
        ->and(successSchema('GET /user'))->toBe(['$ref' => '#/components/schemas/UserResourceWithRoles']);
});

it('documents the page parameter on every paginated list', function () {
    $paginated = documentedOperations()->filter(fn (array $operation, string $key): bool => isset(successSchema($key)['properties']['meta']['properties']['current_page']));

    expect($paginated->keys())->toContain('GET /admin/users', 'GET /explore/activities/sections/{section}')
        ->and($paginated->reject(fn (array $operation): bool => collect($operation['parameters'] ?? [])->contains(fn (array $parameter): bool => $parameter['in'] === 'query' && $parameter['name'] === 'page')))->toBeEmpty();
});

it('types the platform roles with their enum', function () {
    expect(openApiDocument()['components']['schemas']['UserResource']['properties']['roles'])->toBe(['type' => 'array', 'items' => ['$ref' => '#/components/schemas/RoleEnum']]);
});

it('accepts the session of the web apps or the token of the mobile apps', function () {
    expect(openApiDocument()['security'])->toBe([['session' => []], ['token' => []]])
        ->and(documentedOperations()->keys())->not->toContain('POST /auth/token');
});

it('documents the session and the token forms of every sign-in', function () {
    $operations = documentedOperations();
    $schemas = openApiDocument()['components']['schemas'];
    $tokenForm = [
        'type' => 'object',
        'properties' => ['user' => ['$ref' => '#/components/schemas/UserResourceWithRoles'], 'token' => ['type' => 'string']],
        'required' => ['user', 'token'],
    ];

    foreach (['POST /login', 'POST /login/google', 'POST /login/two-factor-challenge', 'GET /magic-link/verify/{id}', 'POST /password/renew', 'POST /register'] as $operation) {
        expect(successSchema($operation)['anyOf'])->toContain(['$ref' => '#/components/schemas/UserResourceWithRoles'], $tokenForm);
    }

    expect(successSchema('POST /login')['anyOf'])->toContain([
        'type' => 'object',
        'properties' => ['two_factor' => ['type' => 'boolean'], 'pending_token' => ['type' => ['string', 'null']]],
        'required' => ['two_factor', 'pending_token'],
    ])
        ->and($schemas['LoginRequest']['properties'])->toHaveKey('device_name')
        ->and($schemas['GoogleAuthRequest']['properties'])->toHaveKey('device_name')
        ->and($schemas['TwoFactorChallengeRequest']['properties'])->toHaveKeys(['device_name', 'pending_token'])
        ->and($schemas['RenewPasswordRequest']['properties'])->toHaveKeys(['device_name', 'pending_token'])
        ->and(collect($operations['GET /magic-link/verify/{id}']['parameters'])->pluck('name'))->toContain('device_name');
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
