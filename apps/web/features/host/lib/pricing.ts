import type { CapacityModel } from "@workspace/modules/establishments";
import type { PetModel } from "@workspace/modules/pets";

export function minPricePerNight(capacities: CapacityModel[]): number | null {
    if (capacities.length === 0) return null;
    return capacities.reduce(
        (min, capacity) =>
            capacity.pricePerNight < min ? capacity.pricePerNight : min,
        capacities[0]!.pricePerNight,
    );
}

export function sumPetsPricePerNight(
    pets: PetModel[],
    capacities: CapacityModel[],
): number {
    return pets.reduce((total, pet) => {
        const capacity = capacities.find((c) => c.animalType.id === pet.animalTypeId);
        return capacity ? total + capacity.pricePerNight : total;
    }, 0);
}

export function acceptedAnimalTypeIds(capacities: CapacityModel[]): number[] {
    return capacities.map((capacity) => capacity.animalType.id);
}

export type PetAvailability = {
    pet: PetModel;
    status: "available" | "type-not-accepted" | "capacity-full";
};

export function resolvePetsAvailability(
    pets: PetModel[],
    capacities: CapacityModel[],
): PetAvailability[] {
    return pets.map((pet) => {
        const capacity = capacities.find((c) => c.animalType.id === pet.animalTypeId);
        if (!capacity) {
            return { pet, status: "type-not-accepted" };
        }
        if (capacity.availableSpots <= 0) {
            return { pet, status: "capacity-full" };
        }
        return { pet, status: "available" };
    });
}
