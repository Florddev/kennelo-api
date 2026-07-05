import { api } from "@workspace/common";

import type { ProfessionalDto } from "../../models/dtos/professional.dto";
import { ProfessionalModel } from "../../models/professional.model";

export async function linkActivityGoogle(
    activityId: string,
    input: {
        placeId: string;
        rating?: number | null;
        reviewsCount?: number | null;
        mapsUrl?: string | null;
    },
): Promise<ProfessionalModel | null> {
    const response = await api.post<ProfessionalDto>(`/admin/activities/${activityId}/google`, {
        google_place_id: input.placeId,
        google_rating: input.rating ?? undefined,
        google_reviews_count: input.reviewsCount ?? undefined,
        google_maps_url: input.mapsUrl ?? undefined,
    });

    if (!response.data) return null;
    return ProfessionalModel.from(response.data);
}
