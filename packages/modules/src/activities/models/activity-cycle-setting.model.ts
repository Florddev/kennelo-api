import { weekdaysFromMask } from "@workspace/common";

import { AnimalTypeModel } from "./animal-type.model";
import type { ActivityCycleSettingDto } from "./dtos/activity-cycle-setting.dto";

export class ActivityCycleSettingModel {
    private constructor(
        public readonly id: string,
        public readonly animalType: AnimalTypeModel,
        public readonly maxCapacity: number,
        public readonly price: number,
        public readonly sumWeekdays: number,
        public readonly weekDays: number[],
        public readonly occupiedSpots: number,
        public readonly availableSpots: number,
    ) {}

    static from(dto: ActivityCycleSettingDto): ActivityCycleSettingModel {
        return new ActivityCycleSettingModel(
            dto.id,
            AnimalTypeModel.from(dto.animal_type),
            dto.max_capacity,
            parseFloat(dto.price),
            dto.sum_weekdays,
            dto.week_days ?? weekdaysFromMask(dto.sum_weekdays),
            dto.occupied_spots,
            dto.available_spots,
        );
    }
}
