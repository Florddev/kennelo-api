<?php

declare(strict_types=1);

namespace App\Http\Controllers\Subscription;

use App\Enums\ApiStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Subscription\StoreSubscriptionCheckoutRequest;
use App\Http\Resources\SubscriptionInvoiceResource;
use App\Http\Resources\SubscriptionPlanResource;
use App\Http\Resources\SubscriptionResource;
use App\Models\Activity;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Services\Subscription\SubscriptionService;
use Illuminate\Http\JsonResponse;

class SubscriptionController extends Controller
{
    public function __construct(
        private SubscriptionService $subscriptionService
    ) {}

    public function plans(): JsonResponse
    {
        $plans = SubscriptionPlan::where('is_active', true)
            ->orderBy('commission_rate', 'desc')
            ->get();

        return SubscriptionPlanResource::collection($plans)
            ->additional([
                'status' => ApiStatusEnum::SUCCESS,
                'timestamp' => human_date(now()),
            ])
            ->response();
    }

    public function show(Activity $activity): JsonResponse
    {
        $this->authorize('manageForActivity', [Subscription::class, $activity]);

        $subscription = $activity->subscription()->with('plan')->first();

        $data = $subscription === null
            ? ['plan' => 'free', 'status' => null, 'is_effective' => false]
            : (new SubscriptionResource($subscription->loadMissing('plan')))->resolve();

        return response()->json([
            'data' => $data,
            'status' => ApiStatusEnum::SUCCESS,
            'timestamp' => human_date(now()),
        ]);
    }

    public function checkout(StoreSubscriptionCheckoutRequest $request, Activity $activity): JsonResponse
    {
        $this->authorize('manageForActivity', [Subscription::class, $activity]);

        $plan = SubscriptionPlan::where('slug', $request->validated('plan_slug'))->firstOrFail();

        $checkoutUrl = $this->subscriptionService->startCheckout($request->user(), $activity, $plan);

        return response()->json([
            'data' => ['checkout_url' => $checkoutUrl],
            'status' => ApiStatusEnum::SUCCESS,
            'timestamp' => human_date(now()),
        ]);
    }

    public function invoices(Activity $activity): JsonResponse
    {
        $this->authorize('manageForActivity', [Subscription::class, $activity]);

        $invoices = $this->subscriptionService->listInvoices($activity);

        return SubscriptionInvoiceResource::collection($invoices)
            ->additional([
                'status' => ApiStatusEnum::SUCCESS,
                'timestamp' => human_date(now()),
            ])
            ->response();
    }

    public function cancel(Activity $activity): JsonResponse
    {
        $this->authorize('manageForActivity', [Subscription::class, $activity]);

        $subscription = $activity->subscription()->firstOrFail();

        $subscription = $this->subscriptionService->cancel($subscription);

        return (new SubscriptionResource($subscription->loadMissing('plan')))
            ->additional([
                'status' => ApiStatusEnum::SUCCESS,
                'timestamp' => human_date(now()),
            ])
            ->response();
    }
}
