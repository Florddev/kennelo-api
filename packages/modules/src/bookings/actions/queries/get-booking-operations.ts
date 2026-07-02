import { api } from "@workspace/common";

import { FinancialOperationModel } from "../../models/financial-operation.model";
import type { FinancialOperationDto } from "../../models/dtos/financial-operation.dto";

export async function getBookingOperations(
    activityId: string,
    bookingId: string,
): Promise<FinancialOperationModel[]> {
    const response = await api.get<FinancialOperationDto[]>(
        `/activities/${activityId}/bookings/${bookingId}/operations`,
    );

    if (!response.data) {
        return [];
    }

    return response.data.map(FinancialOperationModel.from);
}
