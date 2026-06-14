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

    minPrice(): number {
        if (this.prices.length === 0) {
            return 0;
        }

        return this.prices.reduce(
            (min, price) => (price.price < min ? price.price : min),
            this.prices[0]!.price,
        );
    }

    averagePrice(): number {
        if (this.prices.length === 0) {
            return 0;
        }

        const total = this.prices.reduce((sum, price) => sum + price.price, 0);

        return total / this.prices.length;
    }
}
