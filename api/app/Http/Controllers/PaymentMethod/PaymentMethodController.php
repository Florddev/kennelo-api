<?php

declare(strict_types=1);

namespace App\Http\Controllers\PaymentMethod;

use App\Enums\ApiStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\PaymentMethodResource;
use App\Services\Stripe\StripePaymentMethodService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentMethodController extends Controller
{
    public function __construct(private StripePaymentMethodService $service) {}

    public function setupIntent(Request $request): JsonResponse
    {
        $data = $this->service->createSetupIntent($request->user());

        return response()->json([
            'data' => $data,
            'status' => ApiStatus::SUCCESS,
            'timestamp' => human_date(now()),
        ]);
    }

    public function setupCheckoutSession(Request $request): JsonResponse
    {
        $data = $this->service->createSetupCheckoutSession($request->user());

        return response()->json([
            'data' => $data,
            'status' => ApiStatus::SUCCESS,
            'timestamp' => human_date(now()),
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $methods = $this->service->list($request->user());

        return PaymentMethodResource::collection($methods)
            ->additional([
                'status' => ApiStatus::SUCCESS,
                'timestamp' => human_date(now()),
            ])
            ->response();
    }

    public function setDefault(Request $request, string $id): JsonResponse
    {
        $this->service->setDefault($request->user(), $id);

        $methods = $this->service->list($request->user());

        return PaymentMethodResource::collection($methods)
            ->additional([
                'status' => ApiStatus::SUCCESS,
                'timestamp' => human_date(now()),
            ])
            ->response();
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $this->service->delete($request->user(), $id);

        return response()->json(null, 204);
    }
}
