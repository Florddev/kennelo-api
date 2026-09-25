<?php

declare(strict_types=1);

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use App\Http\Resources\FinancialOperationResource;
use App\Models\Activity;
use App\Models\Booking;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @tags Bookings
 */
class BookingOperationController extends Controller
{
    /**
     * Financial journal of a booking
     *
     * Chaque mouvement d'argent (autorisation, capture, remboursement, versement) et chaque changement de statut,
     * dans l'ordre.
     */
    public function index(Activity $activity, Booking $booking): AnonymousResourceCollection
    {
        $this->authorize('manage', $booking);

        return FinancialOperationResource::collection($booking->operations()->get());
    }
}
