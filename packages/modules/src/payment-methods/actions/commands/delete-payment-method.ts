import { api } from "@workspace/common";

export async function deletePaymentMethod(id: string): Promise<void> {
    await api.delete<void>(`/me/payment-methods/${id}`);
}
