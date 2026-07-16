"use client";

import Image from "next/image";
import { Check, PawPrint } from "lucide-react";
import { useMemo, useState } from "react";
import { useTranslations } from "next-intl";
import { type AnimalTypeModel } from "@workspace/modules/pets";
import { ChoiceCardLabel } from "@workspace/ui/components/choice-cards";
import { cn } from "@workspace/ui/lib/utils";
import { WizardStepShell } from "@/components/forms/stepper/wizard-step-shell";
import { isIllustratedType } from "@/features/pets/lib/pet-illustrations";
import { BackgroundShapeSvg } from "@/components/svg/background-shape";

type AnimalTypesStepProps = {
    animalTypes: AnimalTypeModel[];
    value: string[];
    onChange: (value: string[]) => void;
    error?: string;
};

export function AnimalTypesStep({ animalTypes, value, onChange, error }: AnimalTypesStepProps) {
    const t = useTranslations();

    const options = useMemo(
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

    const toggle = (optionValue: string) => {
        if (value.includes(optionValue)) {
            onChange(value.filter((current) => current !== optionValue));
            return;
        }

        onChange([...value, optionValue]);
    };

    return (
        <WizardStepShell
            title={t("features.become-host.steps.animalTypes.title")}
            subtitle={t("features.become-host.steps.animalTypes.subtitle")}
        >
            <div className="grid grid-cols-2 md:grid-cols-3 gap-3">
                {options.map((option) => {
                    const isSelected = value.includes(option.value);

                    return (
                        <button
                            key={option.value}
                            type="button"
                            aria-pressed={isSelected}
                            className={cn(
                                "relative group overflow-hidden flex flex-col items-start justify-between gap-6 rounded-lg border py-4 px-5 text-start transition-all",
                                isSelected ? "ring-2 ring-primary" : "border-input ",
                            )}
                            onClick={() => toggle(option.value)}
                        >
                            <span
                                className={cn(
                                    "absolute top-3 end-3 z-20 flex size-6 items-center justify-center rounded-full border transition-all",
                                    isSelected
                                        ? "bg-primary border-primary text-primary-foreground"
                                        : "bg-background/60 border-input text-transparent",
                                )}
                            >
                                <Check className="size-3.5" />
                            </span>
                            {option.visual}
                            <div className="flex flex-col gap-1 justify-center items-center w-full z-10">
                                <ChoiceCardLabel className="text-xl">
                                    {option.label}
                                </ChoiceCardLabel>
                            </div>
                            <BackgroundShapeSvg
                                className={cn(
                                    "absolute top-0 -start-1/2 translate-x-1/2 -translate-y-1/2 text-muted scale-125",
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
