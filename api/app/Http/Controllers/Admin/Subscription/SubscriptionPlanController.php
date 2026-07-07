<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Subscription;

use App\Enums\ApiStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Subscription\UpdateSubscriptionPlanRequest;
use App\Http\Resources\SubscriptionPlanResource;
use App\Models\SubscriptionPlan;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

/**
 * @tags Admin Subscription Plans
 */
class SubscriptionPlanController extends Controller
{
    public function index(): JsonResponse
    {
        $this->authorize('viewAny', SubscriptionPlan::class);

        $plans = SubscriptionPlan::orderBy('price_monthly')->get();

        return SubscriptionPlanResource::collection($plans)
            ->additional([
                'status' => ApiStatusEnum::SUCCESS,
                'timestamp' => human_date(Carbon::now()),
            ])
            ->response();
    }

    public function update(UpdateSubscriptionPlanRequest $request, SubscriptionPlan $plan): JsonResponse
    {
        $this->authorize('update', $plan);

        $plan->update($request->validated());

        return (new SubscriptionPlanResource($plan->fresh()))
            ->additional([
                'message' => 'Subscription plan updated successfully',
                'status' => ApiStatusEnum::SUCCESS,
                'timestamp' => human_date(Carbon::now()),
            ])
            ->response();
    }
}
