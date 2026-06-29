import { ActivityCycleClosedWeekDayModel } from "./activity-cycle-closed-week-day.model";
import { ActivityCycleSettingModel } from "./activity-cycle-setting.model";
import type { ActivityCycleDto } from "./dtos/activity-cycle.dto";

export class ActivityCycleModel {
    private constructor(
        public readonly id: string,
        public readonly activityId: string,
        public readonly startDate: string | null,
        public readonly endDate: string | null,
        public readonly priority: number,
        public readonly isActive: boolean,
        public readonly color: string | null,
        public readonly settings: ActivityCycleSettingModel[],
        public readonly closedWeekDays: ActivityCycleClosedWeekDayModel[],
        public readonly createdAt: string,
        public readonly updatedAt: string,
    ) {}

    static from(dto: ActivityCycleDto): ActivityCycleModel {
        return new ActivityCycleModel(
            dto.id,
            dto.activity_id,
            dto.start_date,
            dto.end_date,
            dto.priority,
            dto.is_active,
            dto.color,
            dto.settings ? dto.settings.map(ActivityCycleSettingModel.from) : [],
            dto.closed_week_days
                ? dto.closed_week_days.map(ActivityCycleClosedWeekDayModel.from)
                : [],
            dto.created_at,
            dto.updated_at,
        );
    }
}
