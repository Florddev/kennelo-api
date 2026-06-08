import { api } from "@workspace/common";

import { DashboardDto } from "../../models/dtos/dashboard.dto";
import { DashboardModel } from "../../models/dashboard.model";

export async function getActivityDashboard(activityId: string): Promise<DashboardModel> {
    const response = await api.get<DashboardDto>(`/activities/${activityId}/dashboard`);

    if (!response.data) {
        throw new Error("No data returned");
    }

    return DashboardModel.from(response.data);
}
