import type {
    ActivityCycleModel,
    ActivityCycleSettingModel,
    PriceCalendar,
} from "@workspace/modules/activities";
import type { PetModel } from "@workspace/modules/pets";
import { fromApiDate, toApiDate, weekdayMaskContains } from "@workspace/common";

function weekdayBitForDate(date: Date): number {
    return 1 << ((date.getDay() + 6) % 7);
}

export function resolveActiveCycle(
    date: Date,
    cycles: ActivityCycleModel[],
): ActivityCycleModel | null {
    const apiDate = toApiDate(date);
    return (
        cycles
            .filter(
                (cycle) =>
                    cycle.isActive &&
                    (cycle.startDate === null || cycle.startDate <= apiDate) &&
                    (cycle.endDate === null || cycle.endDate >= apiDate),
            )
            .sort((a, b) => b.priority - a.priority)[0] ?? null
    );
}

export function priceForAnimalTypeOnDate(
    date: Date,
    animalTypeId: string,
    cycles: ActivityCycleModel[],
): number | null {
    const cycle = resolveActiveCycle(date, cycles);
    if (!cycle) return null;

    const dayBit = weekdayBitForDate(date);
    const isClosed = cycle.closedWeekDays.some((closed) =>
        weekdayMaskContains(closed.sumWeekdays, dayBit),
    );
    if (isClosed) return null;

    const prices = cycle.settings
        .filter(
            (setting) =>
                setting.animalType.id === animalTypeId &&
                weekdayMaskContains(setting.sumWeekdays, dayBit),
        )
        .map((setting) => setting.price);
    if (prices.length === 0) return null;

    return Math.min(...prices);
}

export function priceForAnimalTypesOnDate(
    date: Date,
    animalTypeIds: string[],
    cycles: ActivityCycleModel[],
): number | null {
    if (animalTypeIds.length === 0) return null;

    let total = 0;
    for (const animalTypeId of animalTypeIds) {
        const price = priceForAnimalTypeOnDate(date, animalTypeId, cycles);
        if (price === null) return null;
        total += price;
    }
    return total;
}

export function buildPetPriceMap(
    priceMap: PriceCalendar,
    animalTypeIds: string[],
    cycles: ActivityCycleModel[],
): PriceCalendar {
    if (animalTypeIds.length === 0) return priceMap;

    const result: PriceCalendar = {};
    for (const dateKey of Object.keys(priceMap)) {
        result[dateKey] = priceForAnimalTypesOnDate(fromApiDate(dateKey), animalTypeIds, cycles);
    }
    return result;
}

export function minAvailablePrice(priceMap: PriceCalendar): number | null {
    const prices = Object.values(priceMap).filter((price): price is number => price !== null);
    if (prices.length === 0) return null;
    return Math.min(...prices);
}

export function totalPriceForRange(priceMap: PriceCalendar, from: Date, to: Date): number | null {
    const cursor = new Date(from);
    cursor.setHours(0, 0, 0, 0);
    const end = new Date(to);
    end.setHours(0, 0, 0, 0);

    let total = 0;
    let counted = 0;
    while (cursor < end) {
        const price = priceMap[toApiDate(cursor)];
        if (price !== null) {
            total += price ?? 0;
            counted += 1;
        }
        cursor.setDate(cursor.getDate() + 1);
    }

    return counted > 0 ? total : null;
}

export function minPricePerNight(capacities: ActivityCycleSettingModel[]): number | null {
    if (capacities.length === 0) return null;
    return capacities.reduce(
        (min, capacity) => (capacity.price < min ? capacity.price : min),
        capacities[0]!.price,
    );
}

export function sumPetsPricePerNight(
    pets: PetModel[],
    capacities: ActivityCycleSettingModel[],
): number {
    return pets.reduce((total, pet) => {
        const capacity = capacities.find((c) => c.animalType.id === pet.animalTypeId);
        return capacity ? total + capacity.price : total;
    }, 0);
}

export function acceptedAnimalTypeIds(capacities: ActivityCycleSettingModel[]): string[] {
    return capacities.map((capacity) => capacity.animalType.id);
}

export function isAnimalTypeAvailableForDates(
    animalTypeId: string,
    capacities: ActivityCycleSettingModel[],
    cycles: ActivityCycleModel[],
    from: Date | undefined,
    to: Date | undefined,
): boolean {
    const capacity = capacities.find((c) => c.animalType.id === animalTypeId);
    if (!capacity || capacity.availableSpots <= 0) {
        return false;
    }
    if (!from || !to) {
        return true;
    }

    const cursor = new Date(from);
    cursor.setHours(0, 0, 0, 0);
    const end = new Date(to);
    end.setHours(0, 0, 0, 0);

    while (cursor < end) {
        if (priceForAnimalTypeOnDate(cursor, animalTypeId, cycles) === null) {
            return false;
        }
        cursor.setDate(cursor.getDate() + 1);
    }

    return true;
}
