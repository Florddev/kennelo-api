import { api } from "@workspace/common";
import { PaymentMethodModel } from "../../models/payment-method.model";
import type { PaymentMethodDto } from "../../models/dtos/payment-method.dto";

export async function getPaymentMethods(): Promise<PaymentMethodModel[]> {
    const response = await api.get<PaymentMethodDto[]>("/me/payment-methods");
    if (!response.data) {
        return [];
    }
    return response.data.map(PaymentMethodModel.from);
}
