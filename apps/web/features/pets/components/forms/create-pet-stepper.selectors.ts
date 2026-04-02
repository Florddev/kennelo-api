import {
    type AnimalTypeModel,
    type AttributeDefinitionModel,
    type PetAttributeCategory,
} from "@workspace/modules/pets";

export function findSelectedAnimalType(
    animalTypes: AnimalTypeModel[],
    selectedAnimalTypeValue: string | null,
): AnimalTypeModel | null {
    return (
        animalTypes.find((animalType) => String(animalType.id) === selectedAnimalTypeValue) ?? null
    );
}

export function buildCategoryDefinitions(
    categoryOrder: PetAttributeCategory[],
    selectedAnimalAttributes: AttributeDefinitionModel[],
) {
    return categoryOrder.map((category) => ({
        category,
        definitions: selectedAnimalAttributes.filter(
            (definition) => definition.category === category,
        ),
    }));
}
