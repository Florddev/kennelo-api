import { api } from "@workspace/common";

import { AnimalTypePriceRangeModel } from "../../models/animal-type-price-range.model";
import type { AnimalTypePriceRangeDto } from "../../models/dtos/animal-type-price-range.dto";

export async function getActivityAnimalTypePrices(
    activityId: string,
): Promise<AnimalTypePriceRangeModel[]> {
    const response = await api.get<AnimalTypePriceRangeDto[]>(
        `/activities/${activityId}/animal-type-prices`,
    );

    if (!response.data) {
        return [];
    }

    return response.data.map(AnimalTypePriceRangeModel.from);
}
