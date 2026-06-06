<?php

declare(strict_types=1);

namespace App\Http\Controllers\Establishment;

use App\Enums\ApiStatusEnum;
use App\Http\Controllers\Controller;
use App\Models\Establishment;
use App\Services\Establishment\EstablishmentDashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

/**
 * @tags Establishments
 */
class EstablishmentDashboardController extends Controller
{
    public function __construct(private EstablishmentDashboardService $service) {}

    public function show(Establishment $establishment): JsonResponse
    {
        $this->authorize('viewCapacities', $establishment);

        $dashboard = $this->service->getDashboard($establishment);

        return response()->json([
            'data' => $dashboard,
            'status' => ApiStatusEnum::SUCCESS,
            'timestamp' => human_date(Carbon::now()),
        ]);
    }
}
