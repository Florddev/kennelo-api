import { petAttributeCategoryOrder, type PetAttributeCategory } from "@workspace/modules/pets";

export const CATEGORY_ORDER: PetAttributeCategory[] = petAttributeCategoryOrder;

export enum StepGroup {
    GENERAL = "general",
    ATTRIBUTES = "attributes",
    COMPLETION = "completion",
}

export enum Step {
    GENERAL_INTRO = "general-intro",
    ANIMAL_TYPE = "animal-type",
    IDENTITY_BASICS = "identity-basics",
    PROFILE = "profile",
    ATTRIBUTES_INTRO = "attributes-intro",
    COMPLETION_INTRO = "completion-intro",
    MEDIA = "media",
}
