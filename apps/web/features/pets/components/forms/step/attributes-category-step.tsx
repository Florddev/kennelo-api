import { Plus, X } from "lucide-react";
import { useState } from "react";
import { useMessages, useTranslations } from "next-intl";
import {
    type AttributeDefinitionModel,
    type CreatePetInput,
    type PetAttributeCategory,
} from "@workspace/modules/pets";
import { Badge } from "@workspace/ui/components/badge";
import { ChoiceCards } from "@workspace/ui/components/choice-cards";
import { FieldLabel } from "@workspace/ui/components/field";
import { Textarea } from "@workspace/ui/components/textarea";
import { cn } from "@workspace/ui/lib/utils";
import { Control, useWatch } from "react-hook-form";
import { WizardStepShell } from "@/components/forms/stepper/wizard-step-shell";
import { TextareaController } from "@/components/forms/textarea-controller";
import { type AttributeDraft } from "../create-pet-stepper.types";

function readNestedMessage(messages: unknown, path: string): string | null {
    const segments = path.split(".");
    let current: unknown = messages;

    for (const segment of segments) {
        if (!current || typeof current !== "object" || !(segment in current)) {
            return null;
        }

        current = (current as Record<string, unknown>)[segment];
    }

    return typeof current === "string" ? current : null;
}

function MedicationsInput({
    value,
    onChange,
}: {
    value: string;
    onChange: (value: string) => void;
}) {
    const t = useTranslations();
    const [inputValue, setInputValue] = useState("");

    const medications = value
        ? value
              .split(",")
              .map((m) => m.trim())
              .filter(Boolean)
        : [];

    function addMedication() {
        const trimmed = inputValue.trim();
        if (!trimmed || medications.includes(trimmed)) {
            return;
        }
        onChange([...medications, trimmed].join(", "));
        setInputValue("");
    }

    function removeMedication(med: string) {
        const updated = medications.filter((m) => m !== med);
        onChange(updated.join(", "));
    }

    return (
        <div className="rounded-lg border bg-card p-3 py-4 flex flex-col gap-2 min-h-[48px]">
            {medications.length > 0 && (
                <div className="flex flex-wrap gap-1.5">
                    {medications.map((med) => (
                        <Badge key={med} variant="secondary" className="gap-1 pe-1">
                            {med}
                            <button
                                type="button"
                                onClick={() => removeMedication(med)}
                                className="rounded-full p-0.5 hover:bg-foreground/10 transition-colors"
                            >
                                <X className="size-3" />
                            </button>
                        </Badge>
                    ))}
                </div>
            )}
            <div className="flex gap-2 items-center">
                <input
                    type="text"
                    value={inputValue}
                    placeholder={t("features.pets.create.attributes.medicationsPlaceholder")}
                    className="flex-1 bg-transparent outline-none text-sm placeholder:text-muted-foreground"
                    onChange={(e) => setInputValue(e.target.value)}
                    onKeyDown={(e) => {
                        if (e.key === "Enter") {
                            e.preventDefault();
                            addMedication();
                        }
                    }}
                />
                <button
                    type="button"
                    onClick={addMedication}
                    disabled={!inputValue.trim()}
                    className="text-muted-foreground hover:text-foreground disabled:opacity-30 transition-colors"
                >
                    <Plus className="size-4" />
                </button>
            </div>
        </div>
    );
}

type OptionBadgeProps = {
    option: { id: number; value: string; label: string };
    definitionCode: string;
    isSelected: boolean;
    onSelect: () => void;
};

function OptionBadge({ option, definitionCode, isSelected, onSelect }: OptionBadgeProps) {
    const t = useTranslations();
    const messages = useMessages();
    const translationKey = `features.pets.attributes_definitions.${definitionCode}.options.${option.value}`;
    const label = readNestedMessage(messages, translationKey) ? t(translationKey) : option.label;

    return (
        <Badge
            asChild
            variant="outline"
            className={cn(
                "h-10 px-6 rounded-full bg-card cursor-pointer text-sm",
                isSelected && "ring-[1px] bg-primary/5 border-primary ring-primary text-primary",
            )}
        >
            <button type="button" onClick={onSelect}>
                {label}
            </button>
        </Badge>
    );
}

type BooleanInputProps = {
    value: string | null;
    onChange: (value: string) => void;
};

function BooleanInput({ value, onChange }: BooleanInputProps) {
    const t = useTranslations();
    return (
        <ChoiceCards
            mode="single"
            value={value}
            onValueChange={onChange}
            options={[
                { value: "true", label: t("common.actions.yes") },
                { value: "false", label: t("common.actions.no") },
            ]}
            layout="grid"
            optionsClassName="grid-cols-2"
        />
    );
}

function draftFreeText(draft: AttributeDraft | undefined): string {
    return draft?.freeText ?? "";
}

function draftFreeTextOrNull(draft: AttributeDraft | undefined): string | null {
    return draft?.freeText ?? null;
}

type AttributeInputProps = {
    definition: AttributeDefinitionModel;
    draft: AttributeDraft | undefined;
    setDraft: (definitionId: number, patch: Partial<AttributeDraft>) => void;
};

function AttributeInput({ definition, draft, setDraft }: AttributeInputProps) {
    const t = useTranslations();

    const onFreeTextChange = (value: string) =>
        setDraft(definition.id, {
            attributeDefinitionId: definition.id,
            freeText: value,
            attributeOptionId: undefined,
        });

    if (definition.code === "medications") {
        return <MedicationsInput value={draftFreeText(draft)} onChange={onFreeTextChange} />;
    }

    if (definition.valueType === "boolean") {
        return <BooleanInput value={draftFreeTextOrNull(draft)} onChange={onFreeTextChange} />;
    }

    if (definition.hasPredefinedOptions && definition.options) {
        return (
            <div className="flex flex-wrap gap-2">
                {definition.options.map((option) => (
                    <OptionBadge
                        key={option.id}
                        option={option}
                        definitionCode={definition.code}
                        isSelected={draft?.attributeOptionId === option.id}
                        onSelect={() =>
                            setDraft(definition.id, {
                                attributeDefinitionId: definition.id,
                                attributeOptionId: option.id,
                                freeText: "",
                            })
                        }
                    />
                ))}
            </div>
        );
    }

    return (
        <Textarea
            value={draftFreeText(draft)}
            placeholder={t("features.pets.create.attributes.freeTextPlaceholder")}
            className="rounded-2xl"
            onChange={(event) =>
                setDraft(definition.id, {
                    attributeDefinitionId: definition.id,
                    freeText: event.target.value,
                    attributeOptionId: undefined,
                })
            }
        />
    );
}

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
    const messages = useMessages();
    const petName = useWatch({ control, name: "name" });
    const questionPetName = petName?.trim() || t("features.pets.create.attributes.defaultPetName");

    return (
        <WizardStepShell
            title={t(`features.pets.create.steps.attributesCategoryTitles.${category}`)}
        >
            <div className="flex flex-col gap-6">
                {definitions.map((definition) => {
                    const translationKey = `features.pets.attributes_definitions.${definition.code}.question`;
                    const translatedQuestion = readNestedMessage(messages, translationKey);
                    const question = translatedQuestion
                        ? t(translationKey, { petName: questionPetName })
                        : t("features.pets.create.attributes.fallbackQuestion", {
                              label: definition.label,
                          });

                    return (
                        <div key={definition.id} className="space-y-2.5">
                            <FieldLabel>{question}</FieldLabel>
                            <AttributeInput
                                definition={definition}
                                draft={drafts[definition.id]}
                                setDraft={setDraft}
                            />
                        </div>
                    );
                })}
                {category === "health" && (
                    <TextareaController
                        control={control}
                        name="healthNotes"
                        rows={4}
                        label={t("features.pets.fields.healthNotes")}
                        placeholder={t("features.pets.create.placeholders.healthNotes")}
                    />
                )}
            </div>
        </WizardStepShell>
    );
}
