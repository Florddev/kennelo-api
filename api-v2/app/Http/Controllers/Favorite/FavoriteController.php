<?php

declare(strict_types=1);

namespace App\Http\Controllers\Favorite;

use App\Http\Controllers\Controller;
use App\Http\Requests\Favorite\ListFavoritesRequest;
use App\Http\Resources\ActivityResource;
use App\Models\Activity;
use App\Services\Favorite\FavoriteService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/**
 * @tags Favorites
 */
class FavoriteController extends Controller
{
    public function __construct(
        private readonly FavoriteService $favorites,
    ) {}

    /**
     * List my favorites
     *
     * Seules les activités encore réservables apparaissent.
     */
    public function index(ListFavoritesRequest $request): AnonymousResourceCollection
    {
        return ActivityResource::collection($this->favorites->list($request->user(), $request->validated('per_page')));
    }

    /**
     * Add a favorite
     *
     * Sans effet si l'activité est déjà en favori.
     */
    public function store(Request $request, Activity $activity): Response
    {
        abort_unless(Activity::query()->whereKey($activity->id)->bookable()->exists(), 404);

        $this->favorites->add($request->user(), $activity);

        return response()->noContent();
    }

    public function destroy(Request $request, Activity $activity): Response
    {
        $this->favorites->remove($request->user(), $activity);

        return response()->noContent();
    }
}
