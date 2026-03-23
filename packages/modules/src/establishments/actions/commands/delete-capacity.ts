import { api } from "@workspace/common";

export async function deleteCapacity(establishmentId: string, capacityId: number): Promise<void> {
    await api.delete(`/establishments/${establishmentId}/capacities/${capacityId}`);
}
