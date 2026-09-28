<?php

declare(strict_types=1);

namespace App\Services\Hosting;

use App\Enums\BookingStatusEnum;
use App\Enums\OrganizationPermissionEnum;
use App\Models\Activity;
use App\Models\Booking;
use App\Models\Organization;
use App\Models\Pet;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Animaux confiés aujourd'hui à une entreprise : ceux des réservations confirmées ou en cours qui couvrent la date
 * du jour, séjours comme rendez-vous. Chaque membre ne voit que les activités où il a bookings.view.
 *
 * La recherche par puce (un animal scanné) se limite à ces animaux : en V1, n'importe quel compte retrouvait un
 * animal et son propriétaire à partir de sa puce.
 */
class InCarePetService
{
    /**
     * Un animal par réservation qui le couvre, avec cette réservation (relation careBooking).
     *
     * @return Collection<int, Pet>
     */
    public function forOrganization(Organization $organization, User $member, ?string $microchip = null): Collection
    {
        $scanned = fn ($pets) => $pets->when($microchip !== null, fn ($pets) => $pets->where('pets.microchip_number', $microchip));

        return $this->current($member)
            ->where('bookings.organization_id', $organization->id)
            ->when($microchip !== null, fn (Builder $bookings) => $bookings->whereHas('pets', $scanned))
            ->with(['activity', 'user', 'pets' => fn ($pets) => $scanned($pets)->with(['animalType', 'animalBreed', 'media'])])
            ->orderBy('start_date')
            ->orderBy('id')
            ->get()
            ->flatMap(fn (Booking $booking): Collection => $booking->pets->map(fn (Pet $pet): Pet => $pet->setRelation('careBooking', $booking)))
            ->values();
    }

    /**
     * Réservations qui couvrent aujourd'hui, dans les activités où la personne voit les réservations.
     *
     * @return Builder<Booking>
     */
    private function current(User $member): Builder
    {
        $today = CarbonImmutable::now((string) config('activities.default_timezone'))->toDateString();

        return Booking::query()
            ->whereIn('bookings.activity_id', Activity::query()->allowing($member, OrganizationPermissionEnum::BOOKINGS_VIEW)->select('activities.id'))
            ->whereIn('bookings.status', [BookingStatusEnum::CONFIRMED, BookingStatusEnum::IN_PROGRESS])
            ->whereDate('bookings.start_date', '<=', $today)
            ->whereDate('bookings.end_date', '>=', $today);
    }
}
