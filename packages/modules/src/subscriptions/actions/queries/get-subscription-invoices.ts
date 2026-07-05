import { api } from "@workspace/common";
import type { SubscriptionInvoiceDto } from "../../models/dtos/subscription-invoice.dto";
import { SubscriptionInvoiceModel } from "../../models/subscription-invoice.model";

export async function getSubscriptionInvoices(
    activityId: string,
): Promise<SubscriptionInvoiceModel[]> {
    const response = await api.get<SubscriptionInvoiceDto[]>(
        `/activities/${activityId}/subscription/invoices`,
    );

    if (!response.data) {
        return [];
    }

    return response.data.map(SubscriptionInvoiceModel.from);
}
