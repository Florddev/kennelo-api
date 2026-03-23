import { api } from "@workspace/common";

import { AvailabilityDto } from "../../models/dtos/availability.dto";
import { AvailabilityModel } from "../../models/availability.model";

export async function getAvailabilities(
    establishmentId: string,
    month: string,
): Promise<AvailabilityModel[]> {
    const response = await api.get<AvailabilityDto[]>(
        `/establishments/${establishmentId}/availabilities`,
        { month },
    );

    if (!response.data) {
        return [];
    }

    return response.data.map(AvailabilityModel.from);
}

export async function getAvailabilitiesRange(
    establishmentId: string,
    startDate: string,
    endDate: string,
): Promise<AvailabilityModel[]> {
    const response = await api.get<AvailabilityDto[]>(
        `/establishments/${establishmentId}/availabilities/range`,
        { start_date: startDate, end_date: endDate },
    );

    if (!response.data) {
        return [];
    }

    return response.data.map(AvailabilityModel.from);
}
