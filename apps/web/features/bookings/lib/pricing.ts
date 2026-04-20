export const SERVICE_FEE_RATE = 0.1;

export function computeNightsBetween(from: Date, to: Date): number {
    const msPerDay = 1000 * 60 * 60 * 24;
    return Math.max(0, Math.round((to.getTime() - from.getTime()) / msPerDay));
}

export type PriceBreakdown = {
    nights: number;
    pricePerNight: number;
    subtotal: number;
    serviceFee: number;
    total: number;
};

export function computePriceBreakdown(pricePerNight: number, nights: number): PriceBreakdown {
    const subtotal = pricePerNight * nights;
    const serviceFee = Math.round(subtotal * SERVICE_FEE_RATE);
    return {
        nights,
        pricePerNight,
        subtotal,
        serviceFee,
        total: subtotal + serviceFee,
    };
}

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
