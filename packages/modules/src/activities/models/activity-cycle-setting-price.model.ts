import type { ActivityCycleSettingPriceDto } from "./dtos/activity-cycle-setting-price.dto";

export class ActivityCycleSettingPriceModel {
    private constructor(
        public readonly weekday: number,
        public readonly price: number,
    ) {}

    static from(dto: ActivityCycleSettingPriceDto): ActivityCycleSettingPriceModel {
        return new ActivityCycleSettingPriceModel(dto.weekday, parseFloat(dto.price));
    }
}
