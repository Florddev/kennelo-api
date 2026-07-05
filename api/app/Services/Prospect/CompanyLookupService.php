<?php

declare(strict_types=1);

namespace App\Services\Prospect;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;

class CompanyLookupService
{
    private const MAX_RETRIES = 3;

    public function searchByText(string $query): ?array
    {
        return $this->firstResult(['q' => $query, 'per_page' => 1]);
    }

    public function findBySiret(string $siret): ?array
    {
        return $this->firstResult(['q' => $siret, 'per_page' => 1]);
    }

    public function findBySiren(string $siren): ?array
    {
        return $this->firstResult(['q' => $siren, 'per_page' => 1]);
    }

    private function firstResult(array $params): ?array
    {
        $response = $this->request($params);

        if ($response === null) {
            return null;
        }

        $result = $response->json('results.0');

        return is_array($result) ? $this->normalize($result) : null;
    }

    private function request(array $params, int $attempt = 1): ?Response
    {
        $endpoint = rtrim((string) config('services.recherche_entreprises.url'), '/').'/search';

        $response = Http::timeout(15)
            ->acceptJson()
            ->get($endpoint, $params);

        if ($response->status() === 429 && $attempt < self::MAX_RETRIES) {
            $retryAfter = (int) ($response->header('Retry-After') ?: 1);
            usleep(max(1, $retryAfter) * 1_000_000);

            return $this->request($params, $attempt + 1);
        }

        if ($response->failed()) {
            return null;
        }

        return $response;
    }

    /**
     * @param  array<string, mixed>  $result
     * @return array<string, mixed>
     */
    private function normalize(array $result): array
    {
        $siege = Arr::get($result, 'siege', []);

        return [
            'siren' => Arr::get($result, 'siren'),
            'siret' => Arr::get($siege, 'siret'),
            'legal_name' => Arr::get($result, 'nom_complet'),
            'ape_code' => Arr::get($result, 'activite_principale'),
            'administrative_state' => Arr::get($result, 'etat_administratif'),
            'creation_date' => Arr::get($result, 'date_creation'),
            'address' => Arr::get($siege, 'adresse'),
            'postal_code' => Arr::get($siege, 'code_postal'),
            'city' => Arr::get($siege, 'libelle_commune'),
            'department' => Arr::get($siege, 'departement'),
            'latitude' => Arr::get($siege, 'latitude'),
            'longitude' => Arr::get($siege, 'longitude'),
        ];
    }
}
