<?php

declare(strict_types=1);

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use App\Http\Requests\Booking\ListBookingsRequest;
use App\Http\Resources\BookingResource;
use App\Models\Activity;
use App\Models\Booking;
use App\Services\Booking\BookingService;
use App\Services\Booking\CancellationService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Réservations d'une activité, côté équipe.
 *
 * @tags Bookings
 */
class ActivityBookingController extends Controller
{
    public function __construct(
        private readonly BookingService $bookings,
        private readonly CancellationService $cancellations,
    ) {}

    /**
     * List the bookings of an activity
     *
     * Dans l'ordre des arrivées. from et to gardent les séjours qui touchent la période.
     */
    public function index(ListBookingsRequest $request, Activity $activity): AnonymousResourceCollection
    {
        $this->authorize('viewBookings', $activity);

        return BookingResource::collection($this->bookings->forActivity($activity, $request->validated()));
    }

    /**
     * Show a booking of the activity
     */
    public function show(Activity $activity, Booking $booking): BookingResource
    {
        $this->authorize('view', $booking);

        return new BookingResource($booking->load(BookingService::RELATIONS));
    }

    /**
     * Accept a booking request
     *
     * Le paiement du client est capturé.
     */
    public function confirm(Activity $activity, Booking $booking): BookingResource
    {
        $this->authorize('manage', $booking);

        return new BookingResource($this->bookings->confirm($booking));
    }

    /**
     * Decline a booking request
     *
     * L'autorisation de paiement est libérée : le client ne paie rien.
     */
    public function reject(Activity $activity, Booking $booking): BookingResource
    {
        $this->authorize('manage', $booking);

        return new BookingResource($this->bookings->reject($booking));
    }

    /**
     * Cancel an accepted booking
     *
     * Le client est remboursé en entier, frais Kennelo compris.
     */
    public function cancel(Request $request, Activity $activity, Booking $booking): BookingResource
    {
        $this->authorize('manage', $booking);

        return new BookingResource($this->cancellations->cancelByPro($booking, $request->user()));
    }
}
