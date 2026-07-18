<?php

declare(strict_types=1);

namespace App\Services\Prospect;

use App\Services\Prospect\Contracts\PlaceDiscoveryService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;

class ApifyDiscoveryService implements PlaceDiscoveryService
{
    public function isConfigured(): bool
    {
        return filled(config('services.apify.token'))
            && filled(config('services.apify.actor'));
    }

    public function discover(array $searchTerms, string $location, int $maxResults): array
    {
        $actor = (string) str((string) config('services.apify.actor'))->replace('/', '~');
        $endpoint = rtrim((string) config('services.apify.base_url'), '/')
            ."/acts/{$actor}/run-sync-get-dataset-items";

        $response = Http::timeout(300)
            ->acceptJson()
            ->post($endpoint.'?token='.config('services.apify.token'), [
                'searchStringsArray' => array_values($searchTerms),
                'locationQuery' => $location,
                'maxCrawledPlacesPerSearch' => $maxResults,
                'language' => 'fr',
                'countryCode' => 'fr',
                'scrapePlaceDetailPage' => false,
                'scrapeContacts' => false,
                'maximumLeadsEnrichmentRecords' => 0,
                'maxReviews' => 0,
                'maxImages' => 0,
                'scrapeReviewsPersonalData' => false,
            ])
            ->throw();

        return $this->normalize($response->json() ?? []);
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array<string, mixed>>
     */
    private function normalize(array $items): array
    {
        return collect($items)
            ->filter(fn (array $item): bool => ! empty($item['placeId']) && ! empty($item['title']))
            ->map(fn (array $item): array => $this->mapItem($item))
            ->unique('google_place_id')
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    private function mapItem(array $item): array
    {
        $postalCode = Arr::get($item, 'postalCode');
        $categories = Arr::get($item, 'categories', []);

        return [
            'name' => Arr::get($item, 'title'),
            'address' => Arr::get($item, 'address'),
            'city' => Arr::get($item, 'city'),
            'postal_code' => $postalCode,
            'department' => department_from_postal_code(is_string($postalCode) ? $postalCode : null),
            'country' => (string) str((string) Arr::get($item, 'countryCode', 'FR'))->upper(),
            'latitude' => Arr::get($item, 'location.lat'),
            'longitude' => Arr::get($item, 'location.lng'),
            'phone' => Arr::get($item, 'phoneUnformatted') ?? Arr::get($item, 'phone'),
            'website' => Arr::get($item, 'website'),
            'google_rating' => Arr::get($item, 'totalScore'),
            'google_reviews_count' => Arr::get($item, 'reviewsCount'),
            'google_place_id' => Arr::get($item, 'placeId'),
            'category' => Arr::get($item, 'categoryName'),
            'services' => is_array($categories) ? array_values($categories) : null,
        ];
    }
}
