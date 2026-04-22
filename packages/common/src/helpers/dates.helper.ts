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
    return (
        a.getFullYear() === b.getFullYear() &&
        a.getMonth() === b.getMonth() &&
        a.getDate() === b.getDate()
    );
}
