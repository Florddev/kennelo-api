import type { AvailabilityModel } from "@workspace/modules/establishments";
import { fromApiDate, isSameDay } from "@workspace/common";

export function buildAvailableDateSet(availabilities: AvailabilityModel[]): Set<string> {
    return new Set(
        availabilities
            .filter((availability) => availability.status === "open")
            .map((availability) => availability.date),
    );
}

export function isDateAvailable(date: Date, availabilities: AvailabilityModel[]): boolean {
    return availabilities.some(
        (availability) =>
            availability.status === "open" && isSameDay(fromApiDate(availability.date), date),
    );
}

export function isDateDisabledForBooking(
    date: Date,
    availabilities: AvailabilityModel[],
    today: Date,
): boolean {
    if (date < today) return true;
    if (availabilities.length === 0) return false;
    return !isDateAvailable(date, availabilities);
}
