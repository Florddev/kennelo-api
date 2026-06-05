import { api } from "@workspace/common";

export async function deleteCapacity(establishmentId: string, capacityId: string): Promise<void> {
    await api.delete(`/establishments/${establishmentId}/capacities/${capacityId}`);
}
