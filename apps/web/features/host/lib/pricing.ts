import type { ActivityCycleSettingModel } from "@workspace/modules/activities";
import type { PetModel } from "@workspace/modules/pets";

export function minPricePerNight(capacities: ActivityCycleSettingModel[]): number | null {
    if (capacities.length === 0) return null;
    return capacities.reduce(
        (min, capacity) => (capacity.minPrice() < min ? capacity.minPrice() : min),
        capacities[0]!.minPrice(),
    );
}

export function sumPetsPricePerNight(
    pets: PetModel[],
    capacities: ActivityCycleSettingModel[],
): number {
    return pets.reduce((total, pet) => {
        const capacity = capacities.find((c) => c.animalType.id === pet.animalTypeId);
        return capacity ? total + capacity.averagePrice() : total;
    }, 0);
}

export function acceptedAnimalTypeIds(capacities: ActivityCycleSettingModel[]): string[] {
    return capacities.map((capacity) => capacity.animalType.id);
}

export type PetAvailability = {
    pet: PetModel;
    status: "available" | "type-not-accepted" | "capacity-full";
};

export function resolvePetsAvailability(
    pets: PetModel[],
    capacities: ActivityCycleSettingModel[],
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
