import { ActivityCycleSettingPriceModel } from "./activity-cycle-setting-price.model";
import { AnimalTypeModel } from "./animal-type.model";
import type { ActivityCycleSettingDto } from "./dtos/activity-cycle-setting.dto";

export class ActivityCycleSettingModel {
    private constructor(
        public readonly id: string,
        public readonly animalType: AnimalTypeModel,
        public readonly maxCapacity: number,
        public readonly prices: ActivityCycleSettingPriceModel[],
        public readonly occupiedSpots: number,
        public readonly availableSpots: number,
    ) {}

    static from(dto: ActivityCycleSettingDto): ActivityCycleSettingModel {
        return new ActivityCycleSettingModel(
            dto.id,
            AnimalTypeModel.from(dto.animal_type),
            dto.max_capacity,
            (dto.prices ?? []).map(ActivityCycleSettingPriceModel.from),
            dto.occupied_spots,
            dto.available_spots,
        );
    }

    priceForWeekday(weekday: number): number | null {
        const match = this.prices.find((price) => price.weekday === weekday);

        return match ? match.price : null;
    }
}
