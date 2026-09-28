<?php

declare(strict_types=1);

namespace App\Services\Explore;

use App\Enums\LocationModeEnum;
use App\Enums\OrganizationLegalFormEnum;
use App\Models\Activity;
use App\Models\AnimalType;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Recherche d'activités réservables pour les clients : sections de la page d'accueil et recherche par filtres.
 *
 * Avec une position, la distance est calculée en SQL (formule de haversine) depuis l'adresse de l'activité.
 * Chez le pro, elle doit être inférieure au rayon de recherche ; à domicile, inférieure au rayon de déplacement
 * de l'activité. Une activité uniquement à distance n'a pas de distance.
 */
class ExploreService
{
    public const int PER_PAGE = 10;

    /** @var list<string> */
    public const array SECTIONS = ['nearby', 'available_this_weekend', 'top_rated', 'verified_pros', 'new_hosts'];

    /** Une section de la page d'accueil qui a moins de résultats est omise. */
    private const int MIN_SECTION_RESULTS = 3;

    private const int NEW_HOSTS_DAYS = 60;

    private const float KM_PER_DEGREE_LATITUDE = 111.045;

    /**
     * Distance en kilomètres entre le point cherché (liaisons : latitude, longitude, latitude) et l'activité,
     * NULL si l'adresse n'a pas de coordonnées. least() borne le cosinus à 1 malgré les arrondis ; comme il ignore
     * les NULL sous PostgreSQL, l'absence de coordonnées est testée à part.
     */
    private const string DISTANCE_SQL = '(case when activity_address.latitude is null or activity_address.longitude is null then null'
        .' else 6371 * acos(least(1.0, cos(radians(?)) * cos(radians(activity_address.latitude))'
        .' * cos(radians(activity_address.longitude) - radians(?)) + sin(radians(?)) * sin(radians(activity_address.latitude)))) end)';

    public function __construct(
        private readonly AvailabilityService $availability,
    ) {}

    /**
     * @param  array{lat?: float|string|null, lng?: float|string|null}  $position
     * @return list<array{id: string, has_more: bool, activities: Collection<int, Activity>}>
     */
    public function sections(array $position, ?User $user): array
    {
        $sections = [];

        foreach (self::SECTIONS as $section) {
            $query = $this->sectionQuery($section, $position, $user);

            if ($query === null) {
                continue;
            }

            $activities = $query->limit(self::PER_PAGE + 1)->get();

            if ($activities->count() < self::MIN_SECTION_RESULTS) {
                continue;
            }

            $sections[] = [
                'id' => $section,
                'has_more' => $activities->count() > self::PER_PAGE,
                'activities' => $activities->take(self::PER_PAGE),
            ];
        }

        return $sections;
    }

    /**
     * Une page d'une section, ou null si la section n'a pas de sens sans position (« près de chez vous »).
     *
     * @param  array{lat?: float|string|null, lng?: float|string|null}  $position
     */
    public function section(string $section, array $position, ?User $user): ?Paginator
    {
        return $this->sectionQuery($section, $position, $user)?->simplePaginate(self::PER_PAGE);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function search(array $filters, ?User $user): Paginator
    {
        $query = $this->baseQuery($user);
        $mode = isset($filters['location_mode']) ? LocationModeEnum::from($filters['location_mode']) : null;
        $point = $this->point($filters);
        $animals = $this->animalCounts($filters['animals'] ?? []);

        $query
            ->when(isset($filters['profession']), fn (Builder $query) => $query->whereHas('profession', fn (Builder $query) => $query->where('code', $filters['profession'])))
            ->when(isset($filters['category']), fn (Builder $query) => $query->whereHas('profession.category', fn (Builder $query) => $query->where('code', $filters['category'])))
            ->when(isset($filters['animal_type']), fn (Builder $query) => $query->whereHas('animalTypes', fn (Builder $query) => $query->where('code', $filters['animal_type'])))
            ->when($mode !== null, fn (Builder $query) => $query->where('activities.serves_'.$mode?->value, true))
            ->when(isset($filters['host_type']), fn (Builder $query) => $this->whereHostType($query, $filters['host_type']))
            ->when(isset($filters['location']), fn (Builder $query) => $this->whereLocation($query, $filters['location']))
            ->when($animals !== [], fn (Builder $query) => $this->whereAcceptsAll($query, array_keys($animals)));

        $withDistance = $point !== null && $mode !== LocationModeEnum::REMOTE;

        if ($withDistance) {
            $this->whereReachable($query, $point, $mode, (float) ($filters['radius'] ?? config('activities.default_search_radius_km')));
        }

        if (isset($filters['start_date'])) {
            $this->whereAvailable(
                $query,
                CarbonImmutable::parse($filters['start_date']),
                CarbonImmutable::parse($filters['end_date'] ?? $filters['start_date']),
                $animals,
            );
        }

        $sort = $filters['sort'] ?? ($withDistance ? 'distance' : 'newest');

        if ($sort === 'distance' && $withDistance) {
            $query->orderBy('distance');
        } else {
            $query->latest('activities.created_at');
        }

        return $query->orderBy('activities.id')->simplePaginate(self::PER_PAGE);
    }

    /**
     * @param  array{lat?: float|string|null, lng?: float|string|null}  $position
     */
    private function sectionQuery(string $section, array $position, ?User $user): ?Builder
    {
        $query = $this->baseQuery($user);
        $point = $this->point($position);

        return match ($section) {
            'nearby' => $point === null ? null : $this->whereReachable($query, $point, null, (float) config('activities.default_search_radius_km'))
                ->orderBy('distance')
                ->orderBy('activities.id'),
            'available_this_weekend' => $point === null ? null : $this->whereAvailable(
                $this->whereReachable($query, $point, null, (float) config('activities.default_search_radius_km')),
                ...$this->weekend(),
            )
                ->orderBy('distance')
                ->orderBy('activities.id'),
            // Les mieux notées, à partir d'un minimum d'avis publiés ; à note égale, les plus notées d'abord.
            'top_rated' => $query
                ->whereHas('reviews', fn (Builder $reviews) => $reviews->where('is_published', true), '>=', (int) config('reviews.top_rated_min_reviews'))
                ->orderByDesc('rating_average')
                ->orderByDesc('rating_count')
                ->orderBy('activities.id'),
            'verified_pros' => $this->orderByDistanceOrNewest(
                $this->whereHostType($query, 'pro'),
                $point,
            ),
            'new_hosts' => $query
                ->where('activities.created_at', '>=', now()->subDays(self::NEW_HOSTS_DAYS))
                ->latest('activities.created_at')
                ->orderBy('activities.id'),
            default => null,
        };
    }

    private function baseQuery(?User $user): Builder
    {
        return Activity::query()
            ->select('activities.*')
            ->bookable()
            ->withRating()
            ->with(['organization.subscription.plan', 'profession.category', 'address', 'animalTypes', 'media'])
            ->when($user !== null, fn (Builder $query) => $query->withExists([
                'favoritedBy as is_favorited' => fn (Builder $query) => $query->whereKey($user?->id),
            ]));
    }

    /**
     * Garde les activités qui peuvent servir le client à cette position, et calcule leur distance.
     *
     * @param  array{0: float, 1: float}  $point
     */
    private function whereReachable(Builder $query, array $point, ?LocationModeEnum $mode, float $radius): Builder
    {
        [$lat, $lng] = $point;
        $bindings = [$lat, $lng, $lat];
        $distance = self::DISTANCE_SQL;

        $query
            ->join('addresses as activity_address', 'activity_address.id', '=', 'activities.address_id')
            ->selectRaw("{$distance} as distance", $bindings)
            ->whereNotNull('activity_address.latitude')
            ->whereNotNull('activity_address.longitude');

        // Cadre autour du point, qui profite de l'index (latitude, longitude) avant le calcul exact.
        $this->whereWithinBox($query, $lat, $lng, $mode === LocationModeEnum::AT_PRO ? $radius : max($radius, (float) config('activities.max_radius_km')));

        // PDO lie un flottant comme du texte sous SQLite, qui le compare alors mal à un nombre : on le convertit.
        $atPro = fn (Builder $query) => $query->where('activities.serves_at_pro', true)->whereRaw("{$distance} <= cast(? as double precision)", [...$bindings, $radius]);
        $atClient = fn (Builder $query) => $query->where('activities.serves_at_client', true)->whereRaw("{$distance} <= activities.service_radius_km", $bindings);

        return match ($mode) {
            LocationModeEnum::AT_PRO => $query->where($atPro),
            LocationModeEnum::AT_CLIENT => $query->where($atClient),
            default => $query->where(fn (Builder $query) => $query->where($atPro)->orWhere($atClient)),
        };
    }

    private function whereWithinBox(Builder $query, float $lat, float $lng, float $radiusKm): void
    {
        $latDelta = $radiusKm / self::KM_PER_DEGREE_LATITUDE;
        $query->whereBetween('activity_address.latitude', [$lat - $latDelta, $lat + $latDelta]);

        $cosLat = cos(deg2rad($lat));

        // Près des pôles ou de l'antiméridien, le cadre en longitude n'a plus de sens : le calcul exact suffit.
        if ($cosLat < 0.00001) {
            return;
        }

        $lngDelta = $radiusKm / (self::KM_PER_DEGREE_LATITUDE * $cosLat);

        if ($lng - $lngDelta >= -180.0 && $lng + $lngDelta <= 180.0) {
            $query->whereBetween('activity_address.longitude', [$lng - $lngDelta, $lng + $lngDelta]);
        }
    }

    /**
     * @param  array{0: float, 1: float}|null  $point
     */
    private function orderByDistanceOrNewest(Builder $query, ?array $point): Builder
    {
        if ($point === null) {
            return $query->latest('activities.created_at')->orderBy('activities.id');
        }

        // Sans filtre de distance : les activités sans coordonnées passent en dernier.
        [$lat, $lng] = $point;

        return $query
            ->leftJoin('addresses as activity_address', 'activity_address.id', '=', 'activities.address_id')
            ->selectRaw(self::DISTANCE_SQL.' as distance', [$lat, $lng, $lat])
            ->orderByRaw('case when activity_address.latitude is null then 1 else 0 end')
            ->orderBy('distance')
            ->orderBy('activities.id');
    }

    /**
     * @param  array<string, int>  $animals
     */
    private function whereAvailable(Builder $query, CarbonImmutable $start, CarbonImmutable $end, array $animals = []): Builder
    {
        return $query->whereIn('activities.id', $this->availability->availableIds(clone $query, $start, $end, $animals));
    }

    /**
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    private function weekend(): array
    {
        $today = CarbonImmutable::today();

        if ($today->isSunday()) {
            return [$today, $today];
        }

        $saturday = $today->isSaturday() ? $today : $today->next(CarbonImmutable::SATURDAY);

        return [$saturday, $saturday->addDay()];
    }

    /**
     * @param  list<string>  $animalTypeIds
     */
    private function whereAcceptsAll(Builder $query, array $animalTypeIds): Builder
    {
        foreach ($animalTypeIds as $animalTypeId) {
            $query->whereHas('animalTypes', fn (Builder $query) => $query->whereKey($animalTypeId));
        }

        return $query;
    }

    /**
     * @param  array<string, int|string>  $counts
     * @return array<string, int>
     */
    private function animalCounts(array $counts): array
    {
        if ($counts === []) {
            return [];
        }

        return AnimalType::query()
            ->whereIn('code', array_keys($counts))
            ->pluck('id', 'code')
            ->mapWithKeys(fn (string $id, string $code): array => [$id => (int) $counts[$code]])
            ->all();
    }

    private function whereHostType(Builder $query, string $hostType): Builder
    {
        return $query->whereHas('organization', fn (Builder $query) => $query->where(
            'legal_form',
            $hostType === 'individual' ? '=' : '!=',
            OrganizationLegalFormEnum::INDIVIDUAL,
        ));
    }

    private function whereLocation(Builder $query, string $location): Builder
    {
        return $query->whereHas('address', fn (Builder $query) => $query
            ->whereLike('city', "%{$location}%")
            ->orWhereLike('postal_code', "{$location}%"));
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array{0: float, 1: float}|null
     */
    private function point(array $input): ?array
    {
        if (! isset($input['lat'], $input['lng'])) {
            return null;
        }

        return [(float) $input['lat'], (float) $input['lng']];
    }
}
