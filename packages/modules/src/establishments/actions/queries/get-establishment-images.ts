import { api } from "@workspace/common";
import type { EstablishmentImageDto } from "../../models/dtos/establishment-image.dto";
import { EstablishmentImageModel } from "../../models/establishment-image.model";

export async function getEstablishmentImages(
    establishmentId: string,
): Promise<EstablishmentImageModel[]> {
    const response = await api.get<EstablishmentImageDto[]>(
        `/establishments/${establishmentId}/images`,
    );
    if (!response.data) return [];
    return response.data.map(EstablishmentImageModel.from);
}
