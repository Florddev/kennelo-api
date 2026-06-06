<?php

declare(strict_types=1);

namespace App\Http\Controllers\Booking;

use App\Enums\ApiStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Booking\ListEstablishmentBookingsRequest;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use App\Models\Establishment;
use App\Services\Booking\BookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EstablishmentBookingController extends Controller
{
    public function __construct(
        private BookingService $bookingService
    ) {}

    public function index(ListEstablishmentBookingsRequest $request, Establishment $establishment): JsonResponse
    {
        $this->authorize('manageForEstablishment', [Booking::class, $establishment]);

        $bookings = $this->bookingService->getEstablishmentBookings($establishment, $request->validated());

        return BookingResource::collection($bookings)
            ->additional([
                'status' => ApiStatusEnum::SUCCESS,
                'timestamp' => human_date(now()),
            ])
            ->response();
    }

    public function confirm(Request $request, Establishment $establishment, Booking $booking): JsonResponse
    {
        $this->authorize('manageForEstablishment', [Booking::class, $establishment]);
        abort_if((string) $booking->establishment_id !== (string) $establishment->id, 404);

        $booking = $this->bookingService->confirm($booking, $request->user());

        return (new BookingResource($booking))
            ->additional([
                'status' => ApiStatusEnum::SUCCESS,
                'timestamp' => human_date(now()),
            ])
            ->response();
    }

    public function cancel(Request $request, Establishment $establishment, Booking $booking): JsonResponse
    {
        $this->authorize('manageForEstablishment', [Booking::class, $establishment]);
        abort_if((string) $booking->establishment_id !== (string) $establishment->id, 404);

        $booking = $this->bookingService->rejectByEstablishment($booking, $request->user());

        return (new BookingResource($booking))
            ->additional([
                'status' => ApiStatusEnum::SUCCESS,
                'timestamp' => human_date(now()),
            ])
            ->response();
    }

    public function complete(Establishment $establishment, Booking $booking): JsonResponse
    {
        $this->authorize('manageForEstablishment', [Booking::class, $establishment]);
        abort_if((string) $booking->establishment_id !== (string) $establishment->id, 404);

        $booking = $this->bookingService->complete($booking);

        return (new BookingResource($booking))
            ->additional([
                'status' => ApiStatusEnum::SUCCESS,
                'timestamp' => human_date(now()),
            ])
            ->response();
    }
}
