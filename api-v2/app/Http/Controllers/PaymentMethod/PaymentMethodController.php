<?php

declare(strict_types=1);

namespace App\Http\Controllers\PaymentMethod;

use App\Http\Controllers\Controller;
use App\Http\Resources\PaymentMethodResource;
use App\Services\Stripe\StripePaymentMethodService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/**
 * @tags Payment methods
 */
class PaymentMethodController extends Controller
{
    public function __construct(private StripePaymentMethodService $service) {}

    public function setupIntent(Request $request): JsonResponse
    {
        return response()->json($this->service->createSetupIntent($request->user()));
    }

    public function setupCheckoutSession(Request $request): JsonResponse
    {
        return response()->json($this->service->createSetupCheckoutSession($request->user()));
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        return PaymentMethodResource::collection($this->service->list($request->user()));
    }

    public function setDefault(Request $request, string $id): AnonymousResourceCollection
    {
        $this->service->setDefault($request->user(), $id);

        return PaymentMethodResource::collection($this->service->list($request->user()));
    }

    public function destroy(Request $request, string $id): Response
    {
        $this->service->delete($request->user(), $id);

        return response()->noContent();
    }
}
