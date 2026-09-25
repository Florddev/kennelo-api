<?php

declare(strict_types=1);

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use App\Http\Requests\Booking\ScheduleBookingItemRequest;
use App\Http\Requests\Booking\StoreBookingItemRequest;
use App\Http\Resources\BookingResource;
use App\Models\Activity;
use App\Models\AgendaResource;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Services\Booking\BookingAdjustmentService;
use App\Services\Booking\BookingService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

/**
 * Options ajoutées, retirées ou placées dans l'agenda par le pro, sur une réservation acceptée.
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
     * Remboursée au client avec sa part des frais Kennelo. Sa place dans l'agenda est libérée.
     */
    public function destroy(Request $request, Activity $activity, Booking $booking, BookingItem $item): BookingResource
    {
        $this->authorize('manage', $booking);

        return new BookingResource($this->adjustments->removeItem($booking, $item, $request->user()));
    }

    /**
     * Schedule a stay option
     *
     * Place l'option dans l'agenda, ou la déplace : sur une ressource qui travaille dans l'activité, un jour du
     * séjour, sur une plage libre (sinon 409). Le pro choisit l'heure librement.
     */
    public function schedule(ScheduleBookingItemRequest $request, Activity $activity, Booking $booking, BookingItem $item): BookingResource
    {
        $this->authorize('manage', $booking);

        return new BookingResource($this->adjustments->scheduleItem(
            $booking,
            $item,
            AgendaResource::query()->findOrFail($request->validated('resource_id')),
            CarbonImmutable::parse($request->validated('starts_at')),
            $request->user(),
        ));
    }
}
