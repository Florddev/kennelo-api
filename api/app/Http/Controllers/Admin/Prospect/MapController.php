<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Prospect;

use App\Enums\ApiStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Prospect\MapProspectsRequest;
use App\Models\Prospect;
use App\Services\Admin\Prospect\ProspectService;
use Illuminate\Http\JsonResponse;

/**
 * @tags Admin Prospect Map
 */
class MapController extends Controller
{
    public function __construct(
        private ProspectService $prospects
    ) {}

    public function index(MapProspectsRequest $request): JsonResponse
    {
        $this->authorize('viewAny', Prospect::class);

        return response()->json([
            'data' => $this->prospects->mapGeoJson($request->validated()),
            'status' => ApiStatusEnum::SUCCESS,
            'timestamp' => human_date(now()),
        ]);
    }
}
