<?php

declare(strict_types=1);

namespace App\Http\Controllers\Booking;

use App\Enums\ApiStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Booking\ListBookingsRequest;
use App\Http\Requests\Booking\StoreBookingRequest;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use App\Services\Booking\BookingService;
use Illuminate\Http\JsonResponse;

class BookingController extends Controller
{
    public function __construct(
        private BookingService $bookingService
    ) {}

    public function index(ListBookingsRequest $request): JsonResponse
    {
        $bookings = $this->bookingService->getUserBookings($request->user(), $request->validated());

        return BookingResource::collection($bookings)
            ->additional([
                'status' => ApiStatusEnum::SUCCESS,
                'timestamp' => human_date(now()),
            ])
            ->response();
    }

    public function show(Booking $booking): JsonResponse
    {
        $this->authorize('view', $booking);

        $booking->load(['establishment', 'pets', 'services', 'user']);

        return (new BookingResource($booking))
            ->additional(['status' => ApiStatusEnum::SUCCESS, 'timestamp' => human_date(now())])
            ->response();
    }

    public function store(StoreBookingRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $booking = $this->bookingService->create(
            $request->user(),
            $validated,
            $validated['payment_method_id'],
            (bool) ($validated['save_payment_method'] ?? false),
        );

        return (new BookingResource($booking))
            ->additional([
                'status' => ApiStatusEnum::SUCCESS,
                'timestamp' => human_date(now()),
            ])
            ->response()
            ->setStatusCode(201);
    }

    public function cancel(Booking $booking): JsonResponse
    {
        $this->authorize('cancel', $booking);

        $booking = $this->bookingService->cancel($booking);

        return (new BookingResource($booking))
            ->additional([
                'status' => ApiStatusEnum::SUCCESS,
                'timestamp' => human_date(now()),
            ])
            ->response();
    }
}
