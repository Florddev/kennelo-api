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
 *
 * Les requêtes présélectionnent large sur les dates ; le début et la fin exacts (Booking::startsAt(), endsAt())
 * se lisent ensuite dans le fuseau de l'activité, à l'heure près pour un rendez-vous.
 */
class BookingLifecycleService
{
    /**
     * Ce qu'il faut pour lire le début et la fin exacts d'une réservation.
     */
    private const array TIMING = ['activity.profession', 'items'];

    public function __construct(
        private readonly BookingService $bookings,
        private readonly NotificationService $notifications,
        private readonly NotificationRecipientResolver $recipients,
    ) {}

    /**
     * Une demande sans réponse expire à la fin du délai d'acceptation (réglage acceptance_window_hours), ou au
     * plus tard quand la réservation devait commencer. L'autorisation de paiement est libérée, et le créneau
     * d'un rendez-vous aussi.
     */
    public function expirePending(): int
    {
        $expired = 0;
        $deadline = now()->subHours((int) setting('acceptance_window_hours'));

        Booking::query()
            ->where('status', BookingStatusEnum::PENDING)
            ->where(fn (Builder $query) => $query
                ->where('created_at', '<=', $deadline)
                ->orWhereDate('start_date', '<=', today()->addDay()))
            ->with(self::TIMING)
            ->lazyById()
            ->each(function (Booking $booking) use (&$expired, $deadline): void {
                if ($booking->created_at?->greaterThan($deadline) && $booking->startsAt()->isFuture()) {
                    return;
                }

                if ($this->move($booking, BookingStatusEnum::EXPIRED)) {
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
     * Une réservation confirmée commence à son début : le jour de l'arrivée pour un séjour, l'heure du rendez-vous.
     * Elle se termine à sa fin : une fois passé le jour du départ, ou le rendez-vous fini.
     *
     * @return array{started: int, completed: int}
     */
    public function advance(): array
    {
        $started = 0;
        $completed = 0;

        Booking::query()
            ->where('status', BookingStatusEnum::CONFIRMED)
            ->whereDate('start_date', '<=', today()->addDay())
            ->with(self::TIMING)
            ->lazyById()
            ->each(function (Booking $booking) use (&$started): void {
                if (! $booking->startsAt()->isFuture() && $booking->endsAt()->isFuture()) {
                    $started += (int) $this->move($booking, BookingStatusEnum::IN_PROGRESS);
                }
            });

        Booking::query()
            ->whereIn('status', [BookingStatusEnum::CONFIRMED, BookingStatusEnum::IN_PROGRESS])
            ->whereDate('end_date', '<=', today())
            ->with(self::TIMING)
            ->lazyById()
            ->each(function (Booking $booking) use (&$completed): void {
                if (! $booking->endsAt()->isFuture() && $this->move($booking, BookingStatusEnum::COMPLETED)) {
                    BookingCompleted::dispatch($booking);
                    $completed++;
                }
            });

        return ['started' => $started, 'completed' => $completed];
    }

    private function move(Booking $booking, BookingStatusEnum $status): bool
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
