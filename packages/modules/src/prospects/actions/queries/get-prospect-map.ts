import { api } from "@workspace/common";

import type { ProspectFeatureCollection } from "../../models/prospect-map.type";
import type { ProspectStatusValue } from "../../types/prospect-status.type";

const EMPTY: ProspectFeatureCollection = { type: "FeatureCollection", features: [] };

export async function getProspectMap(input?: {
    status?: ProspectStatusValue;
    department?: string;
    registered?: boolean;
}): Promise<ProspectFeatureCollection> {
    const params: Record<string, string | number | boolean> = {};

    if (input?.status) params.status = input.status;
    if (input?.department) params.department = input.department;
    if (input?.registered != null) params.registered = input.registered;

    const response = await api.get<ProspectFeatureCollection>(
        "/admin/prospects/map",
        Object.keys(params).length > 0 ? params : undefined,
    );

    return response.data ?? EMPTY;
}
