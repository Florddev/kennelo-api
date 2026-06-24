<?php

declare(strict_types=1);

namespace App\Services\Hosting;

use App\Enums\BookingStatusEnum;
use App\Models\Activity;
use App\Models\Booking;
use App\Models\Pet;
use App\Models\User;
use App\Services\MediaService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class HostScanService
{
    public function findPetByMicrochip(string $microchipNumber): ?Pet
    {
        return Pet::where('microchip_number', $microchipNumber)
            ->with(['animalType', 'petAttributes.attributeDefinition', 'petAttributes.attributeOption', 'media', 'user.media'])
            ->first();
    }

    public function currentBookingForPet(User $user, Pet $pet): ?Booking
    {
        return $this->activeCareQuery($user)
            ->whereHas('pets', fn (Builder $query) => $query->where('pets.id', $pet->id))
            ->with(['activity', 'pets'])
            ->first();
    }

    /** @return Collection<int, Booking> */
    public function pastBookingsForPet(User $user, Pet $pet): Collection
    {
        return Booking::whereIn('activity_id', $this->hostActivityIds($user))
            ->where('status', BookingStatusEnum::COMPLETED->value)
            ->whereHas('pets', fn (Builder $query) => $query->where('pets.id', $pet->id))
            ->with('activity')
            ->orderByDesc('check_out_date')
            ->get();
    }

    /** @return array<int, array<string, mixed>> */
    public function inCarePets(User $user): array
    {
        $bookings = $this->activeCareQuery($user)
            ->with(['activity', 'user', 'pets.animalType', 'pets.media'])
            ->get();

        $pets = [];

        foreach ($bookings as $booking) {
            foreach ($booking->pets as $pet) {
                if (isset($pets[$pet->id])) {
                    continue;
                }

                $pets[$pet->id] = [
                    'id' => $pet->id,
                    'name' => $pet->name,
                    'breed' => $pet->breed,
                    'microchip_number' => $pet->microchip_number,
                    'has_microchip' => $pet->has_microchip,
                    'avatar_url' => $this->avatarUrl($pet),
                    'animal_type' => $pet->animalType === null ? null : [
                        'id' => $pet->animalType->id,
                        'code' => $pet->animalType->code,
                        'name' => $pet->animalType->name,
                        'category' => $pet->animalType->category,
                    ],
                    'owner_name' => $booking->user?->first_name,
                    'activity_name' => $booking->activity?->name,
                    'check_in_date' => $booking->check_in_date->toDateString(),
                    'check_out_date' => $booking->check_out_date->toDateString(),
                ];
            }
        }

        return array_values($pets);
    }

    /** @return array<string, mixed>|null */
    public function ownerPayload(Pet $pet): ?array
    {
        if ($pet->user === null) {
            return null;
        }

        return [
            'id' => $pet->user->id,
            'first_name' => $pet->user->first_name,
            'last_name' => $pet->user->last_name,
            'phone' => $pet->user->phone,
            'avatar_url' => $this->avatarUrl($pet->user),
        ];
    }

    public function isPetInCare(User $user, Pet $pet): bool
    {
        return $this->activeCareQuery($user)
            ->whereHas('pets', fn (Builder $query) => $query->where('pets.id', $pet->id))
            ->exists();
    }

    public function assignMicrochip(Pet $pet, string $microchipNumber): Pet
    {
        $pet->update([
            'microchip_number' => $microchipNumber,
            'has_microchip' => true,
        ]);

        return $pet->fresh(['animalType', 'petAttributes.attributeDefinition', 'petAttributes.attributeOption', 'media']);
    }

    /** @return Collection<int, string> */
    public function hostActivityIds(User $user): Collection
    {
        return Activity::where('manager_id', $user->id)->pluck('id');
    }

    /** @return Builder<Booking> */
    private function activeCareQuery(User $user): Builder
    {
        $today = Carbon::today()->toDateString();

        return Booking::whereIn('activity_id', $this->hostActivityIds($user))
            ->whereIn('status', [BookingStatusEnum::CONFIRMED->value, BookingStatusEnum::IN_PROGRESS->value])
            ->where('check_in_date', '<=', $today)
            ->where('check_out_date', '>=', $today);
    }

    private function avatarUrl(Pet|User $model): ?string
    {
        return $model->getFirstMediaUrl(MediaService::COLLECTION_AVATAR, MediaService::CONVERSION_AVATAR_WEBP)
            ?: $model->getFirstMediaUrl(MediaService::COLLECTION_AVATAR)
            ?: null;
    }
}
