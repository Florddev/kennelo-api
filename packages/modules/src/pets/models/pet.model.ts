import type { PetDto } from "./dtos/pet.dto";
import { AnimalTypeModel } from "./animal-type.model";
import { PetAttributeModel } from "./pet-attribute.model";
import { PetImageModel } from "./pet-image.model";
import type { PetAttributeCategory } from "../types/attributes-categories.type";

type PetAttributeByCategory = {
    category: PetAttributeCategory;
    attributes: PetAttributeModel[];
};

export class PetModel {
    private constructor(
        public readonly id: string,
        public readonly userId: string,
        public readonly animalTypeId: number,
        public readonly name: string,
        public readonly breed: string | null,
        public readonly birthDate: string | null,
        public readonly sex: "male" | "female" | "unknown" | null,
        public readonly weight: number | null,
        public readonly isSterilized: boolean | null,
        public readonly hasMicrochip: boolean,
        public readonly microchipNumber: string | null,
        public readonly adoptionDate: string | null,
        public readonly about: string | null,
        public readonly healthNotes: string | null,
        public readonly avatarUrl: string | null,
        public readonly animalType: AnimalTypeModel | null,
        public readonly attributes: PetAttributeModel[] | null,
        public readonly images: PetImageModel[],
        public readonly createdAt: string,
        public readonly updatedAt: string,
    ) {}

    static from(dto: PetDto): PetModel {
        return new PetModel(
            dto.id,
            dto.user_id,
            dto.animal_type_id,
            dto.name,
            dto.breed,
            dto.birth_date,
            dto.sex,
            dto.weight,
            dto.is_sterilized,
            dto.has_microchip,
            dto.microchip_number,
            dto.adoption_date,
            dto.about,
            dto.health_notes,
            dto.avatar_url ?? null,
            dto.animal_type ? AnimalTypeModel.from(dto.animal_type) : null,
            dto.attributes ? dto.attributes.map(PetAttributeModel.from) : null,
            dto.images ? dto.images.map(PetImageModel.from) : [],
            dto.created_at,
            dto.updated_at,
        );
    }

    getAvatarUrl(): string | undefined {
        if (this.avatarUrl) {
            return this.avatarUrl;
        }
        if (this.images[0] != null) {
            return this.images[0].url;
        }
        return undefined;
    }

    groupAttributesByCategory(
        categories: PetAttributeCategory | PetAttributeCategory[] | null = null,
    ): PetAttributeByCategory[] {
        const attrs = this.attributes;
        if (!attrs) return [];

        const requestedOrder =
            categories === null ? null : Array.isArray(categories) ? categories : [categories];

        const selectedCategories =
            categories === null
                ? null
                : new Set(Array.isArray(categories) ? categories : [categories]);

        const grouped = new Map<PetAttributeCategory, PetAttributeModel[]>();
        attrs.forEach((attr) => {
            const category = attr.attributeDefinition?.category ?? "info";
            if (selectedCategories && !selectedCategories.has(category)) {
                return;
            }
            if (!grouped.has(category)) {
                grouped.set(category, []);
            }
            grouped.get(category)!.push(attr);
        });

        if (requestedOrder) {
            const seen = new Set<PetAttributeCategory>();

            return requestedOrder
                .filter((category) => {
                    if (seen.has(category)) return false;
                    seen.add(category);
                    return grouped.has(category);
                })
                .map((category) => ({
                    category,
                    attributes: grouped.get(category) ?? [],
                }));
        }

        return Array.from(grouped.entries()).map(([category, attributes]) => ({
            category,
            attributes,
        }));
    }
}
