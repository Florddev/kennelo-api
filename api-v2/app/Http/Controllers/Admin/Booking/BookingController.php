<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Booking;

use App\Enums\AdminActionTypeEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Booking\CancelBookingRequest;
use App\Http\Requests\Admin\Booking\ListBookingsRequest;
use App\Http\Requests\Admin\Booking\RefundBookingRequest;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use App\Services\Admin\AdminActionService;
use App\Services\Admin\Booking\AdminBookingService;
use App\Services\Booking\CancellationService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @tags Admin Bookings
 */
class BookingController extends Controller
{
    public function __construct(
        private readonly AdminBookingService $bookings,
        private readonly CancellationService $cancellations,
        private readonly AdminActionService $actions,
    ) {}

    /**
     * List bookings
     *
     * search cherche dans le nom et l'e-mail du client, le nom de l'activité et celui de l'entreprise. from et to
     * gardent les réservations qui touchent la période ; disputed, celles qui ont un litige ouvert (ou aucun).
     */
    public function index(ListBookingsRequest $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Booking::class);

        return BookingResource::collection($this->bookings->paginate($request->validated()));
    }

    /**
     * Show a booking
     *
     * Avec ses paiements, remboursements, versement, litiges et son journal financier.
     */
    public function show(Booking $booking): BookingResource
    {
        $this->authorize('administer', $booking);

        return new BookingResource($this->bookings->load($booking));
    }

    /**
     * Cancel a booking
     *
     * refund=full (par défaut) rembourse tout, frais Kennelo compris ; refund=policy applique la politique
     * d'annulation de la réservation. Une demande pas encore acceptée est annulée sans frais. Refusé pendant un
     * litige. Le client et l'équipe sont prévenus.
     */
    public function cancel(CancelBookingRequest $request, Booking $booking): BookingResource
    {
        $this->authorize('administer', $booking);

        $this->cancellations->cancelByPlatform($booking, $request->user(), $request->appliesPolicy());
        $this->log($request, $booking, AdminActionTypeEnum::CANCEL_BOOKING, $request->validated());

        return new BookingResource($this->bookings->load($booking));
    }

    /**
     * Refund part of a booking
     *
     * Geste commercial : amount est pris sur les prestations, la part de l'entreprise et la commission baissent
     * dans la même proportion. service_fee rend aussi les frais Kennelo. Refusé une fois le versement parti, ou
     * pendant un litige.
     */
    public function refund(RefundBookingRequest $request, Booking $booking): BookingResource
    {
        $this->authorize('administer', $booking);

        $this->cancellations->refundGoodwill($booking, $request->user(), $request->amount(), $request->boolean('service_fee'));
        $this->log($request, $booking, AdminActionTypeEnum::REFUND_BOOKING, $request->validated());

        return new BookingResource($this->bookings->load($booking));
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    private function log(Request $request, Booking $booking, AdminActionTypeEnum $action, array $metadata): void
    {
        $this->actions->log($request->user(), $booking->user, $action, ['booking_id' => $booking->id, ...$metadata]);
    }
}
