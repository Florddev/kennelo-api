<?php

declare(strict_types=1);

namespace App\Services\Explore;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

trait HasHaversine
{
    private function supportsGeo(): bool
    {
        return in_array(DB::connection()->getDriverName(), ['mysql', 'pgsql', 'mariadb'], true);
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
