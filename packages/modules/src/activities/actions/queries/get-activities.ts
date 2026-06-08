import { api } from "@workspace/common";
import { ActivityModel } from "../../models/activity.model";
import { ActivityDto } from "../../models/dtos/activity.dto";

export async function getActivities(): Promise<ActivityModel[]> {
    const response = await api.get<ActivityDto[]>("/activities", {
        per_page: 100,
    });

    if (!response.data) {
        throw new Error("No data returned");
    }

    return response.data.map(ActivityModel.from);
}
