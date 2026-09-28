<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Enums\CancelledByRoleEnum;
use App\Events\Booking\BookingCancelled;
use App\Events\Booking\BookingCompleted;
use App\Events\Booking\BookingConfirmed;
use App\Events\Booking\BookingCreated;
use App\Events\Booking\BookingExpired;
use App\Events\Booking\BookingRejected;
use App\Models\Booking;
use App\Services\Conversation\ConversationService;
use App\Services\Conversation\MessageService;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Chaque étape d'une réservation laisse un message système dans son fil : la demande ouvre la conversation du
 * client avec l'activité, une fin (terminée, annulée, refusée, expirée) archive le fil. Le code de réservation
 * n'appelle jamais la messagerie : tout passe par ses événements.
 */
class PostBookingSystemMessages implements ShouldQueue
{
    public int $tries = 5;

    public int $backoff = 60;

    public function __construct(
        private readonly MessageService $messages,
        private readonly ConversationService $conversations,
    ) {}

    public function handleCreated(BookingCreated $event): void
    {
        $this->messages->postSystem($event->booking, 'booking_created');
    }

    public function handleConfirmed(BookingConfirmed $event): void
    {
        $this->messages->postSystem($event->booking, 'booking_confirmed');
    }

    public function handleRejected(BookingRejected $event): void
    {
        $this->close($event->booking, 'booking_rejected');
    }

    public function handleExpired(BookingExpired $event): void
    {
        $this->close($event->booking, 'booking_expired');
    }

    public function handleCancelled(BookingCancelled $event): void
    {
        $this->close($event->booking, match (true) {
            $event->booking->cancelled_by_role === CancelledByRoleEnum::CLIENT => 'booking_cancelled_by_client',
            $event->booking->cancelled_by_role === CancelledByRoleEnum::PRO => 'booking_cancelled_by_pro',
            $event->booking->cancelled_by !== null => 'booking_cancelled_by_platform',
            default => 'booking_cancelled',
        });
    }

    public function handleCompleted(BookingCompleted $event): void
    {
        $this->close($event->booking, 'booking_completed');
    }

    private function close(Booking $booking, string $event): void
    {
        $this->messages->postSystem($booking, $event);
        $this->conversations->archive($booking);
    }
}
