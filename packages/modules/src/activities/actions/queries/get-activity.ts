import { api } from "@workspace/common";
import { ActivityModel } from "../../models/activity.model";
import { ActivityDto } from "../../models/dtos/activity.dto";

export async function getActivity(id: string): Promise<ActivityModel> {
    const response = await api.get<ActivityDto>(`/activities/${id}`);

    if (!response.data) {
        throw new Error("No data returned");
    }

    return ActivityModel.from(response.data);
}
