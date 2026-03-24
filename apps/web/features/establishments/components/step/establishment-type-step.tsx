"use client";

import { useTranslations } from "next-intl";
import { Home, Heart, Sun, Shield, MoreHorizontal } from "lucide-react";
import {
    ChoiceCardContainer,
    ChoiceCardContent,
    ChoiceCardDescription,
    ChoiceCardIcon,
    ChoiceCardLabel,
    ChoiceCards,
    type ChoiceCardOption,
} from "@workspace/ui/components/choice-cards";
import { StepShell } from "./step-shell";

export type EstablishmentType = "pension" | "breeding" | "daycare" | "shelter" | "other";

type EstablishmentTypeStepProps = {
    value: EstablishmentType | null;
    onChange: (type: EstablishmentType) => void;
    error?: string;
};

export function EstablishmentTypeStep({ value, onChange, error }: EstablishmentTypeStepProps) {
    const t = useTranslations();

    const options: ChoiceCardOption<EstablishmentType>[] = [
        {
            value: "pension",
            icon: Home,
            label: t("features.become-host.steps.establishmentType.pension"),
        },
        {
            value: "breeding",
            icon: Heart,
            label: t("features.become-host.steps.establishmentType.breeding"),
        },
        {
            value: "daycare",
            icon: Sun,
            label: t("features.become-host.steps.establishmentType.daycare"),
        },
        {
            value: "shelter",
            icon: Shield,
            label: t("features.become-host.steps.establishmentType.shelter"),
        },
        {
            value: "other",
            icon: MoreHorizontal,
            label: t("features.become-host.steps.establishmentType.other"),
            className: "col-span-2",
        },
    ];

    return (
        <StepShell
            title={"Parmi les propositions suivantes, laquelle décrit le mieux votre structure ?"}
            // subtitle={t("features.become-host.steps.establishmentType.subtitle")}
        >
            <ChoiceCards
                mode="single"
                options={options}
                value={value}
                optionsClassName="grid-cols-3"
                render={(option, isSelected) => (
                    <ChoiceCardContainer
                        isSelected={isSelected}
                        disabled={option.disabled}
                        className="flex-col h-full justify-between"
                    >
                        {option.icon && (
                            <ChoiceCardIcon icon={option.icon} isSelected={isSelected} />
                        )}
                        <ChoiceCardContent>
                            <ChoiceCardLabel>{option.label}</ChoiceCardLabel>
                            {option.description && (
                                <ChoiceCardDescription>{option.description}</ChoiceCardDescription>
                            )}
                        </ChoiceCardContent>
                    </ChoiceCardContainer>
                )}
                onValueChange={onChange}
                error={error}
                layout="grid"
            />
        </StepShell>
    );
}
