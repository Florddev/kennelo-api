export enum PetAttributeCategoryEnum {
    INFO = "info",
    BEHAVIOR = "behavior",
    SOCIAL = "social",
    HYGIENE = "hygiene",
    CARE = "care",
    HEALTH = "health",
    HABITAT = "habitat",
    DIET = "diet",
}

export type PetAttributeCategory = `${PetAttributeCategoryEnum}`;

export const petAttributeCategoryOrder: PetAttributeCategory[] = [
    PetAttributeCategoryEnum.INFO,
    PetAttributeCategoryEnum.BEHAVIOR,
    PetAttributeCategoryEnum.SOCIAL,
    PetAttributeCategoryEnum.HYGIENE,
    PetAttributeCategoryEnum.CARE,
    PetAttributeCategoryEnum.HEALTH,
    PetAttributeCategoryEnum.HABITAT,
    PetAttributeCategoryEnum.DIET,
];
