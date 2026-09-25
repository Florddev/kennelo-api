<?php

declare(strict_types=1);

namespace App\Services\Booking;

use App\Enums\BookingStatusEnum;
use App\Enums\NotificationTypeEnum;
use App\Enums\OrganizationPermissionEnum;
use App\Enums\PaymentStatusEnum;
use App\Events\Booking\BookingCompleted;
use App\Events\Booking\BookingExpired;
use App\Models\Booking;
use App\Services\Notification\NotificationRecipientResolver;
use App\Services\Notification\NotificationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Étapes automatiques d'une réservation, lancées par les tâches planifiées (routes/console.php). Chaque
 * réservation est reprise sous verrou : une étape déjà franchie entre-temps est ignorée.
 */
class BookingLifecycleService
{
    public function __construct(
        private readonly BookingService $bookings,
        private readonly NotificationService $notifications,
        private readonly NotificationRecipientResolver $recipients,
    ) {}

    /**
     * Une demande sans réponse expire à la fin du délai d'acceptation (réglage acceptance_window_hours), ou au
     * plus tard le jour où le séjour devait commencer. L'autorisation de paiement est libérée.
     */
    public function expirePending(): int
    {
        $expired = 0;

        Booking::query()
            ->where('status', BookingStatusEnum::PENDING)
            ->where(fn (Builder $query) => $query
                ->where('created_at', '<=', now()->subHours((int) setting('acceptance_window_hours')))
                ->orWhereDate('start_date', '<=', today()))
            ->lazyById()
            ->each(function (Booking $booking) use (&$expired): void {
                if ($this->advance($booking, BookingStatusEnum::EXPIRED)) {
                    BookingExpired::dispatch($booking);
                    $expired++;
                }
            });

        return $expired;
    }

    /**
     * Rappelle une fois à l'équipe une demande qui attend sa réponse depuis reminder_after_hours.
     */
    public function remindPending(): int
    {
        $reminded = 0;

        Booking::query()
            ->where('status', BookingStatusEnum::PENDING)
            ->where('payment_status', PaymentStatusEnum::REQUIRES_CAPTURE)
            ->whereNull('reminded_at')
            ->where('created_at', '<=', now()->subHours((int) setting('reminder_after_hours')))
            ->with(['organization', 'activity'])
            ->lazyById()
            ->each(function (Booking $booking) use (&$reminded): void {
                $booking->forceFill(['reminded_at' => now()])->save();

                if ($booking->organization !== null) {
                    $this->notifications->notify(
                        $this->recipients->membersAllowedTo(OrganizationPermissionEnum::BOOKINGS_MANAGE, $booking->organization, $booking->activity_id),
                        NotificationTypeEnum::BOOKING_REMINDER,
                        ['booking_id' => $booking->id, 'activity_id' => $booking->activity_id, 'activity_name' => $booking->activity?->name],
                    );
                }

                $reminded++;
            });

        return $reminded;
    }

    /**
     * Le séjour commence le jour de l'arrivée, et se termine une fois passé le jour du départ.
     *
     * @return array{started: int, completed: int}
     */
    public function advanceStays(): array
    {
        $started = 0;
        $completed = 0;

        Booking::query()
            ->where('status', BookingStatusEnum::CONFIRMED)
            ->whereDate('start_date', '<=', today())
            ->whereDate('end_date', '>=', today())
            ->lazyById()
            ->each(function (Booking $booking) use (&$started): void {
                $started += (int) $this->advance($booking, BookingStatusEnum::IN_PROGRESS);
            });

        Booking::query()
            ->whereIn('status', [BookingStatusEnum::CONFIRMED, BookingStatusEnum::IN_PROGRESS])
            ->whereDate('end_date', '<', today())
            ->lazyById()
            ->each(function (Booking $booking) use (&$completed): void {
                if ($this->advance($booking, BookingStatusEnum::COMPLETED)) {
                    BookingCompleted::dispatch($booking);
                    $completed++;
                }
            });

        return ['started' => $started, 'completed' => $completed];
    }

    private function advance(Booking $booking, BookingStatusEnum $status): bool
    {
        return DB::transaction(function () use ($booking, $status): bool {
            $this->bookings->lock($booking);

            if (! $booking->status->canTransitionTo($status)) {
                return false;
            }

            if ($status === BookingStatusEnum::EXPIRED) {
                $this->bookings->releaseInitial($booking);
            }

            $this->bookings->transition($booking, $status);

            return true;
        });
    }
}
