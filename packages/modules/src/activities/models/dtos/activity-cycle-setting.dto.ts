import type { ActivityCycleSettingPriceDto } from "./activity-cycle-setting-price.dto";
import type { AnimalTypeDto } from "./animal-type.dto";

export type ActivityCycleSettingDto = {
    id: string;
    animal_type: AnimalTypeDto;
    max_capacity: number;
    prices: ActivityCycleSettingPriceDto[];
    occupied_spots: number;
    available_spots: number;
};
