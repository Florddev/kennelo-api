import { weekdaysFromMask } from "@workspace/common";

import type { ActivityCycleClosedWeekDayDto } from "./dtos/activity-cycle-closed-week-day.dto";

export class ActivityCycleClosedWeekDayModel {
    private constructor(
        public readonly id: string,
        public readonly sumWeekdays: number,
        public readonly weekDays: number[],
    ) {}

    static from(dto: ActivityCycleClosedWeekDayDto): ActivityCycleClosedWeekDayModel {
        return new ActivityCycleClosedWeekDayModel(
            dto.id,
            dto.sum_weekdays,
            dto.week_days ?? weekdaysFromMask(dto.sum_weekdays),
        );
    }
}
