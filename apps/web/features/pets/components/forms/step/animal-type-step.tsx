import Image from "next/image";
import { PawPrint } from "lucide-react";
import { useMemo, useState } from "react";
import { useTranslations } from "next-intl";
import { type AnimalTypeModel } from "@workspace/modules/pets";
import { ChoiceCardLabel } from "@workspace/ui/components/choice-cards";
import { cn } from "@workspace/ui/lib/utils";
import { type SingleChoiceCardStepOption } from "@/components/forms/stepper/single-choice-cards-step";
import { WizardStepShell } from "@/components/forms/stepper/wizard-step-shell";
import { isIllustratedType } from "@/features/pets/lib/pet-illustrations";
import { BackgroundShapeSvg } from "@/components/svg/background-shape";

export function AnimalTypeStep({
    animalTypes,
    value,
    onChange,
    error,
}: {
    animalTypes: AnimalTypeModel[];
    value: string | null;
    onChange: (value: string) => void;
    error?: string;
}) {
    const t = useTranslations();
    const animalTypeOptions = useMemo<SingleChoiceCardStepOption<string>[]>(
        () =>
            animalTypes.map((animalType) => {
                const normalizedCode = animalType.code.toLowerCase();

                return {
                    value: animalType.id.toString(),
                    label: animalType.name,
                    visual: isIllustratedType(normalizedCode) ? (
                        <div className="relative w-full h-24 group-hover:scale-115 transition-transform z-10">
                            <Image
                                src={`/illustrations/pets/${normalizedCode}.svg`}
                                alt={animalType.name}
                                className="object-contain"
                                fill
                            />
                        </div>
                    ) : (
                        <PawPrint className="size-1 text-primary" />
                    ),
                };
            }),
        [animalTypes],
    );

    const [rotations] = useState<Record<string, number>>(() =>
        animalTypes.reduce(
            (acc, animalType) => {
                const buf = new Uint32Array(1);
                crypto.getRandomValues(buf);
                acc[animalType.id.toString()] = (buf[0]! / 0xffffffff) * 360;
                return acc;
            },
            {} as Record<string, number>,
        ),
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
                                "relative group overflow-hidden flex flex-col items-start justify-between gap-6 rounded-lg border py-4 px-5 text-start transition-all",
                                isSelected ? "ring-2 ring-primary" : "border-input ",
                            )}
                            onClick={() => onChange(option.value)}
                        >
                            {option.visual}
                            <div className="flex flex-col gap-1 justify-center items-center w-full z-10">
                                <ChoiceCardLabel className="text-xl">
                                    {option.label}
                                </ChoiceCardLabel>
                                {option.description && (
                                    <span className="text-sm text-muted-foreground">
                                        {option.description}
                                    </span>
                                )}
                            </div>
                            <BackgroundShapeSvg
                                className={cn(
                                    "absolute top-0 -left-1/2 translate-x-1/2 -translate-y-1/2 text-muted scale-125",
                                    "z-0 group-hover:scale-120 transition-all duration-300",
                                    isSelected && "text-secondary",
                                )}
                                style={{ transform: `rotate(${rotations[option.value]}deg)` }}
                            />
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
