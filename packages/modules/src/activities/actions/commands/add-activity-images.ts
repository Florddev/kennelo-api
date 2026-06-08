import { api } from "@workspace/common";
import type { ActivityImageDto } from "../../models/dtos/activity-image.dto";
import { ActivityImageModel } from "../../models/activity-image.model";

export async function addActivityImages(
    activityId: string,
    images: File[],
): Promise<ActivityImageModel[]> {
    const formData = new FormData();

    images.forEach((image) => {
        formData.append("images[]", image);
    });

    const response = await api.post<ActivityImageDto[]>(
        `/activities/${activityId}/images/bulk`,
        formData,
    );

    if (!response.data) {
        throw new Error("Failed to upload images");
    }

    return response.data.map(ActivityImageModel.from);
}
