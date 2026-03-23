import { api } from "@workspace/common";

import { AvailabilityDto } from "../../models/dtos/availability.dto";
import { AvailabilityModel } from "../../models/availability.model";
import type { UpdateAvailabilityInput } from "../../validators/availability.schema";

export async function updateAvailability(
    establishmentId: string,
    availabilityId: number,
    input: UpdateAvailabilityInput,
): Promise<AvailabilityModel> {
    const body: Record<string, unknown> = {};

    if (input.status !== undefined) body.status = input.status;
    if (input.note !== undefined) body.note = input.note || null;

    const response = await api.put<AvailabilityDto>(
        `/establishments/${establishmentId}/availabilities/${availabilityId}`,
        body,
    );

    if (!response.data) {
        throw new Error("No data returned");
    }

    return AvailabilityModel.from(response.data);
}
