import { api } from "@workspace/common";

export async function deleteEstablishmentImage(
    establishmentId: string,
    imageId: string,
): Promise<void> {
    await api.delete(`/establishments/${establishmentId}/images/${imageId}`);
}
