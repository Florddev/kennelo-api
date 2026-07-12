import {
    eachDayOfInterval,
    endOfMonth,
    endOfWeek,
    isSameDay,
    isSameMonth,
    startOfDay,
    startOfMonth,
    startOfWeek,
} from "date-fns";

import type { BookingModel, BookingPetModel, BookingStatus } from "@workspace/modules/bookings";

export type MonthStats = {
    arrivals: number;
    departures: number;
    peak: number;
    stays: number;
};

export type PetMovement = {
    bookingId: string;
    activityId: string;
    petId: string;
    petName: string;
    animalTypeCode: string | null;
    animalTypeName: string | null;
    customerName: string;
    status: BookingStatus;
};

export type DayMovements = {
    arrivals: PetMovement[];
    departures: PetMovement[];
    staying: PetMovement[];
    occupancy: number;
};

export const EMPTY_DAY_MOVEMENTS: DayMovements = {
    arrivals: [],
    departures: [],
    staying: [],
    occupancy: 0,
};

export function dayKey(date: Date): string {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, "0");
    const day = String(date.getDate()).padStart(2, "0");
    return `${year}-${month}-${day}`;
}

export function getMonthRange(focusedMonth: Date, weekStartsOn: 0 | 1) {
    const monthStart = startOfMonth(focusedMonth);
    const monthEnd = endOfMonth(focusedMonth);
    const gridStart = startOfWeek(monthStart, { weekStartsOn });
    const gridEnd = endOfWeek(monthEnd, { weekStartsOn });
    return { monthStart, monthEnd, gridStart, gridEnd };
}

export function buildMonthWeeks(focusedMonth: Date, weekStartsOn: 0 | 1): Date[][] {
    const { gridStart, gridEnd } = getMonthRange(focusedMonth, weekStartsOn);
    const allDays = eachDayOfInterval({ start: gridStart, end: gridEnd });
    const weeks: Date[][] = [];
    for (let i = 0; i < allDays.length; i += 7) {
        weeks.push(allDays.slice(i, i + 7));
    }
    return weeks;
}

function toMovement(booking: BookingModel, pet: BookingPetModel): PetMovement {
    return {
        bookingId: booking.id,
        activityId: booking.activityId,
        petId: pet.id,
        petName: pet.name,
        animalTypeCode: pet.animalType?.code ?? null,
        animalTypeName: pet.animalType?.name ?? null,
        customerName: booking.user ? booking.user.getFullName() : "—",
        status: booking.status,
    };
}

export function movementsForDay(date: Date, bookings: BookingModel[]): DayMovements {
    const target = startOfDay(date);
    const arrivals: PetMovement[] = [];
    const departures: PetMovement[] = [];
    const staying: PetMovement[] = [];
    let occupancy = 0;

    for (const booking of bookings) {
        const checkIn = startOfDay(new Date(booking.checkInDate));
        const checkOut = startOfDay(new Date(booking.checkOutDate));

        if (target < checkIn || target > checkOut) continue;

        const isArrival = isSameDay(target, checkIn);
        const isDeparture = isSameDay(target, checkOut);

        for (const pet of booking.pets ?? []) {
            occupancy += 1;
            const movement = toMovement(booking, pet);
            if (isArrival) {
                arrivals.push(movement);
            } else if (isDeparture) {
                departures.push(movement);
            } else {
                staying.push(movement);
            }
        }
    }

    return { arrivals, departures, staying, occupancy };
}

export function buildMovementsByDay(
    weeks: Date[][],
    bookings: BookingModel[],
): Record<string, DayMovements> {
    const map: Record<string, DayMovements> = {};
    for (const week of weeks) {
        for (const day of week) {
            map[dayKey(day)] = movementsForDay(day, bookings);
        }
    }
    return map;
}

export function computeMonthStats(
    weeks: Date[][],
    movementsByDay: Record<string, DayMovements>,
    focusedMonth: Date,
    bookings: BookingModel[],
): MonthStats {
    const monthStart = startOfMonth(focusedMonth);
    const monthEnd = endOfMonth(focusedMonth);

    let arrivals = 0;
    let departures = 0;
    let peak = 0;

    for (const week of weeks) {
        for (const day of week) {
            const movements = movementsByDay[dayKey(day)];
            if (!movements) continue;
            peak = Math.max(peak, movements.occupancy);
            if (!isSameMonth(day, focusedMonth)) continue;
            arrivals += movements.arrivals.length;
            departures += movements.departures.length;
        }
    }

    const stays = bookings.filter((booking) => {
        const checkIn = new Date(booking.checkInDate);
        const checkOut = new Date(booking.checkOutDate);
        return checkIn <= monthEnd && checkOut >= monthStart;
    }).length;

    return { arrivals, departures, peak, stays };
}

export function shiftMonth(date: Date, delta: number): Date {
    const next = new Date(date);
    next.setDate(1);
    next.setMonth(next.getMonth() + delta);
    return next;
}
