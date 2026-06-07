import { api } from "@workspace/common";
import type { ActivityDto } from "../../models/dtos/activity.dto";
import { ActivityModel } from "../../models/activity.model";

export async function uploadActivityAvatar(
    activityId: string,
    avatar: File,
): Promise<ActivityModel> {
    const formData = new FormData();
    formData.append("avatar", avatar);
    const response = await api.post<ActivityDto>(`/activities/${activityId}/avatar`, formData);
    if (!response.data) throw new Error("Failed to upload avatar");
    return ActivityModel.from(response.data);
}
