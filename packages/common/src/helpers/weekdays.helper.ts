export const WEEKDAY_VALUES = {
    monday: 1,
    tuesday: 2,
    wednesday: 4,
    thursday: 8,
    friday: 16,
    saturday: 32,
    sunday: 64,
} as const;

export type WeekDayKey = keyof typeof WEEKDAY_VALUES;

export const WEEKDAY_KEYS: WeekDayKey[] = [
    "monday",
    "tuesday",
    "wednesday",
    "thursday",
    "friday",
    "saturday",
    "sunday",
];

export const NO_WEEKDAYS = 0;

export const ALL_WEEKDAYS = WEEKDAY_KEYS.reduce((mask, key) => mask | WEEKDAY_VALUES[key], 0);

export function weekdaysToMask(days: number[]): number {
    return days.reduce((mask, day) => mask | day, NO_WEEKDAYS);
}

export function weekdaysFromMask(mask: number): number[] {
    return WEEKDAY_KEYS.map((key) => WEEKDAY_VALUES[key]).filter(
        (value) => (mask & value) === value,
    );
}

export function weekdayMaskContains(mask: number, day: number): boolean {
    return (mask & day) === day;
}

export function weekdayKeysFromMask(mask: number): WeekDayKey[] {
    return WEEKDAY_KEYS.filter((key) => weekdayMaskContains(mask, WEEKDAY_VALUES[key]));
}
