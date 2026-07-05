<?php

declare(strict_types=1);

namespace App\Services\Explore;

use App\Enums\PaginationEnum;
use App\Models\SearchLog;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;

class SearchLogService
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        $perPage = $filters['per_page'] ?? PaginationEnum::DEFAULT_PAGINATION->value();

        return SearchLog::query()
            ->when($filters['department'] ?? null, fn ($q, $dept) => $q->where('department', $dept))
            ->when(
                $filters['search'] ?? null,
                fn ($q, $search) => $q->where('location', 'like', "%{$search}%"),
            )
            ->when(
                $filters['from'] ?? null,
                fn ($q, $from) => $q->where('created_at', '>=', Carbon::parse($from)->startOfDay()),
            )
            ->with('user')
            ->latest()
            ->paginate($perPage);
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function record(array $input, ?float $lat, ?float $lng, int $resultsCount, ?string $userId): void
    {
        SearchLog::create([
            'user_id' => $userId,
            'location' => $input['location'] ?? null,
            'latitude' => $lat,
            'longitude' => $lng,
            'department' => $this->departmentFromLocation($input['location'] ?? null),
            'region' => null,
            'filters' => $this->extractFilters($input),
            'results_count' => $resultsCount,
        ]);
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function extractFilters(array $input): array
    {
        return array_filter([
            'host_type' => $input['host_type'] ?? null,
            'min_rating' => $input['min_rating'] ?? null,
            'max_price' => $input['max_price'] ?? null,
            'radius' => $input['radius'] ?? null,
            'sort' => $input['sort'] ?? null,
            'date_from' => $input['date_from'] ?? null,
            'date_to' => $input['date_to'] ?? null,
        ], fn ($value) => $value !== null);
    }

    private function departmentFromLocation(?string $location): ?string
    {
        if ($location === null) {
            return null;
        }

        if (preg_match('/\b(\d{5})\b/', $location, $matches) !== 1) {
            return null;
        }

        $postalCode = $matches[1];
        $prefix = substr($postalCode, 0, 2);

        if ($prefix === '20') {
            return in_array(substr($postalCode, 0, 3), ['200', '201'], true) ? '2A' : '2B';
        }

        if (str_starts_with($postalCode, '97') || str_starts_with($postalCode, '98')) {
            return substr($postalCode, 0, 3);
        }

        return $prefix;
    }
}
