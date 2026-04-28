import { api } from "@workspace/common";
import type { EstablishmentImageDto } from "../../models/dtos/establishment-image.dto";
import { EstablishmentImageModel } from "../../models/establishment-image.model";

export async function addEstablishmentImages(
    establishmentId: string,
    images: File[],
): Promise<EstablishmentImageModel[]> {
    const formData = new FormData();

    images.forEach((image) => {
        formData.append("images[]", image);
    });

    const response = await api.post<EstablishmentImageDto[]>(
        `/establishments/${establishmentId}/images/bulk`,
        formData,
    );

    if (!response.data) {
        throw new Error("Failed to upload images");
    }

    return response.data.map(EstablishmentImageModel.from);
}
