import { api } from "@workspace/common";
import type { PetReviewDto } from "../../models/dtos/pet-review.dto";
import { PetReviewModel } from "../../models/pet-review.model";

export async function getPetReviews(petId: string): Promise<PetReviewModel[]> {
    const response = await api.get<PetReviewDto[]>(`/pets/${petId}/reviews`);

    if (!response.data) {
        return [];
    }

    return response.data.map(PetReviewModel.from);
}
