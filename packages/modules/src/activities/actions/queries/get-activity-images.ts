import { api } from "@workspace/common";
import type { ActivityImageDto } from "../../models/dtos/activity-image.dto";
import { ActivityImageModel } from "../../models/activity-image.model";

export async function getActivityImages(activityId: string): Promise<ActivityImageModel[]> {
    const response = await api.get<ActivityImageDto[]>(`/activities/${activityId}/images`);
    if (!response.data) return [];
    return response.data.map(ActivityImageModel.from);
}
