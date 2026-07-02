<?php

declare(strict_types=1);

namespace App\Http\Controllers\Finance;

use App\Enums\ApiStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Resources\FinancialOperationResource;
use App\Models\Activity;
use App\Models\Booking;
use App\Models\FinancialOperation;
use Illuminate\Http\JsonResponse;

class FinancialOperationController extends Controller
{
    public function index(Activity $activity, Booking $booking): JsonResponse
    {
        $this->authorize('manageForActivity', [Booking::class, $activity]);
        abort_if((string) $booking->activity_id !== (string) $activity->id, 404);

        $operations = FinancialOperation::where('booking_id', $booking->id)
            ->latest()
            ->get();

        return FinancialOperationResource::collection($operations)
            ->additional([
                'status' => ApiStatusEnum::SUCCESS,
                'timestamp' => human_date(now()),
            ])
            ->response();
    }
}
