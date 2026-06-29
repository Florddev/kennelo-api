import { api } from "@workspace/common";
import type { AnimalBreedDto } from "../../models/dtos/animal-breed.dto";
import { AnimalBreedModel } from "../../models/animal-breed.model";

export async function getAnimalBreeds(animalTypeId?: string): Promise<AnimalBreedModel[]> {
    const response = await api.get<AnimalBreedDto[]>(
        "/animal-breeds",
        animalTypeId ? { animal_type_id: animalTypeId } : undefined,
    );

    if (!response.data) {
        return [];
    }

    return response.data.map(AnimalBreedModel.from);
}
