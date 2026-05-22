<?php

declare(strict_types=1);

namespace App\Http\Controllers\Explore;

use App\Contracts\ExploreSection;
use App\Enums\ApiStatus;
use App\Enums\AvailabilityStatus;
use App\Enums\BookingStatus;
use App\Enums\ReviewerType;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Explore\Sections\AvailableWeekendSection;
use App\Http\Controllers\Explore\Sections\NearbySection;
use App\Http\Controllers\Explore\Sections\NewHostsSection;
use App\Http\Controllers\Explore\Sections\TopRatedSection;
use App\Http\Controllers\Explore\Sections\VerifiedProsSection;
use App\Http\Resources\ExploreEstablishmentResource;
use App\Models\AnimalType;
use App\Models\Establishment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ExploreController extends Controller
{
    private const int PER_PAGE = 10;

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
                ['reviews as avg_rating' => fn (Builder $q) => $q->where('is_published', true)],
                'overall_rating'
            )
            ->withCount(
                ['reviews as review_count' => fn (Builder $q) => $q->where('is_published', true)]
            )
            ->active()
            ->whereNull('establishments.deleted_at');
    }

    private function supportsGeo(): bool
    {
        return in_array(DB::connection()->getDriverName(), ['mysql', 'pgsql', 'mariadb'], true);
    }

    private function resolveCoords(Request $request): array
    {
        $lat = $request->filled('lat') ? (float) $request->input('lat') : null;
        $lng = $request->filled('lng') ? (float) $request->input('lng') : null;

        return [$lat, $lng];
    }

    private function resolveAnimalCounts(Request $request): array
    {
        $codes = AnimalType::pluck('code')->all();
        $counts = [];
        foreach ($codes as $code) {
            $value = $request->input($code);
            if ($value !== null && is_numeric($value) && (int) $value > 0) {
                $counts[$code] = (int) $value;
            }
        }

        return $counts;
    }

    private function applyAnimalCountsFilter(Builder $query, array $animalCounts, ?string $dateFrom, ?string $dateTo): void
    {
        $excluded = [BookingStatus::CANCELLED->value, BookingStatus::COMPLETED->value];

        foreach ($animalCounts as $code => $count) {
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

    private function applyDateRangeFilter(Builder $query, string $dateFrom, string $dateTo): void
    {
        $query->whereDoesntHave('availabilities', function (Builder $q) use ($dateFrom, $dateTo): void {
            $q->where('status', AvailabilityStatus::CLOSED->value)
                ->where('date', '>=', $dateFrom)
                ->where('date', '<', $dateTo);
        });
    }

    public function establishments(Request $request): JsonResponse
    {
        [$lat, $lng] = $this->resolveCoords($request);

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
                'establishments' => ExploreEstablishmentResource::collection(
                    $establishments->take(self::PER_PAGE)
                )->resolve($request),
            ];
        }

        return response()->json([
            'data' => ['sections' => $sections],
            'status' => ApiStatus::SUCCESS->value,
            'timestamp' => human_date(Carbon::now()),
        ]);
    }

    public function sectionPage(Request $request, string $sectionId): JsonResponse
    {
        [$lat, $lng] = $this->resolveCoords($request);
        $page = max(1, (int) $request->input('page', 1));

        $section = collect($this->sections())
            ->first(fn (ExploreSection $s) => $s->id() === $sectionId);

        if ($section === null) {
            abort(404);
        }

        $offset = ($page - 1) * self::PER_PAGE;

        $establishments = $section->apply($this->baseQuery(), $lat, $lng)
            ->offset($offset)
            ->limit(self::PER_PAGE + 1)
            ->get();

        $hasMore = $establishments->count() > self::PER_PAGE;

        return response()->json([
            'data' => [
                'establishments' => ExploreEstablishmentResource::collection(
                    $establishments->take(self::PER_PAGE)
                )->resolve($request),
                'meta' => [
                    'current_page' => $page,
                    'per_page' => self::PER_PAGE,
                    'has_more' => $hasMore,
                ],
            ],
            'status' => ApiStatus::SUCCESS->value,
            'timestamp' => human_date(Carbon::now()),
        ]);
    }

    public function search(Request $request): JsonResponse
    {
        [$lat, $lng] = $this->resolveCoords($request);
        $page = max(1, (int) $request->input('page', 1));
        $dateFrom = $request->filled('date_from') ? (string) $request->input('date_from') : null;
        $dateTo = $request->filled('date_to') ? (string) $request->input('date_to') : null;

        $query = $this->baseQuery();

        if ($request->filled('location')) {
            $location = $request->input('location');
            $query->whereHas('address', function (Builder $q) use ($location): void {
                $q->where('city', 'like', "%{$location}%")
                    ->orWhere('region', 'like', "%{$location}%")
                    ->orWhere('country', 'like', "%{$location}%");
            });
        }

        $animalCounts = $this->resolveAnimalCounts($request);
        if (! empty($animalCounts)) {
            $this->applyAnimalCountsFilter($query, $animalCounts, $dateFrom, $dateTo);
        }

        if ($dateFrom && $dateTo) {
            $this->applyDateRangeFilter($query, $dateFrom, $dateTo);
        }

        if ($request->filled('host_type')) {
            match ($request->input('host_type')) {
                'pro' => $query->whereNotNull('establishments.siret'),
                'individual' => $query->whereNull('establishments.siret'),
                default => null,
            };
        }

        if ($request->filled('min_rating')) {
            $minRating = (float) $request->input('min_rating');
            $reviewerType = ReviewerType::USER->value;
            $query->whereRaw(
                '(SELECT COALESCE(AVG(r.overall_rating), 0) FROM reviews r INNER JOIN bookings b ON b.id = r.booking_id WHERE b.establishment_id = establishments.id AND r.is_published = 1 AND r.reviewer_type = ?) >= ?',
                [$reviewerType, $minRating]
            );
        }

        if ($request->filled('max_price')) {
            $query->whereHas(
                'capacities',
                fn (Builder $q) => $q->where('price_per_night', '<=', (float) $request->input('max_price'))
            );
        }

        $geoAvailable = $lat !== null && $lng !== null && $this->supportsGeo();

        if ($geoAvailable) {
            $query
                ->join('addresses as addr_search', 'addr_search.id', '=', 'establishments.address_id')
                ->addSelect(DB::raw(
                    "(6371 * acos(cos(radians({$lat})) * cos(radians(addr_search.latitude)) * cos(radians(addr_search.longitude) - radians({$lng})) + sin(radians({$lat})) * sin(radians(addr_search.latitude)))) AS distance"
                ))
                ->whereNotNull('addr_search.latitude')
                ->whereNotNull('addr_search.longitude');
        }

        $sort = $request->input('sort', 'rating');
        match ($sort) {
            'distance' => $geoAvailable ? $query->orderBy('distance') : $query->orderByRaw('COALESCE(avg_rating, 0) DESC'),
            'price' => $query->orderByRaw('COALESCE(min_price, 0) ASC'),
            default => $query->orderByRaw('COALESCE(avg_rating, 0) DESC'),
        };

        $offset = ($page - 1) * self::PER_PAGE;
        $establishments = $query->offset($offset)->limit(self::PER_PAGE + 1)->get();
        $hasMore = $establishments->count() > self::PER_PAGE;

        return response()->json([
            'data' => [
                'establishments' => ExploreEstablishmentResource::collection(
                    $establishments->take(self::PER_PAGE)
                )->resolve($request),
                'meta' => [
                    'current_page' => $page,
                    'per_page' => self::PER_PAGE,
                    'has_more' => $hasMore,
                ],
            ],
            'status' => ApiStatus::SUCCESS->value,
            'timestamp' => human_date(Carbon::now()),
        ]);
    }
}
