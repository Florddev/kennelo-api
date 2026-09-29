<?php

declare(strict_types=1);

namespace App\Http\Controllers\Activity;

use App\Http\Controllers\Controller;
use App\Http\Requests\Activity\ReplaceTravelFeesRequest;
use App\Http\Resources\ActivityTravelFeeTierResource;
use App\Models\Activity;
use App\Services\Activity\TravelFeeService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @tags Activities
 */
class ActivityTravelFeeController extends Controller
{
    public function __construct(
        private readonly TravelFeeService $travelFees,
    ) {}

    /**
     * List the travel fees
     */
    public function index(Activity $activity): AnonymousResourceCollection
    {
        $this->authorize('view', $activity);

        return ActivityTravelFeeTierResource::collection($this->travelFees->forActivity($activity));
    }

    /**
     * Replace the travel fees
     */
    public function update(ReplaceTravelFeesRequest $request, Activity $activity): AnonymousResourceCollection
    {
        $this->authorize('update', $activity);

        return ActivityTravelFeeTierResource::collection($this->travelFees->replace($activity, $request->validated('tiers')));
    }
}
