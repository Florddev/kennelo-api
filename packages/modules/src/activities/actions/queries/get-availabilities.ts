import { api } from "@workspace/common";

import { AvailabilityDto } from "../../models/dtos/availability.dto";
import { AvailabilityModel } from "../../models/availability.model";

export async function getAvailabilities(
    activityId: string,
    month: string,
): Promise<AvailabilityModel[]> {
    const response = await api.get<AvailabilityDto[]>(`/activities/${activityId}/availabilities`, {
        month,
    });

    if (!response.data) {
        return [];
    }

    return response.data.map(AvailabilityModel.from);
}

export async function getAvailabilitiesRange(
    activityId: string,
    startDate: string,
    endDate: string,
): Promise<AvailabilityModel[]> {
    const response = await api.get<AvailabilityDto[]>(
        `/activities/${activityId}/availabilities/range`,
        { start_date: startDate, end_date: endDate },
    );

    if (!response.data) {
        return [];
    }

    return response.data.map(AvailabilityModel.from);
}
