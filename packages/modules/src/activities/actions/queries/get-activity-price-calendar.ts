import { api } from "@workspace/common";

export type PriceCalendar = Record<string, number | null>;

export async function getActivityPriceCalendar(
    activityId: string,
    from: string,
    to: string,
): Promise<PriceCalendar> {
    const response = await api.get<PriceCalendar>(`/activities/${activityId}/price-calendar`, {
        from,
        to,
    });

    return response.data ?? {};
}
