<?php

declare(strict_types=1);

namespace App\Http\Controllers\Explore;

use App\Enums\ApiStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\EstablishmentResource;
use App\Models\Establishment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ExploreController extends Controller
{
    public function establishments(Request $request): JsonResponse
    {
        $establishments = Establishment::with(['address', 'capacities.animalType'])
            ->active()
            ->latest()
            ->limit(50)
            ->get();

        return EstablishmentResource::collection($establishments)
            ->additional([
                'status' => ApiStatus::SUCCESS,
                'timestamp' => human_date(Carbon::now()),
            ])
            ->response();
    }
}
