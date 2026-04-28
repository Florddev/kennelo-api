export const ANIMAL_TYPE_CODES = [
    "dog",
    "cat",
    "bird",
    "rabbit",
    "reptile",
    "amphibian",
    "fish",
    "spider",
] as const;

export type AnimalTypeCode = (typeof ANIMAL_TYPE_CODES)[number];

export function isKnownAnimalTypeCode(code: string): code is AnimalTypeCode {
    return ANIMAL_TYPE_CODES.includes(code as AnimalTypeCode);
}
