<?php

declare(strict_types=1);

namespace App\Services\Establishment;

use App\Enums\BookingStatusEnum;
use App\Models\Establishment;
use App\Models\EstablishmentCapacity;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class EstablishmentCapacityService
{
    public function getCapacitiesWithOccupancy(Establishment $establishment, ?string $date): Collection
    {
        $date = $date ?? now()->toDateString();

        $capacities = EstablishmentCapacity::with('animalType')
            ->where('establishment_id', $establishment->id)
            ->get();

        $occupancy = DB::table('booking_pets')
            ->join('pets', 'booking_pets.pet_id', '=', 'pets.id')
            ->join('bookings', 'bookings.id', '=', 'booking_pets.booking_id')
            ->where('bookings.establishment_id', $establishment->id)
            ->whereIn('bookings.status', [BookingStatusEnum::CONFIRMED->value, BookingStatusEnum::IN_PROGRESS->value])
            ->where('bookings.check_in_date', '<=', $date)
            ->where('bookings.check_out_date', '>=', $date)
            ->groupBy('pets.animal_type_id')
            ->selectRaw('pets.animal_type_id, COUNT(*) as count')
            ->pluck('count', 'pets.animal_type_id');

        return $capacities->each(function (EstablishmentCapacity $capacity) use ($occupancy): void {
            $capacity->occupied_spots = (int) ($occupancy[$capacity->animal_type_id] ?? 0);
        });
    }

    public function create(Establishment $establishment, array $data): EstablishmentCapacity
    {
        $capacity = EstablishmentCapacity::create([
            'establishment_id' => $establishment->id,
            ...$data,
        ]);

        return $capacity->load('animalType');
    }

    public function update(EstablishmentCapacity $capacity, array $data): EstablishmentCapacity
    {
        $capacity->update($data);

        return $capacity->fresh('animalType');
    }

    public function delete(EstablishmentCapacity $capacity): void
    {
        $capacity->delete();
    }
}
