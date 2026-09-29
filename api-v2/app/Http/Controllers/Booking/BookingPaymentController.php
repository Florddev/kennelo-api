<?php

declare(strict_types=1);

namespace App\Http\Controllers\Booking;

use App\Enums\PaymentStatusEnum;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingPayment;
use App\Services\Booking\BookingPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

/**
 * @tags Bookings
 */
class BookingPaymentController extends Controller
{
    public function __construct(
        private readonly BookingPaymentService $payments,
    ) {}

    /**
     * Confirm a payment
     *
     * Pour un paiement que la banque du client lui demande d'authentifier (3-D Secure) : renvoie le secret à
     * passer à Stripe.js. Le résultat arrive ensuite par le webhook de Stripe.
     */
    public function confirm(Booking $booking, BookingPayment $payment): JsonResponse
    {
        $this->authorize('act', $booking);

        if ($payment->status !== PaymentStatusEnum::REQUIRES_ACTION) {
            throw ValidationException::withMessages(['payment' => __('booking.nothing_to_confirm')]);
        }

        return response()->json(['client_secret' => $this->payments->clientSecret($payment)]);
    }
}
