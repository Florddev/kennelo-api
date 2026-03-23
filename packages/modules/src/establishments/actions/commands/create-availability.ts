import { api } from "@workspace/common";

import { AvailabilityDto } from "../../models/dtos/availability.dto";
import { AvailabilityModel } from "../../models/availability.model";
import type { CreateAvailabilityInput } from "../../validators/availability.schema";

export async function createAvailability(
    establishmentId: string,
    input: CreateAvailabilityInput,
): Promise<AvailabilityModel[]> {
    const body = {
        start_date: input.startDate,
        end_date: input.endDate,
        status: input.status,
        note: input.note || null,
    };

    const response = await api.post<AvailabilityDto[]>(
        `/establishments/${establishmentId}/availabilities`,
        body,
    );

    if (!response.data) {
        return [];
    }

    return response.data.map(AvailabilityModel.from);
}
