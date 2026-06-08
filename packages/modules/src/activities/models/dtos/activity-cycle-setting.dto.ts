import type { AnimalTypeDto } from "./animal-type.dto";

export type ActivityCycleSettingDto = {
    id: string;
    animal_type: AnimalTypeDto;
    max_capacity: number;
    price: string;
    sum_weekdays: number;
    week_days: number[];
    occupied_spots: number;
    available_spots: number;
};
