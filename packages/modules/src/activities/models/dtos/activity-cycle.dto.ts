import type { ActivityCycleClosedWeekDayDto } from "./activity-cycle-closed-week-day.dto";
import type { ActivityCycleSettingDto } from "./activity-cycle-setting.dto";

export type ActivityCycleDto = {
    id: string;
    activity_id: string;
    start_date: string | null;
    end_date: string | null;
    priority: number;
    is_active: boolean;
    settings?: ActivityCycleSettingDto[];
    closed_week_days?: ActivityCycleClosedWeekDayDto[];
    created_at: string;
    updated_at: string;
};
