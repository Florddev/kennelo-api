<?php

declare(strict_types=1);

namespace App\Services\Explore;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

trait HasHaversine
{
    private const float KM_PER_DEGREE_LATITUDE = 111.045;

    private function supportsGeo(): bool
    {
        return in_array(DB::connection()->getDriverName(), ['mysql', 'pgsql', 'mariadb'], true);
    }

    private function applyBoundingBox(Builder $query, float $lat, float $lng, float $radiusKm, string $latCol, string $lngCol): void
    {
        $latDelta = $radiusKm / self::KM_PER_DEGREE_LATITUDE;

        $query->whereBetween($latCol, [$lat - $latDelta, $lat + $latDelta]);

        $cosLat = cos(deg2rad($lat));

        if ($cosLat < 0.00001) {
            return;
        }

        $lngDelta = $radiusKm / (self::KM_PER_DEGREE_LATITUDE * $cosLat);
        $minLng = $lng - $lngDelta;
        $maxLng = $lng + $lngDelta;

        if ($minLng < -180.0 || $maxLng > 180.0) {
            return;
        }

        $query->whereBetween($lngCol, [$minLng, $maxLng]);
    }

    /**
     * @return array{0: string, 1: array<int, float>}
     */
    private function haversineExpression(float $lat, float $lng, string $latCol, string $lngCol): array
    {
        $sql = "(6371 * acos(cos(radians(?)) * cos(radians({$latCol})) * cos(radians({$lngCol}) - radians(?)) + sin(radians(?)) * sin(radians({$latCol}))))";

        return [$sql, [$lat, $lng, $lat]];
    }

    private function applyDistanceSelect(Builder $query, float $lat, float $lng, string $latCol, string $lngCol): void
    {
        [$sql, $bindings] = $this->haversineExpression($lat, $lng, $latCol, $lngCol);

        $query->selectRaw($sql.' AS distance', $bindings);
    }
}
