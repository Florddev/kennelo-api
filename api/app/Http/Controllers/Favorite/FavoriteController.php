<?php

declare(strict_types=1);

namespace App\Http\Controllers\Favorite;

use App\Enums\ApiStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Favorite\StoreFavoriteRequest;
use App\Http\Resources\ActivityResource;
use App\Models\Activity;
use App\Services\Favorite\FavoriteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * @tags Favorites
 */
class FavoriteController extends Controller
{
    public function __construct(
        private FavoriteService $favoriteService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $activities = $this->favoriteService->list($request->user(), $request->query());

        return ActivityResource::collection($activities)
            ->additional([
                'status' => ApiStatusEnum::SUCCESS,
                'timestamp' => human_date(Carbon::now()),
            ])
            ->response();
    }

    public function store(StoreFavoriteRequest $request): JsonResponse
    {
        $activity = Activity::findOrFail($request->validated('activity_id'));

        $this->favoriteService->add($request->user(), $activity);

        $activity->load(['address', 'manager', 'cycles.settings.animalType', 'cycles.settings.prices'])
            ->setAttribute('is_favorited', true);

        return (new ActivityResource($activity))
            ->additional([
                'status' => ApiStatusEnum::SUCCESS,
                'timestamp' => human_date(Carbon::now()),
            ])
            ->response()
            ->setStatusCode(201);
    }

    public function destroy(Request $request, Activity $activity): JsonResponse
    {
        $this->favoriteService->remove($request->user(), $activity);

        return response()->json(null, 204);
    }
}
