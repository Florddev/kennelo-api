import { api } from "@workspace/common";

import { DashboardDto } from "../../models/dtos/dashboard.dto";
import { DashboardModel } from "../../models/dashboard.model";

export async function getEstablishmentDashboard(establishmentId: string): Promise<DashboardModel> {
    const response = await api.get<DashboardDto>(`/establishments/${establishmentId}/dashboard`);

    if (!response.data) {
        throw new Error("No data returned");
    }

    return DashboardModel.from(response.data);
}
