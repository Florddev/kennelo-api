import { api } from "@workspace/common";

import { ActivityModel } from "../../models/activity.model";
import { ActivityDto } from "../../models/dtos/activity.dto";
import { CreateActivityInput } from "../../validators/create-activity.schema";

export async function createActivity(input: CreateActivityInput): Promise<ActivityModel> {
    const body: Record<string, unknown> = {
        name: input.name,
    };

    if (input.type) body.type = input.type;
    if (input.description) body.description = input.description;
    if (input.phone) body.phone = input.phone;
    if (input.email) body.email = input.email;
    if (input.website) body.website = input.website;
    if (input.siret) body.siret = input.siret;

    if (input.address?.line1 && input.address?.city && input.address?.country) {
        body.address = {
            line1: input.address.line1,
            line2: input.address.line2 || null,
            city: input.address.city,
            postal_code: input.address.postalCode,
            region: input.address.region || null,
            country: input.address.country,
        };
    }

    const response = await api.post<ActivityDto>("/activities", body);

    if (!response.data) {
        throw new Error("No data returned");
    }

    return ActivityModel.from(response.data);
}
