import { api } from "@workspace/common";
import type { EstablishmentImageDto } from "../../models/dtos/establishment-image.dto";
import { EstablishmentImageModel } from "../../models/establishment-image.model";

export async function addEstablishmentImage(
    establishmentId: string,
    image: File,
): Promise<EstablishmentImageModel> {
    const formData = new FormData();
    formData.append("image", image);
    const response = await api.post<EstablishmentImageDto>(
        `/establishments/${establishmentId}/images`,
        formData,
    );
    if (!response.data) throw new Error("Failed to upload image");
    return EstablishmentImageModel.from(response.data);
}
