import { useTranslations } from "next-intl";
import {
    type AttributeDefinitionModel,
    type CreatePetInput,
    type PetAttributeCategory,
} from "@workspace/modules/pets";
import { Control, useWatch } from "react-hook-form";
import { WizardStepShell } from "@/components/forms/stepper/wizard-step-shell";
import { InlineController } from "@/components/forms/inline-controller";
import { AttributeInline } from "@/features/pets/components/attribute-inline";
import { getDraftPatch } from "@/features/pets/utils/attribute-form-utils";
import { type AttributeDraft } from "../create-pet-stepper.types";

type AttributesCategoryStepProps = {
    category: PetAttributeCategory;
    definitions: AttributeDefinitionModel[];
    drafts: Record<number, AttributeDraft>;
    setDraft: (definitionId: number, patch: Partial<AttributeDraft>) => void;
    control: Control<CreatePetInput>;
};

export function AttributesCategoryStep({
    category,
    definitions,
    drafts,
    setDraft,
    control,
}: AttributesCategoryStepProps) {
    const t = useTranslations();
    const petName = useWatch({ control, name: "name" });
    const questionPetName = petName?.trim() || t("features.pets.create.attributes.defaultPetName");

    return (
        <WizardStepShell
            title={t(`features.pets.create.steps.attributesCategoryTitles.${category}`)}
        >
            {definitions.map((definition) => (
                <AttributeInline
                    key={definition.id}
                    definition={definition}
                    draft={drafts[definition.id]}
                    onChange={(v) => setDraft(definition.id, getDraftPatch(definition, v))}
                    isLoading={false}
                    petName={questionPetName}
                />
            ))}
            {category === "health" && (
                <InlineController
                    control={control}
                    name="healthNotes"
                    type="textarea"
                    label={t("features.pets.fields.healthNotes")}
                    placeholder={t("features.pets.create.placeholders.healthNotes")}
                />
            )}
        </WizardStepShell>
    );
}
