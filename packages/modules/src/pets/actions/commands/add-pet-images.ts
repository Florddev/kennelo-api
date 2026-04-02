import { api } from "@workspace/common";
import type { PetImageDto } from "../../models/dtos/pet-image.dto";
import { PetImageModel } from "../../models/pet-image.model";

export async function addPetImages(petId: string, images: File[]): Promise<PetImageModel[]> {
    const formData = new FormData();

    images.forEach((image) => {
        formData.append("images[]", image);
    });

    const response = await api.post<PetImageDto[]>(`/pets/${petId}/images/bulk`, formData);

    if (!response.data) {
        throw new Error("Failed to upload images");
    }

    return response.data.map(PetImageModel.from);
}
