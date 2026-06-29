import { type AttributeDefinitionModel, type CreatePetInput } from "@workspace/modules/pets";
import { type AttributeDraft } from "./create-pet-stepper.types";

type AttributeValuePayload =
    | { valueText: string }
    | { valueInteger: number }
    | { valueDecimal: number }
    | { valueBoolean: boolean }
    | { valueDate: string };

function parseAttributeValueByType(
    valueType: string,
    rawValue: string,
): AttributeValuePayload | null {
    const normalizedText = rawValue.trim();

    if (!normalizedText) {
        return null;
    }

    if (valueType === "integer") {
        const parsedInteger = Number.parseInt(normalizedText, 10);
        if (Number.isNaN(parsedInteger)) {
            return null;
        }

        return { valueInteger: parsedInteger };
    }

    if (valueType === "decimal") {
        const parsedDecimal = Number.parseFloat(normalizedText);
        if (Number.isNaN(parsedDecimal)) {
            return null;
        }

        return { valueDecimal: parsedDecimal };
    }

    if (valueType === "date") {
        return { valueDate: normalizedText };
    }

    if (valueType === "boolean") {
        if (normalizedText.toLowerCase() === "true") {
            return { valueBoolean: true };
        }

        if (normalizedText.toLowerCase() === "false") {
            return { valueBoolean: false };
        }

        return null;
    }

    return { valueText: normalizedText };
}

function toAttributeValuePayload(
    definition: AttributeDefinitionModel,
    rawValue: string,
): AttributeValuePayload | null {
    if (typeof definition.toValuePayload === "function") {
        return definition.toValuePayload(rawValue);
    }

    return parseAttributeValueByType(definition.valueType, rawValue);
}

function normalizeNullableString(value?: string | null): string | null {
    if (!value) {
        return null;
    }

    const normalized = value.trim();
    return normalized.length > 0 ? normalized : null;
}

export function buildCreatePetPayload(values: CreatePetInput): CreatePetInput {
    return {
        animalTypeId: values.animalTypeId,
        animalBreedId: values.animalBreedId ?? null,
        name: values.name,
        breed: normalizeNullableString(values.breed),
        birthDate: normalizeNullableString(values.birthDate),
        sex: values.sex ?? null,
        weight: values.weight ?? null,
        isSterilized: values.isSterilized ?? null,
        hasMicrochip: values.hasMicrochip ?? false,
        microchipNumber: values.hasMicrochip
            ? normalizeNullableString(values.microchipNumber)
            : null,
        adoptionDate: normalizeNullableString(values.adoptionDate),
        about: normalizeNullableString(values.about),
        healthNotes: normalizeNullableString(values.healthNotes),
    };
}

export function buildAttributePayload(
    selectedAnimalAttributes: AttributeDefinitionModel[],
    drafts: Record<number, AttributeDraft>,
) {
    const definitionById = new Map(
        selectedAnimalAttributes.map((definition) => [definition.id, definition]),
    );

    const attributes = Object.values(drafts)
        .map((draft) => {
            const definition = definitionById.get(draft.attributeDefinitionId);

            if (!definition) {
                return null;
            }

            if (draft.attributeOptionId) {
                const selectedOption = definition.options?.find(
                    (option) => option.id === draft.attributeOptionId,
                );

                if (!selectedOption) {
                    return null;
                }

                const optionValuePayload = toAttributeValuePayload(
                    definition,
                    selectedOption.value,
                ) ?? {
                    valueText: selectedOption.value,
                };

                return {
                    attributeDefinitionId: draft.attributeDefinitionId,
                    attributeOptionId: draft.attributeOptionId,
                    ...optionValuePayload,
                };
            }

            const freeText = draft.freeText ?? "";
            const valuePayload = toAttributeValuePayload(definition, freeText);

            if (!valuePayload) {
                return null;
            }

            return {
                attributeDefinitionId: draft.attributeDefinitionId,
                ...valuePayload,
            };
        })
        .filter((item): item is NonNullable<typeof item> => item !== null);

    return attributes.length > 0 ? { attributes } : null;
}

export function applyAttributeDraftPatch(
    previous: Record<number, AttributeDraft>,
    definitionId: number,
    patch: Partial<AttributeDraft>,
): Record<number, AttributeDraft> {
    return {
        ...previous,
        [definitionId]: {
            ...previous[definitionId],
            ...patch,
            attributeDefinitionId: definitionId,
        },
    };
}
