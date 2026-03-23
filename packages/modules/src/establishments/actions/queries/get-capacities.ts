import { api } from "@workspace/common";

import { CapacityDto } from "../../models/dtos/capacity.dto";
import { CapacityModel } from "../../models/capacity.model";

export async function getCapacities(
    establishmentId: string,
    date?: string,
): Promise<CapacityModel[]> {
    const params = date ? { date } : undefined;
    const response = await api.get<CapacityDto[]>(
        `/establishments/${establishmentId}/capacities`,
        params,
    );

    if (!response.data) {
        return [];
    }

    return response.data.map(CapacityModel.from);
}
