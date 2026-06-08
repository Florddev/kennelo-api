<?php

declare(strict_types=1);

namespace App\Http\Controllers\Activity;

use App\Enums\ApiStatusEnum;
use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Services\Activity\ActivityDashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

/**
 * @tags Activities
 */
class ActivityDashboardController extends Controller
{
    public function __construct(private ActivityDashboardService $service) {}

    public function show(Activity $activity): JsonResponse
    {
        $this->authorize('viewCycles', $activity);

        $dashboard = $this->service->getDashboard($activity);

        return response()->json([
            'data' => $dashboard,
            'status' => ApiStatusEnum::SUCCESS,
            'timestamp' => human_date(Carbon::now()),
        ]);
    }
}
