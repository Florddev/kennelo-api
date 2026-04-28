export function toApiDate(date: Date): string {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, "0");
    const day = String(date.getDate()).padStart(2, "0");
    return `${year}-${month}-${day}`;
}

export function fromApiDate(value: string): Date {
    const [year, month, day] = value.split("-").map(Number);
    return new Date(year!, (month ?? 1) - 1, day ?? 1);
}

export function computeNights(from: Date | undefined, to: Date | undefined): number {
    if (!from || !to) return 0;
    const msPerDay = 1000 * 60 * 60 * 24;
    return Math.max(0, Math.round((to.getTime() - from.getTime()) / msPerDay));
}

export function formatDay(date: Date, locale: string): string {
    return new Intl.DateTimeFormat(locale, { day: "numeric", month: "short" }).format(date);
}

export function formatDateRange(from: Date, to: Date, locale: string): string {
    return `${formatDay(from, locale)} - ${formatDay(to, locale)}`;
}

export function isSameDay(a: Date, b: Date): boolean {
    if (isNaN(a.getTime()) || isNaN(b.getTime())) return false;

    return (
        a.getFullYear() === b.getFullYear() &&
        a.getMonth() === b.getMonth() &&
        a.getDate() === b.getDate()
    );
}

/**
 * @example "14:30" ou "19/11"
 */
export function formatTime(dateStr: string | null, locales?: Intl.LocalesArgument): string {
    if (!dateStr) return "";
    const date = new Date(dateStr);
    const now = new Date();
    const isToday =
        date.getDate() === now.getDate() &&
        date.getMonth() === now.getMonth() &&
        date.getFullYear() === now.getFullYear();

    if (isToday) {
        return date.toLocaleTimeString(locales ?? [], { hour: "2-digit", minute: "2-digit" });
    }
    return date.toLocaleDateString(locales ?? [], { day: "2-digit", month: "2-digit" });
}

/**
 * @example "14:30"
 */
export function formatTimeOnly(dateStr: string | null, locales?: Intl.LocalesArgument): string {
    if (!dateStr) return "";
    const date = new Date(dateStr);
    if (isNaN(date.getTime())) return "";
    return date.toLocaleTimeString(locales ?? [], { hour: "2-digit", minute: "2-digit" });
}

/**
 * @example "15/03/2024 14:30"
 */
export function formatDateTime(dateStr: string | null, locales?: Intl.LocalesArgument): string {
    if (!dateStr) return "";
    const date = new Date(dateStr);
    if (isNaN(date.getTime())) return "";
    return date.toLocaleString(locales ?? [], {
        day: "2-digit",
        month: "2-digit",
        year: "numeric",
        hour: "2-digit",
        minute: "2-digit",
    });
}

/**
 * @example "aujourd'hui" ou "hier" ou "mercredi" ou "8 mars 2024"
 */
export function formatDateAdaptive(dateStr: string | null, locales?: Intl.LocalesArgument): string {
    if (!dateStr) return "";
    const date = new Date(dateStr);
    const now = new Date();
    const toMidnight = (d: Date) => new Date(d.getFullYear(), d.getMonth(), d.getDate()).getTime();
    const diffDays = Math.round((toMidnight(now) - toMidnight(date)) / 86_400_000);
    const locale = locales as string | string[] | undefined;

    if (diffDays === 0) {
        return new Intl.RelativeTimeFormat(locale, { numeric: "auto" }).format(0, "day");
    }
    if (diffDays === 1) {
        return new Intl.RelativeTimeFormat(locale, { numeric: "auto" }).format(-1, "day");
    }
    if (diffDays <= 5) {
        return new Intl.DateTimeFormat(locale, { weekday: "long" }).format(date);
    }
    return new Intl.DateTimeFormat(locale, {
        day: "numeric",
        month: "short",
        year: "numeric",
    }).format(date);
}

function formatDateRangeGeneric(
    startDateStr: string | null,
    endDateStr: string | null,
    options: Intl.DateTimeFormatOptions,
    locales?: Intl.LocalesArgument,
): string {
    if (!startDateStr || !endDateStr) return "";
    const startDate = new Date(startDateStr);
    const endDate = new Date(endDateStr);
    if (isNaN(startDate.getTime()) || isNaN(endDate.getTime())) return "";
    const from = startDate <= endDate ? startDate : endDate;
    const to = startDate <= endDate ? endDate : startDate;
    const formatter = new Intl.DateTimeFormat(locales ?? [], options);
    return typeof formatter.formatRange === "function"
        ? formatter.formatRange(from, to)
        : `${formatter.format(from)} - ${formatter.format(to)}`;
}

/**
 * @example "26 – 28 sept"
 */
export function formatDateRangeShort(
    startDateStr: string | null,
    endDateStr: string | null,
    locales?: Intl.LocalesArgument,
): string {
    return formatDateRangeGeneric(
        startDateStr,
        endDateStr,
        { day: "numeric", month: "short" },
        locales,
    );
}

/**
 * @example "15 – 20 mars 2024"
 */
export function formatDateRangeCompact(
    startDateStr: string | null,
    endDateStr: string | null,
    locales?: Intl.LocalesArgument,
): string {
    return formatDateRangeGeneric(
        startDateStr,
        endDateStr,
        { day: "numeric", month: "short", year: "numeric" },
        locales,
    );
}

export function isWithinOneMonthAfter(dateStr: string): boolean {
    const date = new Date(dateStr);
    const limit = new Date(date);
    limit.setMonth(limit.getMonth() + 1);
    return new Date() <= limit;
}
