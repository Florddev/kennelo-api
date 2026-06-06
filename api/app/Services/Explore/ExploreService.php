<?php

declare(strict_types=1);

namespace App\Services\Explore;

use App\Contracts\ExploreSection;
use App\Enums\AvailabilityStatusEnum;
use App\Enums\BookingStatusEnum;
use App\Enums\ReviewerTypeEnum;
use App\Models\AnimalType;
use App\Models\Establishment;
use App\Services\Explore\Sections\AvailableWeekendSection;
use App\Services\Explore\Sections\HasHaversine;
use App\Services\Explore\Sections\NearbySection;
use App\Services\Explore\Sections\NewHostsSection;
use App\Services\Explore\Sections\TopRatedSection;
use App\Services\Explore\Sections\VerifiedProsSection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class ExploreService
{
    use HasHaversine;

    public const int PER_PAGE = 10;

    private const int MIN_SECTION_RESULTS = 3;

    /** @return list<ExploreSection> */
    private function sections(): array
    {
        return [
            new NearbySection,
            new AvailableWeekendSection,
            new VerifiedProsSection,
            new TopRatedSection,
            new NewHostsSection,
        ];
    }

    private function baseQuery(): Builder
    {
        return Establishment::select('establishments.*')
            ->with(['address', 'capacities.animalType'])
            ->withAvg(
                ['reviews as avg_rating' => fn (Builder $q) => $q->whereRaw('"is_published" IS TRUE')],
                'overall_rating'
            )
            ->withCount(
                ['reviews as review_count' => fn (Builder $q) => $q->whereRaw('"is_published" IS TRUE')]
            )
            ->active()
            ->whereHas('manager', function ($q) {
                $q->where('stripe_charges_enabled', true);
            });
    }

    /**
     * @return list<array{id: string, has_more: bool, establishments: Collection}>
     */
    public function getSections(?float $lat, ?float $lng): array
    {
        $sections = [];

        foreach ($this->sections() as $section) {
            $establishments = $section->apply($this->baseQuery(), $lat, $lng)
                ->limit(self::PER_PAGE + 1)
                ->get();

            if ($establishments->count() < self::MIN_SECTION_RESULTS) {
                continue;
            }

            $hasMore = $establishments->count() > self::PER_PAGE;

            $sections[] = [
                'id' => $section->id(),
                'has_more' => $hasMore,
                'establishments' => $establishments->take(self::PER_PAGE),
            ];
        }

        return $sections;
    }

    /**
     * @return array{establishments: Collection, has_more: bool, page: int}|null
     */
    public function getSectionPage(string $sectionId, ?float $lat, ?float $lng, int $page): ?array
    {
        $section = collect($this->sections())
            ->first(fn (ExploreSection $s) => $s->id() === $sectionId);

        if ($section === null) {
            return null;
        }

        $offset = ($page - 1) * self::PER_PAGE;

        $establishments = $section->apply($this->baseQuery(), $lat, $lng)
            ->offset($offset)
            ->limit(self::PER_PAGE + 1)
            ->get();

        return [
            'establishments' => $establishments->take(self::PER_PAGE),
            'has_more' => $establishments->count() > self::PER_PAGE,
            'page' => $page,
        ];
    }

    /**
     * @return array{establishments: Collection, has_more: bool, page: int}
     */
    public function search(array $input, ?float $lat, ?float $lng, int $page): array
    {
        $query = $this->baseQuery();

        $this->applyLocationFilter($query, $input);
        $this->applyAnimalCountsFilter($query, $input);
        $this->applyDateRangeFilter($query, $input);
        $this->applyHostTypeFilter($query, $input);
        $this->applyMinRatingFilter($query, $input);
        $this->applyMaxPriceFilter($query, $input);

        $geoAvailable = $lat !== null && $lng !== null && $this->supportsGeo();

        if ($geoAvailable) {
            $this->applyGeoJoin($query, $lat, $lng, $input);
        }

        $this->applySort($query, $input['sort'] ?? 'rating', $geoAvailable);

        $offset = ($page - 1) * self::PER_PAGE;
        $establishments = $query->offset($offset)->limit(self::PER_PAGE + 1)->get();

        return [
            'establishments' => $establishments->take(self::PER_PAGE),
            'has_more' => $establishments->count() > self::PER_PAGE,
            'page' => $page,
        ];
    }

    private function applyLocationFilter(Builder $query, array $input): void
    {
        if (empty($input['location'])) {
            return;
        }

        $location = $input['location'];

        $query->whereHas('address', function (Builder $q) use ($location): void {
            $q->where('city', 'like', "%{$location}%")
                ->orWhere('region', 'like', "%{$location}%")
                ->orWhere('country', 'like', "%{$location}%");
        });
    }

    private function applyAnimalCountsFilter(Builder $query, array $input): void
    {
        $codes = AnimalType::pluck('code')->all();
        $excluded = [BookingStatusEnum::CANCELLED->value, BookingStatusEnum::COMPLETED->value];
        $dateFrom = $input['date_from'] ?? null;
        $dateTo = $input['date_to'] ?? null;

        foreach ($codes as $code) {
            $value = $input[$code] ?? null;
            if ($value === null || ! is_numeric($value) || (int) $value <= 0) {
                continue;
            }

            $count = (int) $value;

            $query->whereHas('capacities', function (Builder $q) use ($code, $count, $dateFrom, $dateTo, $excluded): void {
                $q->whereHas('animalType', fn (Builder $at) => $at->where('code', $code));

                if ($dateFrom && $dateTo) {
                    $q->whereRaw(
                        'establishment_capacities.max_capacity - (
                            SELECT COALESCE(COUNT(bp.id), 0)
                            FROM booking_pet bp
                            JOIN bookings bk ON bk.id = bp.booking_id
                            JOIN pets p ON p.id = bp.pet_id
                            JOIN animal_types at ON at.id = p.animal_type_id
                            WHERE bk.establishment_id = establishment_capacities.establishment_id
                            AND at.code = ?
                            AND bk.status NOT IN (?, ?)
                            AND bk.check_in_date < ?
                            AND bk.check_out_date > ?
                        ) >= ?',
                        [$code, ...$excluded, $dateTo, $dateFrom, $count]
                    );
                } else {
                    $q->where('max_capacity', '>=', $count);
                }
            });
        }
    }

    private function applyDateRangeFilter(Builder $query, array $input): void
    {
        $dateFrom = $input['date_from'] ?? null;
        $dateTo = $input['date_to'] ?? null;

        if (! $dateFrom || ! $dateTo) {
            return;
        }

        $query->whereDoesntHave('availabilities', function (Builder $q) use ($dateFrom, $dateTo): void {
            $q->where('status', AvailabilityStatusEnum::CLOSED->value)
                ->where('date', '>=', $dateFrom)
                ->where('date', '<', $dateTo);
        });
    }

    private function applyHostTypeFilter(Builder $query, array $input): void
    {
        $hostType = $input['host_type'] ?? null;

        match ($hostType) {
            'pro' => $query->whereNotNull('establishments.siret'),
            'individual' => $query->whereNull('establishments.siret'),
            default => null,
        };
    }

    private function applyMinRatingFilter(Builder $query, array $input): void
    {
        if (! isset($input['min_rating'])) {
            return;
        }

        $query->whereRaw(
            '(SELECT COALESCE(AVG(r.overall_rating), 0) FROM reviews r INNER JOIN bookings b ON b.id = r.booking_id WHERE b.establishment_id = establishments.id AND r.is_published IS TRUE AND r.reviewer_type = ?) >= ?',
            [ReviewerTypeEnum::USER->value, (float) $input['min_rating']]
        );
    }

    private function applyMaxPriceFilter(Builder $query, array $input): void
    {
        if (! isset($input['max_price'])) {
            return;
        }

        $query->whereHas(
            'capacities',
            fn (Builder $q) => $q->where('price_per_night', '<=', (float) $input['max_price'])
        );
    }

    private function applyGeoJoin(Builder $query, float $lat, float $lng, array $input): void
    {
        $query
            ->join('addresses as addr_search', 'addr_search.id', '=', 'establishments.address_id')
            ->addSelect(DB::raw(
                $this->haversineExpression($lat, $lng, 'addr_search.latitude', 'addr_search.longitude').' AS distance'
            ))
            ->whereNotNull('addr_search.latitude')
            ->whereNotNull('addr_search.longitude');

        if (isset($input['radius'])) {
            $query->whereRaw(
                '(6371 * acos(LEAST(1.0, cos(radians(?)) * cos(radians(addr_search.latitude)) * cos(radians(addr_search.longitude) - radians(?)) + sin(radians(?)) * sin(radians(addr_search.latitude))))) <= ?',
                [$lat, $lng, $lat, (float) $input['radius']]
            );
        }
    }

    private function applySort(Builder $query, string $sort, bool $geoAvailable): void
    {
        match ($sort) {
            'distance' => $geoAvailable
                ? $query->orderBy('distance')
                : $query->orderByRaw('avg_rating DESC NULLS LAST'),
            'price' => $query->orderByRaw('COALESCE(min_price, 0) ASC'),
            default => $query->orderByRaw('avg_rating DESC NULLS LAST'),
        };
    }
}
