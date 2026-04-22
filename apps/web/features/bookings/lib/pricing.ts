import type { CapacityModel } from "@workspace/modules/establishments";
import type { PetModel } from "@workspace/modules/pets";

export function sumPetsPricePerNight(
    selectedPets: PetModel[],
    capacities: CapacityModel[],
): number {
    return selectedPets.reduce((total, pet) => {
        const capacity = capacities.find((c) => c.animalType.id === pet.animalTypeId);
        return capacity ? total + capacity.pricePerNight : total;
    }, 0);
}

export type BookingTotals = {
    pricePerNight: number;
    nights: number;
    total: number;
};

export function computeBookingTotals(
    selectedPets: PetModel[],
    capacities: CapacityModel[],
    nights: number,
): BookingTotals {
    const pricePerNight = sumPetsPricePerNight(selectedPets, capacities);
    return {
        pricePerNight,
        nights,
        total: pricePerNight * nights,
    };
}
