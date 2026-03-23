import { api } from "@workspace/common";

import { AvailabilityDto } from "../../models/dtos/availability.dto";
import { AvailabilityModel } from "../../models/availability.model";
import type { BulkAvailabilityInput } from "../../validators/availability.schema";

export async function bulkAvailability(
    establishmentId: string,
    input: BulkAvailabilityInput,
): Promise<AvailabilityModel[]> {
    const body = {
        dates: input.dates,
        status: input.status,
        note: input.note || null,
    };

    const response = await api.post<AvailabilityDto[]>(
        `/establishments/${establishmentId}/availabilities/bulk`,
        body,
    );

    if (!response.data) {
        return [];
    }

    return response.data.map(AvailabilityModel.from);
}
