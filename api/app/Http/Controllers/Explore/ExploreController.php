<?php

declare(strict_types=1);

namespace App\Http\Controllers\Explore;

use App\Enums\ApiStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Explore\SearchExploreRequest;
use App\Http\Resources\ExploreActivityResource;
use App\Services\Explore\ExploreService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExploreController extends Controller
{
    public function __construct(private ExploreService $service) {}

    public function activities(Request $request): JsonResponse
    {
        [$lat, $lng] = $this->resolveCoords($request);

        $sections = $this->service->getSections($lat, $lng, $request->user());

        $sectionsData = collect($sections)->map(fn ($section) => [
            'id' => $section['id'],
            'has_more' => $section['has_more'],
            'activities' => ExploreActivityResource::collection($section['activities'])->resolve($request),
        ])->all();

        return response()->json([
            'data' => ['sections' => $sectionsData],
            'status' => ApiStatusEnum::SUCCESS->value,
            'timestamp' => human_date(now()),
        ]);
    }

    public function sectionPage(Request $request, string $sectionId): JsonResponse
    {
        [$lat, $lng] = $this->resolveCoords($request);
        $page = max(1, (int) $request->input('page', 1));

        $result = $this->service->getSectionPage($sectionId, $lat, $lng, $page, $request->user());

        abort_if($result === null, 404);

        return response()->json([
            'data' => [
                'activities' => ExploreActivityResource::collection($result['activities'])->resolve($request),
                'meta' => [
                    'current_page' => $result['page'],
                    'per_page' => ExploreService::PER_PAGE,
                    'has_more' => $result['has_more'],
                ],
            ],
            'status' => ApiStatusEnum::SUCCESS->value,
            'timestamp' => human_date(now()),
        ]);
    }

    public function search(SearchExploreRequest $request): JsonResponse
    {
        [$lat, $lng] = $this->resolveCoords($request);
        $page = max(1, (int) $request->input('page', 1));

        $result = $this->service->search($request->validated(), $lat, $lng, $page, $request->user());

        return response()->json([
            'data' => [
                'activities' => ExploreActivityResource::collection($result['activities'])->resolve($request),
                'meta' => [
                    'current_page' => $result['page'],
                    'per_page' => ExploreService::PER_PAGE,
                    'has_more' => $result['has_more'],
                ],
            ],
            'status' => ApiStatusEnum::SUCCESS->value,
            'timestamp' => human_date(now()),
        ]);
    }

    private function resolveCoords(Request $request): array
    {
        $lat = $request->filled('lat') ? (float) $request->input('lat') : null;
        $lng = $request->filled('lng') ? (float) $request->input('lng') : null;

        return [$lat, $lng];
    }
}
