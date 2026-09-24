<?php

declare(strict_types=1);

namespace App\Services\Organization;

use App\Services\Organization\Exceptions\CompanyLookupUnavailableException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Interroge l'API publique « Recherche d'entreprises » (recherche-entreprises.api.gouv.fr).
 */
class CompanyLookupService
{
    /**
     * Retourne l'entreprise dont le SIREN est exactement celui demandé : la recherche plein texte
     * de l'API peut renvoyer une autre entreprise en premier résultat.
     *
     * @return array<string, mixed>|null
     */
    public function findBySiren(string $siren): ?array
    {
        try {
            $results = Http::baseUrl((string) config('services.recherche_entreprises.url'))
                ->acceptJson()
                ->connectTimeout(3)
                ->timeout(10)
                ->retry([200, 1000], 0, fn (Throwable $exception): bool => $exception instanceof ConnectionException
                    || ($exception instanceof RequestException
                        && ($exception->response->serverError() || $exception->response->status() === 429)))
                ->get('/search', ['q' => $siren, 'per_page' => 5])
                ->throw()
                ->json('results', []);
        } catch (ConnectionException|RequestException $exception) {
            throw new CompanyLookupUnavailableException(previous: $exception);
        }

        $company = collect(is_array($results) ? $results : [])
            ->first(fn (mixed $result): bool => is_array($result) && Arr::get($result, 'siren') === $siren);

        return is_array($company) ? $this->normalize($company) : null;
    }

    /**
     * @param  array<string, mixed>  $company
     * @return array<string, mixed>
     */
    private function normalize(array $company): array
    {
        $headquarters = Arr::get($company, 'siege', []);

        $street = collect([
            Arr::get($headquarters, 'numero_voie'),
            Arr::get($headquarters, 'indice_repetition'),
            Arr::get($headquarters, 'type_voie'),
            Arr::get($headquarters, 'libelle_voie'),
        ])->filter()->implode(' ');

        return [
            'siren' => Arr::get($company, 'siren'),
            'siret' => Arr::get($headquarters, 'siret'),
            'legal_name' => Arr::get($company, 'nom_complet'),
            'ape_code' => Arr::get($company, 'activite_principale'),
            'is_active' => Arr::get($company, 'etat_administratif') === 'A',
            'created_on' => Arr::get($company, 'date_creation'),
            'address' => [
                'line1' => $street !== '' ? $street : Arr::get($headquarters, 'adresse'),
                'line2' => Arr::get($headquarters, 'complement_adresse'),
                'postal_code' => Arr::get($headquarters, 'code_postal'),
                'city' => Arr::get($headquarters, 'libelle_commune'),
                'country' => 'FR',
                'latitude' => is_numeric($latitude = Arr::get($headquarters, 'latitude')) ? (float) $latitude : null,
                'longitude' => is_numeric($longitude = Arr::get($headquarters, 'longitude')) ? (float) $longitude : null,
            ],
        ];
    }
}
