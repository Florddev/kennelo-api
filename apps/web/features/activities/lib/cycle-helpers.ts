import {
    ALL_WEEKDAYS,
    toApiDate,
    weekdayMaskContains,
    weekdaysFromMask,
    WEEKDAY_VALUES,
} from "@workspace/common";
import type { ActivityCycleModel, UpsertCycleSettingsInput } from "@workspace/modules/activities";

export type CycleMatrixRow = {
    animalTypeId: string;
    maxCapacity: number;
    prices: Record<number, number>;
};

export type CycleMatrixValue = {
    rows: CycleMatrixRow[];
    openMask: number;
};

export const WEEKDAY_ORDER: number[] = [
    WEEKDAY_VALUES.monday,
    WEEKDAY_VALUES.tuesday,
    WEEKDAY_VALUES.wednesday,
    WEEKDAY_VALUES.thursday,
    WEEKDAY_VALUES.friday,
    WEEKDAY_VALUES.saturday,
    WEEKDAY_VALUES.sunday,
];

export const WEEKDAY_LABEL_KEYS: Record<number, string> = {
    [WEEKDAY_VALUES.monday]: "monday",
    [WEEKDAY_VALUES.tuesday]: "tuesday",
    [WEEKDAY_VALUES.wednesday]: "wednesday",
    [WEEKDAY_VALUES.thursday]: "thursday",
    [WEEKDAY_VALUES.friday]: "friday",
    [WEEKDAY_VALUES.saturday]: "saturday",
    [WEEKDAY_VALUES.sunday]: "sunday",
};

const JS_DAY_TO_WEEKDAY: Record<number, number> = {
    0: WEEKDAY_VALUES.sunday,
    1: WEEKDAY_VALUES.monday,
    2: WEEKDAY_VALUES.tuesday,
    3: WEEKDAY_VALUES.wednesday,
    4: WEEKDAY_VALUES.thursday,
    5: WEEKDAY_VALUES.friday,
    6: WEEKDAY_VALUES.saturday,
};

export function weekdayValueForDate(date: Date): number {
    return JS_DAY_TO_WEEKDAY[date.getDay()] ?? WEEKDAY_VALUES.sunday;
}

export function isDefaultCycle(cycle: ActivityCycleModel): boolean {
    return cycle.startDate === null && cycle.endDate === null;
}

export function closedMaskForCycle(cycle: ActivityCycleModel): number {
    return cycle.closedWeekDays.reduce((mask, closed) => mask | closed.sumWeekdays, 0);
}

export function cycleCoversDate(cycle: ActivityCycleModel, dateStr: string): boolean {
    const startOk = cycle.startDate === null || cycle.startDate <= dateStr;
    const endOk = cycle.endDate === null || cycle.endDate >= dateStr;

    return startOk && endOk;
}

export function resolveCycleForDate(
    cycles: ActivityCycleModel[],
    date: Date,
): ActivityCycleModel | null {
    const dateStr = toApiDate(date);
    const matching = cycles.filter((cycle) => cycle.isActive && cycleCoversDate(cycle, dateStr));

    if (matching.length === 0) {
        return null;
    }

    return matching.reduce((best, cycle) => (cycle.priority > best.priority ? cycle : best));
}

export function isClosedOnDate(cycle: ActivityCycleModel, date: Date): boolean {
    return weekdayMaskContains(closedMaskForCycle(cycle), weekdayValueForDate(date));
}

export function priceForDate(
    cycles: ActivityCycleModel[],
    animalTypeId: string,
    date: Date,
): number | null {
    const cycle = resolveCycleForDate(cycles, date);

    if (cycle === null || isClosedOnDate(cycle, date)) {
        return null;
    }

    const setting = cycle.settings.find((item) => item.animalType.id === animalTypeId);

    if (!setting) {
        return null;
    }

    return setting.priceForWeekday(weekdayValueForDate(date));
}

export function cycleToMatrix(cycle: ActivityCycleModel): CycleMatrixValue {
    const closed = closedMaskForCycle(cycle);

    return {
        openMask: ALL_WEEKDAYS & ~closed,
        rows: cycle.settings.map((setting) => ({
            animalTypeId: setting.animalType.id,
            maxCapacity: setting.maxCapacity,
            prices: Object.fromEntries(setting.prices.map((price) => [price.weekday, price.price])),
        })),
    };
}

export function emptyMatrix(): CycleMatrixValue {
    return { rows: [], openMask: ALL_WEEKDAYS };
}

export function matrixToSettingsInput(value: CycleMatrixValue): UpsertCycleSettingsInput {
    const openWeekdays = weekdaysFromMask(value.openMask);

    return {
        settings: value.rows.map((row) => ({
            animalTypeId: row.animalTypeId,
            maxCapacity: row.maxCapacity,
            prices: openWeekdays.map((weekday) => ({
                weekday,
                price: row.prices[weekday] ?? 0,
            })),
        })),
    };
}

export function matrixClosedMask(value: CycleMatrixValue): number {
    return ALL_WEEKDAYS & ~value.openMask;
}

export function cyclesOverlap(a: ActivityCycleModel, b: ActivityCycleModel): boolean {
    const aStartsBeforeBEnds =
        a.startDate === null || b.endDate === null || a.startDate <= b.endDate;
    const aEndsAfterBStarts =
        a.endDate === null || b.startDate === null || a.endDate >= b.startDate;

    return aStartsBeforeBEnds && aEndsAfterBStarts;
}
