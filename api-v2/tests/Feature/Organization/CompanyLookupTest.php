<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Http;

function registryCompany(string $siren): array
{
    return [
        'siren' => $siren,
        'nom_complet' => 'PENSION DES LILAS',
        'activite_principale' => '96.09Z',
        'etat_administratif' => 'A',
        'date_creation' => '2019-04-01',
        'siege' => [
            'siret' => $siren.'00012',
            'numero_voie' => '3',
            'type_voie' => 'RUE',
            'libelle_voie' => 'DES LILAS',
            'code_postal' => '69003',
            'libelle_commune' => 'LYON',
            'latitude' => '45.7597',
            'longitude' => '4.8422',
        ],
    ];
}

it('returns the company whose SIREN matches exactly', function () {
    Http::preventStrayRequests();
    Http::fake(['recherche-entreprises.api.gouv.fr/search*' => Http::response(['results' => [
        registryCompany('987654321'),
        registryCompany('123456789'),
    ]])]);

    $this->withHeaders(asUser(User::factory()->create()))
        ->getJson('/api/organizations/company-lookup/123456789')
        ->assertOk()
        ->assertJsonPath('siren', '123456789')
        ->assertJsonPath('legal_name', 'PENSION DES LILAS')
        ->assertJsonPath('is_active', true)
        ->assertJsonPath('address.line1', '3 RUE DES LILAS')
        ->assertJsonPath('address.latitude', 45.7597);
});

it('returns 404 when no result has this SIREN', function () {
    Http::preventStrayRequests();
    Http::fake(['recherche-entreprises.api.gouv.fr/search*' => Http::response(['results' => [registryCompany('987654321')]])]);

    $this->withHeaders(asUser(User::factory()->create()))
        ->getJson('/api/organizations/company-lookup/123456789')
        ->assertNotFound()
        ->assertJsonPath('message', __('organization.company_not_found'));
});

it('returns 503 when the register cannot be reached', function () {
    Http::preventStrayRequests();
    Http::fake(['recherche-entreprises.api.gouv.fr/search*' => Http::failedConnection()]);

    $this->withHeaders(asUser(User::factory()->create()))
        ->getJson('/api/organizations/company-lookup/123456789')
        ->assertServiceUnavailable()
        ->assertJsonPath('message', __('organization.company_lookup_unavailable'));
});

it('returns 404 for a value that is not a SIREN', function () {
    Http::preventStrayRequests();

    $this->withHeaders(asUser(User::factory()->create()))
        ->getJson('/api/organizations/company-lookup/12345')
        ->assertNotFound();
});
