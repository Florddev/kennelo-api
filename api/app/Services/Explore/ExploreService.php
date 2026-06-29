<?php

declare(strict_types=1);

namespace App\Services\Explore;

use App\Enums\AvailabilityStatusEnum;
use App\Enums\BookingStatusEnum;
use App\Enums\ReviewerTypeEnum;
use App\Models\Activity;
use App\Models\AnimalType;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ExploreService
{
    use HasHaversine;

    public const int PER_PAGE = 10;

    private const int MIN_SECTION_RESULTS = 3;

    /** @return list<string> */
    private function sectionIds(): array
    {
        return ['nearby', 'available_weekend', 'verified_pros', 'top_rated', 'new_hosts'];
    }

    private function applySection(string $sectionId, Builder $query, ?float $lat, ?float $lng): Builder
    {
        return match ($sectionId) {
            'nearby' => $this->nearbySection($query, $lat, $lng),
            'available_weekend' => $this->availableWeekendSection($query, $lat, $lng),
            'verified_pros' => $this->verifiedProsSection($query, $lat, $lng),
            'top_rated' => $this->topRatedSection($query),
            'new_hosts' => $this->newHostsSection($query),
            default => throw new InvalidArgumentException("Unknown explore section: {$sectionId}"),
        };
    }

    private function baseQuery(?User $user = null): Builder
    {
        return Activity::select('activities.*')
            ->with(['address', 'cycles.settings.animalType'])
            ->withAvg(
                ['reviews as avg_rating' => fn (Builder $q) => $q->where('is_published', true)],
                'overall_rating'
            )
            ->withCount(
                ['reviews as review_count' => fn (Builder $q) => $q->where('is_published', true)]
            )
            ->withIsFavorited($user)
            ->active()
            ->whereHas('manager', function ($q) {
                $q->where('stripe_charges_enabled', true)
                    ->whereNotNull('email_verified_at');
            });
    }

    /**
     * @return list<array{id: string, has_more: bool, activities: Collection}>
     */
    public function getSections(?float $lat, ?float $lng, ?User $user = null): array
    {
        $sections = [];

        foreach ($this->sectionIds() as $sectionId) {
            $activities = $this->applySection($sectionId, $this->baseQuery($user), $lat, $lng)
                ->limit(self::PER_PAGE + 1)
                ->get();

            if ($activities->count() < self::MIN_SECTION_RESULTS) {
                continue;
            }

            $hasMore = $activities->count() > self::PER_PAGE;

            $sections[] = [
                'id' => $sectionId,
                'has_more' => $hasMore,
                'activities' => $activities->take(self::PER_PAGE),
            ];
        }

        return $sections;
    }

    /**
     * @return array{activities: Collection, has_more: bool, page: int}|null
     */
    public function getSectionPage(string $sectionId, ?float $lat, ?float $lng, int $page, ?User $user = null): ?array
    {
        if (! in_array($sectionId, $this->sectionIds(), true)) {
            return null;
        }

        $offset = ($page - 1) * self::PER_PAGE;

        $activities = $this->applySection($sectionId, $this->baseQuery($user), $lat, $lng)
            ->offset($offset)
            ->limit(self::PER_PAGE + 1)
            ->get();

        return [
            'activities' => $activities->take(self::PER_PAGE),
            'has_more' => $activities->count() > self::PER_PAGE,
            'page' => $page,
        ];
    }

    /**
     * @return array{activities: Collection, has_more: bool, page: int}
     */
    public function search(array $input, ?float $lat, ?float $lng, int $page, ?User $user = null): array
    {
        $query = $this->baseQuery($user);

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
        $activities = $query->offset($offset)->limit(self::PER_PAGE + 1)->get();

        return [
            'activities' => $activities->take(self::PER_PAGE),
            'has_more' => $activities->count() > self::PER_PAGE,
            'page' => $page,
        ];
    }

    private function nearbySection(Builder $query, ?float $lat, ?float $lng): Builder
    {
        if ($lat === null || $lng === null || ! $this->supportsGeo()) {
            return $query
                ->orderByRaw('avg_rating DESC NULLS LAST')
                ->orderByDesc('review_count');
        }

        $query
            ->join('addresses as addr_nearby', 'addr_nearby.id', '=', 'activities.address_id')
            ->whereNotNull('addr_nearby.latitude')
            ->whereNotNull('addr_nearby.longitude')
            ->orderBy('distance')
            ->orderByRaw('avg_rating DESC NULLS LAST');

        $this->applyDistanceSelect($query, $lat, $lng, 'addr_nearby.latitude', 'addr_nearby.longitude');

        return $query;
    }

    private function availableWeekendSection(Builder $query, ?float $lat, ?float $lng): Builder
    {
        $saturday = Carbon::now()->next(Carbon::SATURDAY)->toDateString();
        $sunday = Carbon::now()->next(Carbon::SUNDAY)->toDateString();

        $query->whereHas('availabilities', function (Builder $q) use ($saturday, $sunday): void {
            $q->whereIn('date', [$saturday, $sunday])
                ->where('status', AvailabilityStatusEnum::OPEN->value);
        });

        if ($lat !== null && $lng !== null && $this->supportsGeo()) {
            $query
                ->join('addresses as addr_weekend', 'addr_weekend.id', '=', 'activities.address_id')
                ->whereNotNull('addr_weekend.latitude')
                ->whereNotNull('addr_weekend.longitude')
                ->orderBy('distance')
                ->orderByRaw('avg_rating DESC NULLS LAST');

            $this->applyDistanceSelect($query, $lat, $lng, 'addr_weekend.latitude', 'addr_weekend.longitude');
        } else {
            $query->orderByRaw('avg_rating DESC NULLS LAST');
        }

        return $query;
    }

    private function verifiedProsSection(Builder $query, ?float $lat, ?float $lng): Builder
    {
        $query
            ->whereNotNull('activities.siret')
            ->whereHas('manager', fn (Builder $q) => $q->where('is_id_verified', true));

        if ($lat !== null && $lng !== null && $this->supportsGeo()) {
            $query
                ->join('addresses as addr_pros', 'addr_pros.id', '=', 'activities.address_id')
                ->whereNotNull('addr_pros.latitude')
                ->whereNotNull('addr_pros.longitude')
                ->orderBy('distance')
                ->orderByRaw('avg_rating DESC NULLS LAST');

            $this->applyDistanceSelect($query, $lat, $lng, 'addr_pros.latitude', 'addr_pros.longitude');
        } else {
            $query->orderByRaw('avg_rating DESC NULLS LAST');
        }

        return $query;
    }

    private function topRatedSection(Builder $query): Builder
    {
        $reviewerType = ReviewerTypeEnum::USER->value;

        return $query
            ->whereRaw(
                '(SELECT COALESCE(AVG(r.overall_rating), 0) FROM reviews r INNER JOIN bookings b ON b.id = r.booking_id WHERE b.activity_id = activities.id AND r.is_published IS TRUE AND r.reviewer_type = ?) >= ?',
                [$reviewerType, 4.5]
            )
            ->whereRaw(
                '(SELECT COUNT(*) FROM reviews r INNER JOIN bookings b ON b.id = r.booking_id WHERE b.activity_id = activities.id AND r.is_published IS TRUE AND r.reviewer_type = ?) >= ?',
                [$reviewerType, 5]
            )
            ->orderByRaw('avg_rating DESC NULLS LAST')
            ->orderByDesc('review_count');
    }

    private function newHostsSection(Builder $query): Builder
    {
        return $query
            ->where('activities.created_at', '>=', Carbon::now()->subDays(60))
            ->orderByDesc('activities.created_at');
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

            $query->whereHas('cycles.settings', function (Builder $q) use ($code, $count, $dateFrom, $dateTo, $excluded): void {
                $q->whereHas('animalType', fn (Builder $at) => $at->where('code', $code));

                if ($dateFrom && $dateTo) {
                    $q->whereRaw(
                        'activities_cycles_settings.max_capacity - (
                            SELECT COALESCE(COUNT(bp.id), 0)
                            FROM booking_pet bp
                            JOIN bookings bk ON bk.id = bp.booking_id
                            JOIN pets p ON p.id = bp.pet_id
                            JOIN animal_types at ON at.id = p.animal_type_id
                            JOIN activities_cycles ac ON ac.id = activities_cycles_settings.activity_cycle_id
                            WHERE bk.activity_id = ac.activity_id
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
            'pro' => $query->whereNotNull('activities.siret'),
            'individual' => $query->whereNull('activities.siret'),
            default => null,
        };
    }

    private function applyMinRatingFilter(Builder $query, array $input): void
    {
        if (! isset($input['min_rating'])) {
            return;
        }

        $query->whereRaw(
            '(SELECT COALESCE(AVG(r.overall_rating), 0) FROM reviews r INNER JOIN bookings b ON b.id = r.booking_id WHERE b.activity_id = activities.id AND r.is_published IS TRUE AND r.reviewer_type = ?) >= ?',
            [ReviewerTypeEnum::USER->value, (float) $input['min_rating']]
        );
    }

    private function applyMaxPriceFilter(Builder $query, array $input): void
    {
        if (! isset($input['max_price'])) {
            return;
        }

        $query->whereHas(
            'cycles.settings',
            fn (Builder $q) => $q->where('price', '<=', (float) $input['max_price'])
        );
    }

    private function applyGeoJoin(Builder $query, float $lat, float $lng, array $input): void
    {
        $query
            ->join('addresses as addr_search', 'addr_search.id', '=', 'activities.address_id')
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
