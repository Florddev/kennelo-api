<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Enums\CancelledByRoleEnum;
use App\Enums\DisputeStatusEnum;
use App\Enums\NotificationTypeEnum;
use App\Enums\OrganizationPermissionEnum;
use App\Enums\PaymentKindEnum;
use App\Events\Booking\BookingCancelled;
use App\Events\Booking\BookingCompleted;
use App\Events\Booking\BookingConfirmed;
use App\Events\Booking\BookingCreated;
use App\Events\Booking\BookingDisputeClosed;
use App\Events\Booking\BookingDisputeOpened;
use App\Events\Booking\BookingExpired;
use App\Events\Booking\BookingPaidOut;
use App\Events\Booking\BookingPaymentActionRequired;
use App\Events\Booking\BookingPaymentCaptured;
use App\Events\Booking\BookingRefunded;
use App\Events\Booking\BookingRejected;
use App\Models\Booking;
use App\Services\Notification\NotificationRecipientResolver;
use App\Services\Notification\NotificationService;

/**
 * Prévient le client ou l'équipe de l'activité à chaque étape d'une réservation. Les événements partent après
 * le commit ; les notifications elles-mêmes sont envoyées en file d'attente.
 */
class SendBookingNotifications
{
    public function __construct(
        private readonly NotificationService $notifications,
        private readonly NotificationRecipientResolver $recipients,
    ) {}

    public function handleCreated(BookingCreated $event): void
    {
        $this->notifyTeam($event->booking, NotificationTypeEnum::BOOKING_CREATED, OrganizationPermissionEnum::BOOKINGS_MANAGE);
    }

    public function handleConfirmed(BookingConfirmed $event): void
    {
        $this->notifyClient($event->booking, NotificationTypeEnum::BOOKING_CONFIRMED);
    }

    public function handleRejected(BookingRejected $event): void
    {
        $this->notifyClient($event->booking, NotificationTypeEnum::BOOKING_REJECTED);
    }

    public function handleExpired(BookingExpired $event): void
    {
        $this->notifyClient($event->booking, NotificationTypeEnum::BOOKING_EXPIRED);
    }

    public function handleCancelled(BookingCancelled $event): void
    {
        $booking = $event->booking;

        match (true) {
            $booking->cancelled_by_role === CancelledByRoleEnum::CLIENT => $this->notifyTeam($booking, NotificationTypeEnum::BOOKING_CANCELLED_BY_CLIENT, OrganizationPermissionEnum::BOOKINGS_VIEW),
            $booking->cancelled_by_role === CancelledByRoleEnum::PRO => $this->notifyClient($booking, NotificationTypeEnum::BOOKING_CANCELLED_BY_PRO),
            $booking->cancelled_by !== null => $this->notifyPlatformCancellation($booking),
            default => $this->notifyClient($booking, NotificationTypeEnum::PAYMENT_FAILED, ['amount' => $booking->total_price]),
        };
    }

    public function handleCompleted(BookingCompleted $event): void
    {
        $this->notifyClient($event->booking, NotificationTypeEnum::BOOKING_COMPLETED);
    }

    public function handleRefunded(BookingRefunded $event): void
    {
        $this->notifyClient($event->booking, NotificationTypeEnum::PAYMENT_REFUNDED, ['amount' => $event->amount]);
    }

    /**
     * Le paiement initial est annoncé par la confirmation ; seuls les compléments ont leur propre message.
     */
    public function handlePaymentCaptured(BookingPaymentCaptured $event): void
    {
        if ($event->payment->kind === PaymentKindEnum::SUPPLEMENT && $event->payment->loadMissing('booking')->booking !== null) {
            $this->notifyClient($event->payment->booking, NotificationTypeEnum::PAYMENT_SUCCEEDED, ['amount' => $event->payment->amount]);
        }
    }

    public function handlePaymentActionRequired(BookingPaymentActionRequired $event): void
    {
        if ($event->payment->loadMissing('booking')->booking !== null) {
            $this->notifyClient($event->payment->booking, NotificationTypeEnum::PAYMENT_ACTION_REQUIRED, [
                'amount' => $event->payment->amount,
                'payment_id' => $event->payment->id,
            ]);
        }
    }

    public function handlePaidOut(BookingPaidOut $event): void
    {
        if ($event->payout->loadMissing('booking')->booking !== null) {
            $this->notifyTeam($event->payout->booking, NotificationTypeEnum::PAYOUT_SENT, OrganizationPermissionEnum::FINANCE_VIEW, [
                'amount' => $event->payout->amount,
            ]);
        }
    }

    public function handleDisputeOpened(BookingDisputeOpened $event): void
    {
        $dispute = $event->dispute->loadMissing('booking.activity');

        if ($dispute->booking === null) {
            return;
        }

        $this->notifications->notify($this->recipients->admins(), NotificationTypeEnum::DISPUTE_OPENED, [
            ...$this->payload($dispute->booking),
            'amount' => $dispute->amount,
            'due_by' => $dispute->evidence_due_by?->toDateString(),
        ]);
        $this->notifyTeam($dispute->booking, NotificationTypeEnum::BOOKING_DISPUTED, OrganizationPermissionEnum::FINANCE_VIEW, ['amount' => $dispute->amount]);
    }

    public function handleDisputeClosed(BookingDisputeClosed $event): void
    {
        $dispute = $event->dispute->loadMissing('booking');

        if ($dispute->booking === null) {
            return;
        }

        $this->notifyTeam(
            $dispute->booking,
            $dispute->status === DisputeStatusEnum::LOST ? NotificationTypeEnum::BOOKING_DISPUTE_LOST : NotificationTypeEnum::BOOKING_DISPUTE_WON,
            OrganizationPermissionEnum::FINANCE_VIEW,
            ['amount' => $dispute->recovered_amount],
        );
    }

    private function notifyPlatformCancellation(Booking $booking): void
    {
        $this->notifyClient($booking, NotificationTypeEnum::BOOKING_CANCELLED_BY_PLATFORM);
        $this->notifyTeam($booking, NotificationTypeEnum::TEAM_BOOKING_CANCELLED_BY_PLATFORM, OrganizationPermissionEnum::BOOKINGS_VIEW);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function notifyClient(Booking $booking, NotificationTypeEnum $type, array $data = []): void
    {
        if ($booking->loadMissing(['user', 'activity'])->user !== null) {
            $this->notifications->notify($booking->user, $type, [...$this->payload($booking), ...$data]);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function notifyTeam(Booking $booking, NotificationTypeEnum $type, OrganizationPermissionEnum $permission, array $data = []): void
    {
        if ($booking->loadMissing(['organization', 'activity'])->organization !== null) {
            $this->notifications->notify(
                $this->recipients->membersAllowedTo($permission, $booking->organization, $booking->activity_id),
                $type,
                [...$this->payload($booking), ...$data],
            );
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(Booking $booking): array
    {
        return [
            'booking_id' => $booking->id,
            'activity_id' => $booking->activity_id,
            'activity_name' => $booking->activity?->name,
            'start_date' => $booking->start_date->toDateString(),
            'end_date' => $booking->end_date->toDateString(),
            'starts_at' => $booking->isAppointment() ? $booking->startsAt()->toISOString() : null,
        ];
    }
}
