import { api } from "@workspace/common";

export async function deleteAvailability(
    establishmentId: string,
    availabilityId: number,
): Promise<void> {
    await api.delete(`/establishments/${establishmentId}/availabilities/${availabilityId}`);
}
