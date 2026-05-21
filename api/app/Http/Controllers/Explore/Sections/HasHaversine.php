<?php

declare(strict_types=1);

namespace App\Http\Controllers\Explore\Sections;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

trait HasHaversine
{
    private function supportsGeo(): bool
    {
        return in_array(DB::connection()->getDriverName(), ['mysql', 'pgsql', 'mariadb'], true);
    }

    private function haversineExpression(float $lat, float $lng, string $latCol, string $lngCol): string
    {
        return "(6371 * acos(cos(radians({$lat})) * cos(radians({$latCol})) * cos(radians({$lngCol}) - radians({$lng})) + sin(radians({$lat})) * sin(radians({$latCol}))))";
    }

    private function applyDistanceSelect(Builder $query, float $lat, float $lng, string $latCol, string $lngCol): void
    {
        $query->addSelect(DB::raw(
            $this->haversineExpression($lat, $lng, $latCol, $lngCol).' AS distance'
        ));
    }
}
