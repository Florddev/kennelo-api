<?php

declare(strict_types=1);

namespace App\Http\Controllers\Subscription;

use App\Enums\ApiStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Subscription\StoreSubscriptionCheckoutRequest;
use App\Http\Resources\SubscriptionInvoiceResource;
use App\Http\Resources\SubscriptionPlanResource;
use App\Http\Resources\SubscriptionResource;
use App\Models\SubscriptionPlan;
use App\Services\Subscription\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

    public function show(Request $request): JsonResponse
    {
        $subscription = $request->user()->subscription()->with('plan')->first();

        $data = $subscription === null
            ? ['plan' => 'free', 'status' => null, 'is_effective' => false]
            : (new SubscriptionResource($subscription))->resolve();

        return response()->json([
            'data' => $data,
            'status' => ApiStatusEnum::SUCCESS,
            'timestamp' => human_date(now()),
        ]);
    }

    public function checkout(StoreSubscriptionCheckoutRequest $request): JsonResponse
    {
        $plan = SubscriptionPlan::where('slug', $request->validated('plan_slug'))->firstOrFail();

        $checkoutUrl = $this->subscriptionService->startCheckout($request->user(), $plan);

        return response()->json([
            'data' => ['checkout_url' => $checkoutUrl],
            'status' => ApiStatusEnum::SUCCESS,
            'timestamp' => human_date(now()),
        ]);
    }

    public function invoices(Request $request): JsonResponse
    {
        $invoices = $this->subscriptionService->listInvoices($request->user());

        return SubscriptionInvoiceResource::collection($invoices)
            ->additional([
                'status' => ApiStatusEnum::SUCCESS,
                'timestamp' => human_date(now()),
            ])
            ->response();
    }

    public function cancel(Request $request): JsonResponse
    {
        $subscription = $request->user()->subscription()->firstOrFail();

        $subscription = $this->subscriptionService->cancel($subscription);

        return (new SubscriptionResource($subscription->loadMissing('plan')))
            ->additional([
                'status' => ApiStatusEnum::SUCCESS,
                'timestamp' => human_date(now()),
            ])
            ->response();
    }
}
