<?php

declare(strict_types=1);

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use App\Http\Requests\Booking\StoreBookingItemRequest;
use App\Http\Resources\BookingResource;
use App\Models\Activity;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Services\Booking\BookingAdjustmentService;
use App\Services\Booking\BookingService;
use Illuminate\Http\Request;

/**
 * Options ajoutées ou retirées par le pro sur une réservation acceptée.
 *
 * @tags Bookings
 */
class BookingItemController extends Controller
{
    public function __construct(
        private readonly BookingAdjustmentService $adjustments,
    ) {}

    /**
     * Add an option to a booking
     *
     * Débitée aussitôt avec la carte du client. Si sa banque exige qu'il s'authentifie, le paiement reste en
     * attente (payments.*.status requires_action) et le client est prévenu.
     */
    public function store(StoreBookingItemRequest $request, Activity $activity, Booking $booking): BookingResource
    {
        $this->authorize('manage', $booking);

        $this->adjustments->addItem($booking, $request->validated());

        return new BookingResource($booking->load(BookingService::RELATIONS));
    }

    /**
     * Remove an option from a booking
     *
     * Remboursée au client avec sa part des frais Kennelo.
     */
    public function destroy(Request $request, Activity $activity, Booking $booking, BookingItem $item): BookingResource
    {
        $this->authorize('manage', $booking);

        return new BookingResource($this->adjustments->removeItem($booking, $item, $request->user()));
    }
}
