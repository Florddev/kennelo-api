<?php

declare(strict_types=1);

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use App\Http\Requests\Booking\ListBookingsRequest;
use App\Http\Requests\Booking\QuoteBookingRequest;
use App\Http\Requests\Booking\StoreBookingRequest;
use App\Http\Resources\BookingQuoteResource;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use App\Services\Booking\BookingService;
use App\Services\Booking\CancellationService;
use App\Services\Booking\QuoteService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Réservations du client.
 *
 * @tags Bookings
 */
class BookingController extends Controller
{
    public function __construct(
        private readonly BookingService $bookings,
        private readonly QuoteService $quotes,
        private readonly CancellationService $cancellations,
    ) {}

    /**
     * Quote a stay
     *
     * Même calcul que la réservation : dates, places et animaux, options. Rien n'est enregistré.
     */
    public function quote(QuoteBookingRequest $request): BookingQuoteResource
    {
        return new BookingQuoteResource($this->quotes->quote($request->user(), $request->validated()));
    }

    /**
     * Book a stay
     *
     * Le paiement est autorisé, puis capturé quand le pro accepte. Si la banque exige 3-D Secure, la réponse
     * porte le client_secret à confirmer dans le front ; la demande n'est présentée au pro qu'après.
     */
    public function store(StoreBookingRequest $request): BookingResource
    {
        return new BookingResource($this->bookings->create($request->user(), $request->validated()));
    }

    /**
     * List my bookings
     */
    public function index(ListBookingsRequest $request): AnonymousResourceCollection
    {
        return BookingResource::collection($this->bookings->forClient($request->user(), $request->validated()));
    }

    /**
     * Show a booking
     *
     * Pour son client et pour l'équipe de l'activité.
     */
    public function show(Booking $booking): BookingResource
    {
        $this->authorize('view', $booking);

        return new BookingResource($booking->load(BookingService::RELATIONS));
    }

    /**
     * Cancel my booking
     *
     * Sans frais tant que le pro n'a pas accepté. Ensuite, remboursement selon la politique d'annulation, comptée
     * depuis le début du séjour ; un séjour commencé ne s'annule plus.
     */
    public function cancel(Request $request, Booking $booking): BookingResource
    {
        $this->authorize('act', $booking);

        return new BookingResource($this->cancellations->cancelByClient($booking, $request->user()));
    }
}
