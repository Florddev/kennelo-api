import { api } from "@workspace/common";
import type { ActivityImageDto } from "../../models/dtos/activity-image.dto";
import { ActivityImageModel } from "../../models/activity-image.model";

export async function addActivityImage(
    activityId: string,
    image: File,
): Promise<ActivityImageModel> {
    const formData = new FormData();
    formData.append("image", image);
    const response = await api.post<ActivityImageDto>(`/activities/${activityId}/images`, formData);
    if (!response.data) throw new Error("Failed to upload image");
    return ActivityImageModel.from(response.data);
}
