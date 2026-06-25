<?php

declare(strict_types=1);

namespace App\Http\Controllers\Hosting;

use App\Enums\ApiStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Hosting\AssignMicrochipRequest;
use App\Http\Resources\BookingResource;
use App\Http\Resources\ConversationResource;
use App\Http\Resources\PetResource;
use App\Models\Booking;
use App\Models\Pet;
use App\Services\Conversation\ConversationService;
use App\Services\Hosting\HostScanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HostScanController extends Controller
{
    public function __construct(
        private HostScanService $hostScanService,
        private ConversationService $conversationService
    ) {}

    public function conversation(Request $request, Booking $booking): JsonResponse
    {
        $booking->loadMissing('activity');
        abort_if($booking->activity === null, 404);

        $this->authorize('manageForActivity', [Booking::class, $booking->activity]);

        $conversation = $this->conversationService->getOrCreateForBooking($request->user(), $booking);

        return (new ConversationResource($conversation))
            ->additional([
                'status' => ApiStatusEnum::SUCCESS,
                'timestamp' => human_date(now()),
            ])
            ->response();
    }

    public function show(Request $request, string $microchipNumber): JsonResponse
    {
        $pet = $this->hostScanService->findPetByMicrochip($microchipNumber);

        if ($pet === null) {
            return response()->json([
                'status' => ApiStatusEnum::SUCCESS,
                'timestamp' => human_date(now()),
                'found' => false,
                'pet' => null,
                'owner' => null,
                'current_booking' => null,
                'past_bookings' => [],
            ]);
        }

        $user = $request->user();
        $currentBooking = $this->hostScanService->currentBookingForPet($user, $pet);
        $pastBookings = $this->hostScanService->pastBookingsForPet($user, $pet);

        return response()->json([
            'status' => ApiStatusEnum::SUCCESS,
            'timestamp' => human_date(now()),
            'found' => true,
            'pet' => new PetResource($pet),
            'owner' => $this->hostScanService->ownerPayload($pet),
            'current_booking' => $currentBooking === null ? null : new BookingResource($currentBooking),
            'past_bookings' => BookingResource::collection($pastBookings),
        ]);
    }

    public function inCare(Request $request): JsonResponse
    {
        return response()->json([
            'status' => ApiStatusEnum::SUCCESS,
            'timestamp' => human_date(now()),
            'data' => $this->hostScanService->inCarePets($request->user()),
        ]);
    }

    public function assignMicrochip(AssignMicrochipRequest $request, Pet $pet): JsonResponse
    {
        $pet = $this->hostScanService->assignMicrochip($pet, $request->validated()['microchip_number']);

        return (new PetResource($pet))
            ->additional([
                'status' => ApiStatusEnum::SUCCESS,
                'timestamp' => human_date(now()),
            ])
            ->response();
    }
}
