import { api } from "@workspace/common";

import type { GoogleCandidate } from "../../models/google-candidate.type";

export async function searchActivityGoogle(activityId: string): Promise<GoogleCandidate | null> {
    const response = await api.post<GoogleCandidate | null>(
        `/admin/activities/${activityId}/google/search`,
    );

    return response.data;
}
