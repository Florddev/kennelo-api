import { api } from "@workspace/common";
import { PaymentMethodModel } from "../../models/payment-method.model";
import type { PaymentMethodDto } from "../../models/dtos/payment-method.dto";

export async function setDefaultPaymentMethod(id: string): Promise<PaymentMethodModel[]> {
    const response = await api.put<PaymentMethodDto[]>(`/me/payment-methods/${id}/default`, {});
    if (!response.data) {
        return [];
    }
    return response.data.map(PaymentMethodModel.from);
}
