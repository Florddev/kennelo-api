import { api } from "@workspace/common";

import { ActivityDto } from "../../models/dtos/activity.dto";
import { ActivityModel } from "../../models/activity.model";
import { UpdateActivityInput } from "../../validators/update-activity.schema";

export async function updateActivity(
    id: string,
    input: UpdateActivityInput,
): Promise<ActivityModel> {
    const body: Record<string, unknown> = {
        name: input.name,
    };

    if (input.description) body.description = input.description;
    if (input.phone) body.phone = input.phone;
    if (input.email) body.email = input.email;
    if (input.website) body.website = input.website;
    if (input.siret) body.siret = input.siret;

    if (input.address) {
        body.address = {
            line1: input.address.line1,
            line2: input.address.line2 || null,
            city: input.address.city,
            postal_code: input.address.postalCode,
            region: input.address.region || null,
            country: input.address.country,
            latitude: input.address.latitude ?? null,
            longitude: input.address.longitude ?? null,
        };
    }

    const response = await api.put<ActivityDto>(`/activities/${id}`, body);

    if (!response.data) {
        throw new Error("No data returned");
    }

    return ActivityModel.from(response.data);
}
