import Image from "next/image";
import { PawPrint } from "lucide-react";
import { useMemo } from "react";
import { useTranslations } from "next-intl";
import { type AnimalTypeModel } from "@workspace/modules/pets";
import { ChoiceCardLabel } from "@workspace/ui/components/choice-cards";
import { cn } from "@workspace/ui/lib/utils";
import { type SingleChoiceCardStepOption } from "@/components/forms/stepper/single-choice-cards-step";
import { WizardStepShell } from "@/components/forms/stepper/wizard-step-shell";
import { isIllustratedType } from "@/features/pets/lib/pet-illustrations";

type AnimalTypeStepProps = {
    animalTypes: AnimalTypeModel[];
    value: string | null;
    onChange: (value: string) => void;
    error?: string;
};

export function AnimalTypeStep({ animalTypes, value, onChange, error }: AnimalTypeStepProps) {
    const t = useTranslations();
    const animalTypeOptions = useMemo<SingleChoiceCardStepOption<string>[]>(
        () =>
            animalTypes.map((animalType) => {
                const normalizedCode = animalType.code.toLowerCase();

                return {
                    value: animalType.id.toString(),
                    label: animalType.name,
                    visual: isIllustratedType(normalizedCode) ? (
                        <Image
                            src={`/illustrations/pets/${normalizedCode}.svg`}
                            alt={animalType.name}
                            width={40}
                            height={40}
                            className="size-10 object-contain"
                        />
                    ) : (
                        <PawPrint className="size-10 text-primary" />
                    ),
                };
            }),
        [animalTypes],
    );

    return (
        <WizardStepShell title={t("features.pets.create.steps.type.title")}>
            <div className="grid grid-cols-2 md:grid-cols-3 gap-3">
                {animalTypeOptions.map((option) => {
                    const isSelected = option.value === value;

                    return (
                        <button
                            key={option.value}
                            type="button"
                            className={cn(
                                "flex flex-col items-start justify-between gap-4 rounded-lg border py-4 px-5 text-start transition-all",
                                isSelected
                                    ? "ring-2 ring-primary bg-primary/5"
                                    : "border-input hover:border-primary/60",
                            )}
                            onClick={() => onChange(option.value)}
                        >
                            {option.visual}
                            <div className="flex flex-col gap-1">
                                <ChoiceCardLabel>{option.label}</ChoiceCardLabel>
                                {option.description && (
                                    <span className="text-sm text-muted-foreground">
                                        {option.description}
                                    </span>
                                )}
                            </div>
                        </button>
                    );
                })}
            </div>
            {error && (
                <p className="text-sm text-destructive bg-destructive/5 border border-destructive/30 rounded-2xl px-4 py-3">
                    {error}
                </p>
            )}
        </WizardStepShell>
    );
}
