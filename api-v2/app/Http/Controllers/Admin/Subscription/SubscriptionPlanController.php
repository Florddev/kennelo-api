<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Subscription;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Subscription\UpdateSubscriptionPlanRequest;
use App\Http\Resources\SubscriptionPlanResource;
use App\Models\SubscriptionPlan;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @tags Admin Subscription Plans
 */
class SubscriptionPlanController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', SubscriptionPlan::class);

        return SubscriptionPlanResource::collection(SubscriptionPlan::orderBy('price_monthly')->get());
    }

    public function update(UpdateSubscriptionPlanRequest $request, SubscriptionPlan $plan): SubscriptionPlanResource
    {
        $this->authorize('update', $plan);

        $plan->update($request->validated());

        return new SubscriptionPlanResource($plan->fresh());
    }
}
