<?php

declare(strict_types=1);

namespace App\Http\Controllers\Booking;

use App\Enums\ApiStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Booking\ListActivityBookingsRequest;
use App\Http\Resources\BookingResource;
use App\Models\Activity;
use App\Models\Booking;
use App\Services\Booking\BookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ActivityBookingController extends Controller
{
    public function __construct(
        private BookingService $bookingService
    ) {}

    public function index(ListActivityBookingsRequest $request, Activity $activity): JsonResponse
    {
        $this->authorize('manageForActivity', [Booking::class, $activity]);

        $bookings = $this->bookingService->getActivityBookings($activity, $request->validated());

        return BookingResource::collection($bookings)
            ->additional([
                'status' => ApiStatusEnum::SUCCESS,
                'timestamp' => human_date(now()),
            ])
            ->response();
    }

    public function confirm(Request $request, Activity $activity, Booking $booking): JsonResponse
    {
        $this->authorize('manageForActivity', [Booking::class, $activity]);
        abort_if((string) $booking->activity_id !== (string) $activity->id, 404);

        $message = $request->input('message');
        $booking = $this->bookingService->confirm($booking, $request->user(), $message);

        return (new BookingResource($booking))
            ->additional([
                'status' => ApiStatusEnum::SUCCESS,
                'timestamp' => human_date(now()),
            ])
            ->response();
    }

    public function cancel(Request $request, Activity $activity, Booking $booking): JsonResponse
    {
        $this->authorize('manageForActivity', [Booking::class, $activity]);
        abort_if((string) $booking->activity_id !== (string) $activity->id, 404);

        $message = $request->input('message');
        $booking = $this->bookingService->rejectByActivity($booking, $request->user(), $message);

        return (new BookingResource($booking))
            ->additional([
                'status' => ApiStatusEnum::SUCCESS,
                'timestamp' => human_date(now()),
            ])
            ->response();
    }

    public function complete(Activity $activity, Booking $booking): JsonResponse
    {
        $this->authorize('manageForActivity', [Booking::class, $activity]);
        abort_if((string) $booking->activity_id !== (string) $activity->id, 404);

        $booking = $this->bookingService->complete($booking);

        return (new BookingResource($booking))
            ->additional([
                'status' => ApiStatusEnum::SUCCESS,
                'timestamp' => human_date(now()),
            ])
            ->response();
    }
}
