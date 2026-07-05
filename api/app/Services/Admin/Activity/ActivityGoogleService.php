<?php

declare(strict_types=1);

namespace App\Services\Admin\Activity;

use App\Models\Activity;
use App\Services\Prospect\Contracts\PlaceDiscoveryService;
use Illuminate\Support\Carbon;

class ActivityGoogleService
{
    public function __construct(
        private PlaceDiscoveryService $discovery,
    ) {}

    public function searchGoogle(Activity $activity): ?array
    {
        $activity->loadMissing('address');
        $address = $activity->address;
        $city = $address !== null ? (string) $address->city : '';

        $query = trim(implode(' ', array_filter([
            $activity->name,
            $address?->line1,
            $city,
        ])));

        $results = $this->discovery->discover([$query], $city, 1);

        if ($results === []) {
            return null;
        }

        return $this->toCandidate($results[0]);
    }

    public function linkGoogle(Activity $activity, array $data): Activity
    {
        $placeId = $data['google_place_id'];

        $activity->update([
            'google_place_id' => $placeId,
            'google_rating' => $data['google_rating'] ?? null,
            'google_reviews_count' => $data['google_reviews_count'] ?? null,
            'google_maps_url' => $data['google_maps_url'] ?? $this->mapsUrlFromPlaceId($placeId),
            'google_synced_at' => Carbon::now(),
        ]);

        return $activity->fresh(['address', 'manager', 'reviewedBy']);
    }

    public function unlinkGoogle(Activity $activity): Activity
    {
        $activity->update([
            'google_place_id' => null,
            'google_rating' => null,
            'google_reviews_count' => null,
            'google_maps_url' => null,
            'google_synced_at' => null,
        ]);

        return $activity->fresh(['address', 'manager', 'reviewedBy']);
    }

    /**
     * @param  array<string, mixed>  $place
     * @return array<string, mixed>
     */
    private function toCandidate(array $place): array
    {
        $placeId = $place['google_place_id'] ?? null;

        return [
            'google_place_id' => $placeId,
            'name' => $place['name'] ?? null,
            'address' => $place['address'] ?? null,
            'city' => $place['city'] ?? null,
            'phone' => $place['phone'] ?? null,
            'website' => $place['website'] ?? null,
            'google_rating' => $place['google_rating'] ?? null,
            'google_reviews_count' => $place['google_reviews_count'] ?? null,
            'google_maps_url' => $placeId !== null ? $this->mapsUrlFromPlaceId($placeId) : null,
        ];
    }

    private function mapsUrlFromPlaceId(string $placeId): string
    {
        return 'https://www.google.com/maps/place/?q=place_id:'.$placeId;
    }
}
