<?php

declare(strict_types=1);

namespace App\Http\Controllers\Explore;

use App\Http\Controllers\Controller;
use App\Http\Requests\Explore\ExploreRequest;
use App\Http\Requests\Explore\SearchActivitiesRequest;
use App\Http\Resources\ActivityResource;
use App\Services\Explore\ExploreService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Recherche publique : seules les activités réservables apparaissent.
 *
 * @tags Explore
 */
class ExploreController extends Controller
{
    public function __construct(
        private readonly ExploreService $explore,
    ) {}

    /**
     * Home page sections
     *
     * « Près de chez vous » et « Disponibles ce week-end » (avec lat et lng), « Mieux notés », « Professionnels », « Nouveaux ». Une section de moins de trois activités est omise.
     */
    public function activities(ExploreRequest $request): JsonResponse
    {
        $sections = $this->explore->sections($request->validated(), $request->user());

        return response()->json(array_map(fn (array $section): array => [
            'id' => $section['id'],
            'has_more' => $section['has_more'],
            'activities' => ActivityResource::collection($section['activities'])->resolve($request),
        ], $sections));
    }

    /**
     * One section, page by page
     */
    public function section(ExploreRequest $request, string $section): AnonymousResourceCollection
    {
        $activities = $this->explore->section($section, $request->validated(), $request->user());

        abort_if($activities === null, 404);

        return ActivityResource::collection($activities);
    }

    /**
     * Search activities
     *
     * Filtres par métier, catégorie, espèce, lieu d'exercice et position. Avec une position, le tri par défaut
     * est la distance ; à domicile, l'activité doit couvrir la position avec son rayon de déplacement.
     * Avec start_date (et end_date, jour du départ), seules restent les activités qui ont de la place chaque nuit
     * ou un créneau libre ; animals[code]=nombre limite aux activités qui accueillent ces animaux.
     */
    public function search(SearchActivitiesRequest $request): AnonymousResourceCollection
    {
        return ActivityResource::collection($this->explore->search($request->validated(), $request->user()));
    }
}
