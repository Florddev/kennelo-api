import { type AttributeDefinitionModel, type PetModel } from "@workspace/modules/pets";

import { applyAttributeDraftPatch } from "@/features/pets/components/forms/create-pet-stepper.mappers";
import type { AttributeDraft } from "@/features/pets/components/forms/create-pet-stepper.types";
import { type InlineValue } from "@/components/forms/inline-inputs/types";

export function readNestedMessage(messages: unknown, path: string): string | null {
    const segments = path.split(".");
    let current: unknown = messages;
    for (const segment of segments) {
        if (!current || typeof current !== "object" || !(segment in current)) return null;
        current = (current as Record<string, unknown>)[segment];
    }
    return typeof current === "string" ? current : null;
}

export function initDraftsFromPet(pet: PetModel): Record<number, AttributeDraft> {
    const drafts: Record<number, AttributeDraft> = {};
    pet.attributes?.forEach((attr) => {
        const draft: AttributeDraft = { attributeDefinitionId: attr.attributeDefinitionId };
        if (attr.attributeOptionId) {
            draft.attributeOptionId = attr.attributeOptionId;
        } else if (attr.value !== null) {
            draft.freeText = String(attr.value);
        }
        drafts[attr.attributeDefinitionId] = draft;
    });
    return drafts;
}

function parseBooleanDraft(freeText?: string): boolean | undefined {
    if (freeText === "true") return true;
    if (freeText === "false") return false;
    return undefined;
}

function parseNumberDraft(freeText?: string): number | undefined {
    const n = parseFloat(freeText ?? "");
    return isNaN(n) ? undefined : n;
}

export function getDraftValue(
    definition: AttributeDefinitionModel,
    draft?: AttributeDraft,
): InlineValue {
    const freeText = draft?.freeText;

    switch (definition.inputType) {
        case "badge-list":
            return draft?.attributeOptionId ?? "";
        case "boolean":
            return parseBooleanDraft(freeText);
        case "number":
            return parseNumberDraft(freeText);
        case "multi-list":
            return freeText
                ? freeText
                      .split(",")
                      .map((m) => m.trim())
                      .filter(Boolean)
                : [];
        default:
            return freeText ?? "";
    }
}

export function getDraftPatch(
    definition: AttributeDefinitionModel,
    value: InlineValue,
): Partial<AttributeDraft> {
    if (definition.inputType === "badge-list") {
        const v = value as string;
        return { attributeOptionId: v || undefined, freeText: "" };
    }
    if (definition.inputType === "multi-list") {
        const arr = (value as string[]) ?? [];
        return { freeText: arr.join(", "), attributeOptionId: undefined };
    }
    return {
        freeText: value !== null && value !== undefined ? String(value) : "",
        attributeOptionId: undefined,
    };
}

export function applyInlineValue(
    prev: Record<number, AttributeDraft>,
    definition: AttributeDefinitionModel,
    value: InlineValue,
): Record<number, AttributeDraft> {
    return applyAttributeDraftPatch(prev, definition.id, getDraftPatch(definition, value));
}
